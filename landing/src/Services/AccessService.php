<?php

namespace App\Services;

use App\Support\Permissions;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Qué secciones del admin puede usar el usuario logueado.
 *
 *  - root: users.role = 'root' en la master, o el root de la master que
 *    entró a un sitio por SSO (sesión con impersonated_by). Ve todo.
 *  - resto: los permisos de su rol (role_permissions) en la DB actual.
 *  - un módulo apagado oculta y bloquea su sección para todos.
 *
 * Cachea por request (estático); AuthMiddleware llama reset() al cargar el usuario.
 */
final class AccessService
{
    public const ROOT = 'root';

    private static ?array $permissions = null;
    private static ?array $modules = null;

    public static function reset(): void
    {
        self::$permissions = null;
        self::$modules = null;
        self::$rootExists = null;
    }

    public static function isMasterContext(): bool
    {
        return ($_SESSION['context'] ?? 'master') === 'master';
    }

    public static function isRoot(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        if (!empty($_SESSION['impersonated_by'])) {
            return true; // root de la master dentro de un sitio (SSO)
        }
        $role = $_SESSION['user_role'] ?? '';
        if ($role === self::ROOT) {
            return true; // en un sitio solo controla ese sitio: lo de la master sigue bloqueado por scope
        }
        // Base master sin migrar a roles (no hay ningún root todavía): el admin actúa
        // como root para poder entrar a Sistema → Aplicar migraciones.
        return $role === 'admin' && self::isMasterContext() && !self::rootExists();
    }

    private static ?bool $rootExists = null;

    private static function rootExists(): bool
    {
        if (self::$rootExists === null) {
            try {
                self::$rootExists = Capsule::table('users')->where('role', self::ROOT)->exists();
            } catch (\Throwable $e) {
                self::$rootExists = false;
            }
        }
        return self::$rootExists;
    }

