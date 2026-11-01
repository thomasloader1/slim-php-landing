<?php

namespace App\Controllers\Admin;

use App\Models\User;
use App\Services\AccessService;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Usuarios de la DB actual (master o sitio). Reglas:
 *  - Solo se asignan roles de AccessService::assignableRoles() (root y admin, solo el root).
 *  - Nadie cambia su propio rol, se desactiva ni se borra.
 *  - No se puede degradar ni borrar al último root.
 */
class UserAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $users = User::select('id', 'name', 'email', 'role', 'active', 'created_at')->orderBy('id')->get();
        return $this->render($response, 'admin/users/index', [
            'users'      => $users,
            'roleNames'  => [AccessService::ROOT => 'Root'] + Capsule::table('roles')->pluck('name', 'slug')->all(),
            'manageable' => array_keys(AccessService::assignableRoles()),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/users/create', ['roles' => AccessService::assignableRoles()]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $back = url('admin/users/create');

        $error = $this->validate($data, null) ?? $this->validateRole((string) ($data['role'] ?? ''));
        if (!$error && strlen((string) ($data['password'] ?? '')) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($error) {
            return $this->fail($response, $back, $error);
        }

        User::create([
            'name'          => trim((string) $data['name']),
            'email'         => strtolower(trim((string) $data['email'])),
            'password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role'          => (string) $data['role'],
            'active'        => isset($data['active']) ? 1 : 0,
        ]);

        $_SESSION['flash_success'] = 'Usuario creado.';
        return $response->withHeader('Location', url('admin/users'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = User::find($args['id']);
        if ($error = $this->cannotManage($user)) {
            return $this->fail($response, url('admin/users'), $error);
        }
        return $this->render($response, 'admin/users/edit', [
            'user'   => $user,
            'roles'  => AccessService::assignableRoles(),
            'isSelf' => $this->isSelf($user),
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = User::find($args['id']);
        if ($error = $this->cannotManage($user)) {
            return $this->fail($response, url('admin/users'), $error);
        }
        $data = (array) $request->getParsedBody();
        $back = url('admin/users/edit/' . $user->id);
        $self = $this->isSelf($user);

        // El propio rol y estado no se tocan desde acá (evita quedarse afuera)
        $role = $self ? $user->role : (string) ($data['role'] ?? '');
        $active = $self ? 1 : (isset($data['active']) ? 1 : 0);

        $error = $this->validate($data, $user->id) ?? ($self ? null : $this->validateRole($role));
        if (!$error && !empty($data['password']) && strlen((string) $data['password']) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!$error && $user->role === AccessService::ROOT && ($role !== AccessService::ROOT || !$active) && $this->rootCount() <= 1) {
            $error = 'Es el único root: no se le puede quitar el rol ni desactivarlo.';
        }
        if ($error) {
            return $this->fail($response, $back, $error);
        }

        $update = [
            'name'   => trim((string) $data['name']),
            'email'  => strtolower(trim((string) $data['email'])),
            'role'   => $role,
            'active' => $active,
        ];
        if (!empty($data['password'])) {
            $update['password_hash'] = password_hash((string) $data['password'], PASSWORD_BCRYPT);
        }
        $user->update($update);

        $_SESSION['flash_success'] = 'Usuario actualizado.';
        return $response->withHeader('Location', url('admin/users'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = User::find($args['id']);
        $error = $this->cannotManage($user);
        if (!$error && $this->isSelf($user)) {
            $error = 'No podés borrar tu propio usuario.';
        }
        if (!$error && $user->role === AccessService::ROOT && $this->rootCount() <= 1) {
            $error = 'Es el único root: no se puede borrar.';
        }
        if ($error) {
            return $this->fail($response, url('admin/users'), $error);
        }

        $user->delete();
        $_SESSION['flash_success'] = 'Usuario eliminado.';
        return $response->withHeader('Location', url('admin/users'))->withStatus(302);
    }

    /** null si el usuario actual puede editar a $user; si no, el motivo. */
    private function cannotManage(?User $user): ?string
    {
        if (!$user) {
            return 'El usuario no existe.';
        }
        if ($this->isSelf($user) || AccessService::isRoot()) {
            return null;
        }
        return in_array($user->role, array_keys(AccessService::assignableRoles()), true)
            ? null
            : 'Solo el root puede modificar a ese usuario.';
    }

    private function validate(array $data, ?int $ignoreId): ?string
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($name === '' || mb_strlen($name) > 100) {
            return 'El nombre es obligatorio (máx. 100 caracteres).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            return 'El email no es válido.';
        }
        $taken = User::where('email', $email)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        return $taken ? 'Ya existe un usuario con ese email.' : null;
    }

    private function validateRole(string $role): ?string
    {
        return array_key_exists($role, AccessService::assignableRoles()) ? null : 'Elegí un rol válido.';
    }

    private function isSelf(User $user): bool
    {
        return (int) ($_SESSION['user_id'] ?? 0) === (int) $user->id && empty($_SESSION['impersonated_by']);
    }

    private function rootCount(): int
    {
        return User::where('role', AccessService::ROOT)->where('active', 1)->count();
    }

    private function fail(Response $response, string $location, string $message): Response
    {
        $_SESSION['flash_error'] = $message;
        return $response->withHeader('Location', $location)->withStatus(302);
    }
}
