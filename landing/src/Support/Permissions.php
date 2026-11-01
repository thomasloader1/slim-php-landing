<?php

namespace App\Support;

/**
 * Catálogo único de secciones del admin: lo usan el sidebar, el bloqueo de
 * rutas (PermissionMiddleware) y la matriz de Roles y permisos.
 *
 * scope:
 *   - site:   asignable a roles (lo que el root decide que ve cada usuario).
 *   - root:   solo el root, en cualquier contexto (master o sitio vía SSO).
 *   - master: solo el root y solo en la master (gestor de sitios).
 * module: flag de settings que, apagado, oculta y bloquea la sección para todos.
 *
 * Una ruta nueva bajo /admin DEBE tener su prefijo acá: lo que no está en el
 * catálogo (ni en PUBLIC_PATHS) se bloquea.
 */
final class Permissions
{
    /** Rutas del grupo /admin que no requieren permiso. */
    public const PUBLIC_PATHS = ['/admin', '/admin/login', '/admin/logout', '/admin/sso'];

    public const CATALOG = [
        'modules'   => ['label' => 'Módulos',          'icon' => 'fa-puzzle-piece',        'url' => 'admin/modules',    'prefixes' => ['/admin/modules', '/admin/demo'], 'module' => null, 'scope' => 'site'],
        'builder'   => ['label' => 'UX Builder',       'icon' => 'fa-wand-magic-sparkles', 'url' => 'admin/builder',    'prefixes' => ['/admin/builder'],   'module' => null, 'scope' => 'site'],
        'links'     => ['label' => 'Enlaces',          'icon' => 'fa-link',                'url' => 'admin/links',      'prefixes' => ['/admin/links'],     'module' => 'module_links_enabled', 'scope' => 'site'],
        'locations' => ['label' => 'Ubicaciones',      'icon' => 'fa-map-location-dot',    'url' => 'admin/locations',  'prefixes' => ['/admin/locations'], 'module' => 'module_locations_enabled', 'scope' => 'site'],
        'users'     => ['label' => 'Usuarios',         'icon' => 'fa-users',               'url' => 'admin/users',      'prefixes' => ['/admin/users'],     'module' => null, 'scope' => 'site'],
        'roles'     => ['label' => 'Roles y permisos', 'icon' => 'fa-user-shield',         'url' => 'admin/roles',      'prefixes' => ['/admin/roles'],     'module' => null, 'scope' => 'root'],
        'settings'  => ['label' => 'Ajustes',          'icon' => 'fa-gears',               'url' => 'admin/settings',   'prefixes' => ['/admin/settings'],  'module' => null, 'scope' => 'site'],
        'seo'       => ['label' => 'SEO',              'icon' => 'fa-magnifying-glass',    'url' => 'admin/seo',        'prefixes' => ['/admin/seo'],       'module' => null, 'scope' => 'site'],
        'favicon'   => ['label' => 'Favicons',         'icon' => 'fa-icons',               'url' => 'admin/favicon',    'prefixes' => ['/admin/favicon'],   'module' => null, 'scope' => 'site'],
        'products'  => ['label' => 'Productos',        'icon' => 'fa-box-open',            'url' => 'admin/menu/items', 'prefixes' => ['/admin/menu'],      'module' => 'module_menu_enabled', 'scope' => 'site'],
        'sites'     => ['label' => 'Sitios',           'icon' => 'fa-server',              'url' => 'admin/sites',      'prefixes' => ['/admin/sites'],     'module' => 'module_site_manager_enabled', 'scope' => 'master'],
        'system'    => ['label' => 'Sistema',          'icon' => 'fa-stethoscope',         'url' => 'admin/system',     'prefixes' => ['/admin/system'],    'module' => null, 'scope' => 'master'],
        'servers'   => ['label' => 'Servidores',       'icon' => 'fa-terminal',            'url' => 'admin/servers',    'prefixes' => ['/admin/servers'],   'module' => null, 'scope' => 'master'],
    ];

    /** Flags de módulo que vienen apagados si no existen en settings. */
    public const MODULES_OFF_BY_DEFAULT = ['module_site_manager_enabled'];

    /** @return array<string,array> secciones que se pueden asignar a un rol */
    public static function assignable(): array
    {
        return array_filter(self::CATALOG, fn ($p) => $p['scope'] === 'site');
    }

    /**
     * Permiso requerido por un path del admin (sin base path): null si es
     * público, '' si no está en el catálogo (se bloquea). Gana el prefijo
     * más largo.
     */
    public static function forPath(string $path): ?string
    {
        $path = '/' . trim($path, '/');
        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return null;
        }

        $match = '';
        $length = 0;
        foreach (self::CATALOG as $key => $perm) {
            foreach ($perm['prefixes'] as $prefix) {
                if (($path === $prefix || str_starts_with($path, $prefix . '/')) && strlen($prefix) > $length) {
                    $match = $key;
                    $length = strlen($prefix);
                }
            }
        }
        return $match;
    }
}