    /** @return string[] claves de permiso del rol del usuario en la DB actual */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        $role = (string) ($_SESSION['user_role'] ?? '');
        if ($role === '' || !isset($_SESSION['user_id'])) {
            return self::$permissions = [];
        }
        try {
            self::$permissions = Capsule::table('role_permissions')
                ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
                ->where('roles.slug', $role)
                ->pluck('role_permissions.permission')
                ->all();
        } catch (\Throwable $e) {
            // Base sin migrar todavía: sin permisos (solo el root entra)
            log_error('[AccessService] ' . $e->getMessage(), null, [], $e);
            self::$permissions = [];
        }
        return self::$permissions;
    }

    public static function moduleEnabled(string $flag): bool
    {
        if (self::$modules === null) {
            $flags = array_values(array_unique(array_filter(array_merge(
                array_column(Permissions::CATALOG, 'module'),
                array_column(Permissions::CATALOG, 'requires')
            ))));
            self::$modules = Capsule::table('settings')->whereIn('setting_key', $flags)
                ->pluck('setting_value', 'setting_key')->all();
        }
        $default = in_array($flag, Permissions::MODULES_OFF_BY_DEFAULT, true) ? '0' : '1';
        return (self::$modules[$flag] ?? $default) === '1';
    }

    /**
     * ¿Puede usar la sección ("links") o la función ("links.delete")?
     * Sección: alcanza con tener la sección completa o alguna de sus funciones.
     */
    public static function can(string $key): bool
    {
        $section = Permissions::sectionOf($key);
        $perm = Permissions::CATALOG[$section] ?? null;
        if ($perm === null || ($key !== $section && !isset(Permissions::FEATURES[$section][$key]))) {
            return false;
        }
        if ($perm['scope'] === 'master' && !self::isMasterContext()) {
            return false;
        }
        if (self::isRoot()) {
            return true; // el root ve también lo apagado (marcado "Off") para poder configurarlo
        }
        if (self::isOff($section) || $perm['scope'] !== 'site') {
            return false;
        }
        $granted = self::grantedFeatures();
        if (in_array($section, self::permissions(), true)) {
            return true; // sección completa = todas sus funciones
        }
        if ($key === $section) {
            foreach ($granted as $g) {
                if (Permissions::sectionOf($g) === $section) {
                    return true;
                }
            }
            return false;
        }
        return in_array($key, $granted, true);
    }

    /** ¿Tiene la sección completa (todas sus funciones)? */
    public static function canAll(string $section): bool
    {
        return self::can($section) && (self::isRoot() || in_array($section, self::permissions(), true));
    }

    /** ¿Puede hacer este pedido? (sección y, si tiene funciones, la función que corresponde a la ruta) */
    public static function canRequest(string $section, string $method, string $path): bool
    {
        if (empty(Permissions::FEATURES[$section])) {
            return self::can($section);
        }
        $feature = Permissions::featureFor($section, $method, $path);
        return $feature === null ? self::canAll($section) : self::can($feature);
    }

    /** Funciones otorgadas al rol, con las implícitas (editar → ver). */
    private static function grantedFeatures(): array
    {
        $out = [];
        foreach (self::permissions() as $key) {
            $section = Permissions::sectionOf($key);
            if ($key === $section) {
                continue;
            }
            $out[] = $key;
            array_push($out, ...(Permissions::FEATURES[$section][$key]['implies'] ?? []));
        }
        return array_values(array_unique($out));
    }

    /** ¿La sección está apagada en Módulos (su flag o el que requiere)? */
    public static function isOff(string $key): bool
    {
        $perm = Permissions::CATALOG[$key] ?? null;
        if ($perm === null) {
            return true;
        }
        foreach ([$perm['module'] ?? null, $perm['requires'] ?? null] as $flag) {
            if ($flag !== null && !self::moduleEnabled($flag)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string,array> secciones visibles en el sidebar, en orden */
    public static function visibleSections(): array
    {
        return array_filter(Permissions::CATALOG, fn ($p, $key) => self::can($key), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Sidebar agrupado: [groupKey => ['label','icon','active','items' => [[key,label,icon,url,active,off], ...]]] (off = apagada en Módulos; solo la ve el root).
     * Las secciones con 'children' se expanden en sus sub-links (mismo permiso).
     * $path: path actual sin base (para marcar activo); null = sin activo.
     */
    public static function visibleNav(?string $path = null): array
    {
        $path = $path === null ? null : '/' . trim($path, '/');
        $activeKey = $path === null ? null : Permissions::forPath($path);
        $matches = fn (string $prefix) => $path !== null && ($path === $prefix || str_starts_with($path, $prefix . '/'));

        $nav = [];
        foreach (self::visibleSections() as $key => $section) {
            $off = self::isOff($key);
            $items = [];
            foreach ($section['children'] ?? [] as $child) {
                if (!empty($child['feature']) && !self::can($child['feature'])) {
                    continue; // rol con permiso parcial: solo las sub-secciones que puede usar
                }
                $items[] = ['key' => $key, 'label' => $child['label'], 'icon' => $child['icon'], 'url' => $child['url'], 'active' => $matches($child['prefix']), 'off' => $off];
            }
            if (!$items) {
                $items[] = ['key' => $key, 'label' => $section['label'], 'icon' => $section['icon'], 'url' => self::entryUrl($key), 'active' => $activeKey === $key, 'off' => $off];
            } elseif ($activeKey === $key && !in_array(true, array_column($items, 'active'), true)) {
                $items[0]['active'] = true;
            }

            $group = $section['group'] ?? $key;
            $nav[$group] ??= (Permissions::GROUPS[$group] ?? ['label' => $section['label'], 'icon' => $section['icon']]) + ['items' => []];
            array_push($nav[$group]['items'], ...$items);
        }

        // Orden de Permissions::GROUPS y flag de grupo activo
        $ordered = [];
        foreach (array_keys(Permissions::GROUPS + $nav) as $group) {
            if (isset($nav[$group])) {
                $ordered[$group] = $nav[$group] + [
                    'active' => in_array(true, array_column($nav[$group]['items'], 'active'), true),
                    'off'    => !in_array(false, array_column($nav[$group]['items'], 'off'), true),
                ];
            }
        }
        return $ordered;
    }

    /**
     * Migas de pan para el path: Dashboard › Grupo › Sección. Devuelve [['label','url'], ...]
     * y 'leaf' = true si el path es exactamente la página de la sección (la vista no agrega
     * otro nivel); si es una subpágina (crear, editar, detalle), la vista agrega su título.
     *
     * @return array{crumbs: array<int,array{label:string,url:string}>, leaf: bool}
     */
    public static function breadcrumbs(string $path): array
    {
        $path = '/' . trim($path, '/');
        $crumbs = [];
        $leaf = true;
        foreach (self::visibleNav($path) as $group) {
            if (!$group['active']) {
                continue;
            }
            $crumbs[] = ['label' => 'Dashboard', 'url' => 'admin'];
            $items = $group['items'];
            if (count($items) > 1) {
                $crumbs[] = ['label' => $group['label'], 'url' => $items[0]['url']];
            }
            foreach ($items as $item) {
                if ($item['active']) {
                    $crumbs[] = ['label' => $item['label'], 'url' => $item['url']];
                    $leaf = $path === '/' . $item['url'];
                    break;
                }
            }
            break;
        }
        // "Usuarios › Usuarios": el grupo y la sección se llaman igual → queda uno solo
        $deduped = [];
        foreach ($crumbs as $c) {
            if ($deduped && end($deduped)['label'] === $c['label']) {
                array_pop($deduped);
            }
            $deduped[] = $c;
        }
        return ['crumbs' => $deduped, 'leaf' => $leaf];
    }

    /**
     * URL de entrada de una sección para el usuario actual: la de siempre o, si su rol
     * solo tiene algunas funciones y no la principal, la primera pantalla que sí puede ver.
     */
    public static function entryUrl(string $key): string
    {
        $url = Permissions::CATALOG[$key]['url'];
        if (empty(Permissions::FEATURES[$key]) || self::canRequest($key, 'GET', '/' . $url)) {
            return $url;
        }
        foreach (Permissions::FEATURES[$key] as $feature => $def) {
            if (!self::can($feature)) {
                continue;
            }
            foreach ($def['routes'] as [$method, $prefix]) {
                if ($method === 'GET' || $method === '*') {
                    return ltrim($prefix, '/');
                }
            }
        }
        return $url;
    }

    /** Path actual del request sin base path (para visibleNav / breadcrumbs). */
    public static function currentPath(): string
    {
        $raw = getenv('APP_BASE_PATH');
        $base = rtrim($raw !== false ? $raw : ($_ENV['APP_BASE_PATH'] ?? ''), '/');
        $uri = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0];
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . trim($uri, '/');
    }

    /** Roles que el usuario actual puede asignar a otros. */
    public static function assignableRoles(): array
    {
        $roles = Capsule::table('roles')->orderBy('is_system', 'desc')->orderBy('name')->pluck('name', 'slug')->all();
        if (!self::isRoot()) {
            unset($roles['admin']); // solo el root crea administradores
        }
        if (self::isRoot() && self::isMasterContext()) {
            $roles = [self::ROOT => 'Root (control total)'] + $roles;
        }
        return $roles;
    }
}
