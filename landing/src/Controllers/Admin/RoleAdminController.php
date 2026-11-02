<?php

namespace App\Controllers\Admin;

use App\Models\Role;
use App\Services\AccessService;
use App\Support\Permissions;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Roles y permisos (solo root, ver Permissions::CATALOG['roles']).
 * Trabaja sobre la DB actual: en la master configura los usuarios de la
 * master; dentro de un sitio (root entra por SSO) configura los de ese sitio.
 */
class RoleAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $roles = Role::orderBy('is_system', 'desc')->orderBy('name')->get();
        return $this->render($response, 'admin/roles/index', [
            'roles'       => $roles,
            'catalog'     => Permissions::assignable(),
            'inMaster'    => AccessService::isMasterContext(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/roles/form', ['role' => null, 'granted' => []] + self::formData());
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim((string) ($data['name'] ?? ''));

        if ($error = $this->validateName($name, null)) {
            return $this->fail($response, url('admin/roles/create'), $error);
        }
        $slug = self::slugify($name);
        if ($slug === '' || $slug === AccessService::ROOT || Role::where('slug', $slug)->exists()) {
            return $this->fail($response, url('admin/roles/create'), 'Ya existe un rol con un nombre equivalente. Elegí otro nombre.');
        }

        $role = Role::create(['slug' => $slug, 'name' => $name, 'is_system' => 0]);
        $role->syncPermissions(self::filterPermissions($data['permissions'] ?? []));

        $_SESSION['flash_success'] = "Rol \"{$name}\" creado.";
        return $response->withHeader('Location', url('admin/roles'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $role = Role::find($args['id']);
        if (!$role) {
            return $this->fail($response, url('admin/roles'), 'El rol no existe.');
        }
        return $this->render($response, 'admin/roles/form', [
            'role' => $role, 'granted' => $role->permissions(),
        ] + self::formData());
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $role = Role::find($args['id']);
        if (!$role) {
            return $this->fail($response, url('admin/roles'), 'El rol no existe.');
        }
        $data = (array) $request->getParsedBody();
        $name = trim((string) ($data['name'] ?? ''));

        if ($error = $this->validateName($name, $role->id)) {
            return $this->fail($response, url('admin/roles/edit/' . $role->id), $error);
        }

        $role->update(['name' => $name]); // el slug no cambia: lo referencian los usuarios
        // Los módulos apagados no se muestran en el formulario: sus permisos guardados se conservan
        $hidden = array_filter($role->permissions(), fn ($k) => AccessService::isOff(Permissions::sectionOf($k)));
        $role->syncPermissions(array_merge(self::filterPermissions($data['permissions'] ?? []), $hidden));

        $_SESSION['flash_success'] = "Permisos de \"{$name}\" guardados. Aplican en el próximo clic de sus usuarios.";
        return $response->withHeader('Location', url('admin/roles'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $role = Role::find($args['id']);
        if (!$role) {
            return $this->fail($response, url('admin/roles'), 'El rol no existe.');
        }
        if ($role->is_system) {
            return $this->fail($response, url('admin/roles'), 'Los roles del sistema no se pueden borrar (podés quitarles permisos).');
        }
        if (($count = $role->usersCount()) > 0) {
            return $this->fail($response, url('admin/roles'), "No se puede borrar \"{$role->name}\": lo usan {$count} usuario(s). Cambiales el rol primero.");
        }
        $role->delete(); // role_permissions se borra en cascada

        $_SESSION['flash_success'] = "Rol \"{$role->name}\" eliminado.";
        return $response->withHeader('Location', url('admin/roles'))->withStatus(302);
    }

    /**
     * Solo claves asignables (secciones o funciones; descarta root/master y basura).
     * Todas las funciones de una sección tildadas → se guarda la sección completa.
     */
    public static function filterPermissions($input): array
    {
        return Permissions::normalizeGrants(is_array($input) ? $input : []);
    }

    /**
     * Datos del formulario: las secciones asignables que están encendidas en Módulos,
     * agrupadas como el sidebar, con sus funciones; y las apagadas aparte.
     */
    public static function formData(): array
    {
        $groups = [];
        $off = [];
        foreach (Permissions::assignable() as $key => $section) {
            if (AccessService::isOff($key)) {
                $off[$key] = $section['label'];
                continue;
            }
            $group = $section['group'];
            $groups[$group] ??= (Permissions::GROUPS[$group] ?? ['label' => $group, 'icon' => 'fa-folder']) + ['sections' => []];
            $groups[$group]['sections'][$key] = $section + ['features' => Permissions::FEATURES[$key] ?? []];
        }
        return ['groups' => $groups, 'off' => $off, 'catalog' => Permissions::assignable()];
    }

    /** "Vendedor Sucursal" → "vendedor-sucursal" */
    public static function slugify(string $name): string
    {
        $v = mb_strtolower(trim($name));
        $v = strtr($v, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $v = trim((string) preg_replace('/[^a-z0-9]+/', '-', $v), '-');
        return substr($v, 0, 40);
    }

    private function validateName(string $name, ?int $ignoreId): ?string
    {
        if ($name === '') {
            return 'El nombre del rol es obligatorio.';
        }
        if (mb_strlen($name) > 100) {
            return 'El nombre no puede superar 100 caracteres.';
        }
        $taken = Role::where('name', $name)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        return $taken ? "Ya existe un rol llamado \"{$name}\"." : null;
    }

    private function fail(Response $response, string $location, string $message): Response
    {
        $_SESSION['flash_error'] = $message;
        return $response->withHeader('Location', $location)->withStatus(302);
    }
}
