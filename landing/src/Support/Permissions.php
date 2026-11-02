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
 * module: flag de settings (Módulos, solo root) que, apagado, oculta y bloquea la
 *         sección para todos menos el root, que la ve marcada "Off".
 * requires: flag adicional que también tiene que estar encendido (Pedidos necesita el Shop).
 *
 * Una ruta nueva bajo /admin DEBE tener su prefijo acá: lo que no está en el
 * catálogo (ni en PUBLIC_PATHS) se bloquea.
 */
final class Permissions
{
    /** Rutas del grupo /admin que no requieren permiso. */
    public const PUBLIC_PATHS = ['/admin', '/admin/login', '/admin/logout', '/admin/sso'];

    /**
     * Grupos del sidebar, en orden. Cada sección del CATALOG declara su 'group'.
     * Un grupo con una sola sección visible se muestra como link directo.
     */
    public const GROUPS = [
        'content'  => ['label' => 'Contenido',  'icon' => 'fa-pen-ruler'],
        'shop'     => ['label' => 'Shop',       'icon' => 'fa-store'],
        'users'    => ['label' => 'Usuarios',   'icon' => 'fa-users'],
        'site'     => ['label' => 'Sitio',      'icon' => 'fa-sliders'],
        'platform' => ['label' => 'Plataforma', 'icon' => 'fa-layer-group'],
        'root'     => ['label' => 'Root',       'icon' => 'fa-crown'],       // herramientas del root, al pie del sidebar
    ];

    /**
     * children: sub-links de navegación dentro de una sección (mismo permiso).
     */
    public const CATALOG = [
        'builder'     => ['label' => 'UX Builder',       'icon' => 'fa-wand-magic-sparkles', 'url' => 'admin/builder',     'prefixes' => ['/admin/builder'],     'module' => 'module_builder_enabled', 'scope' => 'site', 'group' => 'content'],
        'links'       => ['label' => 'Enlaces',          'icon' => 'fa-link',                'url' => 'admin/links',       'prefixes' => ['/admin/links'],       'module' => 'module_links_enabled', 'scope' => 'site', 'group' => 'content'],
        'locations'   => ['label' => 'Ubicaciones',      'icon' => 'fa-map-location-dot',    'url' => 'admin/locations',   'prefixes' => ['/admin/locations'],   'module' => 'module_locations_enabled', 'scope' => 'site', 'group' => 'content'],
        'products'    => ['label' => 'Productos',        'icon' => 'fa-box-open',            'url' => 'admin/menu/items',  'prefixes' => ['/admin/menu'],        'module' => 'module_menu_enabled', 'scope' => 'site', 'group' => 'shop',
            'children' => [
                ['label' => 'Productos',     'icon' => 'fa-box-open',    'url' => 'admin/menu/items',    'prefix' => '/admin/menu/items',    'feature' => 'products.view'],
                ['label' => 'Categorías',    'icon' => 'fa-tags',        'url' => 'admin/menu/sections', 'prefix' => '/admin/menu/sections', 'feature' => 'products.sections'],
                ['label' => 'Configuración', 'icon' => 'fa-gear',        'url' => 'admin/menu/settings', 'prefix' => '/admin/menu/settings', 'feature' => 'products.settings'],
            ]],
        'orders'      => ['label' => 'Pedidos',          'icon' => 'fa-receipt',             'url' => 'admin/shop/orders', 'prefixes' => ['/admin/shop'],        'module' => 'module_orders_enabled', 'requires' => 'module_menu_enabled', 'scope' => 'site', 'group' => 'shop'],
        'users'       => ['label' => 'Usuarios',         'icon' => 'fa-user',                'url' => 'admin/users',       'prefixes' => ['/admin/users'],       'module' => 'module_users_enabled', 'scope' => 'site', 'group' => 'users'],
        'roles'       => ['label' => 'Roles y permisos', 'icon' => 'fa-user-shield',         'url' => 'admin/roles',       'prefixes' => ['/admin/roles'],       'module' => null, 'scope' => 'root', 'group' => 'users'],
        'settings'    => ['label' => 'Ajustes',          'icon' => 'fa-gears',               'url' => 'admin/settings',    'prefixes' => ['/admin/settings'],    'module' => 'module_settings_enabled', 'scope' => 'site', 'group' => 'site'],
        'seo'         => ['label' => 'SEO',              'icon' => 'fa-magnifying-glass',    'url' => 'admin/seo',         'prefixes' => ['/admin/seo'],         'module' => 'module_seo_enabled', 'scope' => 'site', 'group' => 'site'],
        'favicon'     => ['label' => 'Favicons',         'icon' => 'fa-icons',               'url' => 'admin/favicon',     'prefixes' => ['/admin/favicon'],     'module' => 'module_favicon_enabled', 'scope' => 'site', 'group' => 'site'],
        'modules'     => ['label' => 'Módulos',          'icon' => 'fa-puzzle-piece',        'url' => 'admin/modules',     'prefixes' => ['/admin/modules', '/admin/demo'], 'module' => null, 'scope' => 'root', 'group' => 'root'],
        'sites'       => ['label' => 'Sitios',           'icon' => 'fa-globe',               'url' => 'admin/sites',       'prefixes' => ['/admin/sites'],       'module' => 'module_site_manager_enabled', 'scope' => 'master', 'group' => 'platform'],
        'directories' => ['label' => 'Directorios',      'icon' => 'fa-folder-tree',         'url' => 'admin/directories', 'prefixes' => ['/admin/directories'], 'module' => null, 'scope' => 'master', 'group' => 'platform'],
        'system'      => ['label' => 'Sistema',          'icon' => 'fa-stethoscope',         'url' => 'admin/system',      'prefixes' => ['/admin/system'],      'module' => null, 'scope' => 'master', 'group' => 'platform'],
    ];

    /**
     * Funciones de cada sección, para dar permisos parciales a un rol
     * ("links" en role_permissions = todas; "links.view" = solo esa).
     *
     * routes: [método, prefijo] — método '*' = cualquiera. Gana el prefijo más
     * largo y, a igual largo, el método exacto. Un pedido de una sección con
     * funciones que no matchea ninguna ruta exige la sección completa.
     * implies: funciones que se otorgan junto con esta (editar/eliminar requieren ver).
     */
    public const FEATURES = [
        'links' => [
            'links.view'   => ['label' => 'Ver',            'routes' => [['GET', '/admin/links']]],
            'links.edit'   => ['label' => 'Crear y editar', 'routes' => [['*', '/admin/links/create'], ['*', '/admin/links/edit'], ['POST', '/admin/links/reorder']], 'implies' => ['links.view']],
            'links.delete' => ['label' => 'Eliminar',       'routes' => [['POST', '/admin/links/delete']], 'implies' => ['links.view']],
        ],
        'locations' => [
            'locations.view'   => ['label' => 'Ver',            'routes' => [['GET', '/admin/locations']]],
            'locations.edit'   => ['label' => 'Crear y editar', 'routes' => [['*', '/admin/locations/create'], ['*', '/admin/locations/edit'], ['POST', '/admin/locations']], 'implies' => ['locations.view']],
            'locations.delete' => ['label' => 'Eliminar',       'routes' => [['POST', '/admin/locations/delete']], 'implies' => ['locations.view']],
        ],
        'products' => [
            'products.view'     => ['label' => 'Ver productos',             'routes' => [['GET', '/admin/menu/items']]],
            'products.edit'     => ['label' => 'Crear y editar productos',  'routes' => [['*', '/admin/menu/items/create'], ['*', '/admin/menu/items/edit'], ['POST', '/admin/menu/items/reorder']], 'implies' => ['products.view']],
            'products.delete'   => ['label' => 'Eliminar productos',        'routes' => [['POST', '/admin/menu/items/delete']], 'implies' => ['products.view']],
            'products.sections' => ['label' => 'Categorías',                'routes' => [['*', '/admin/menu/sections']]],
            'products.import'   => ['label' => 'Importar / exportar Excel', 'routes' => [['*', '/admin/menu/items/import'], ['GET', '/admin/menu/items/template'], ['GET', '/admin/menu/items/export']], 'implies' => ['products.view']],
            'products.prices'   => ['label' => 'Ajuste masivo de precios',  'routes' => [['POST', '/admin/menu/items/adjust-prices']], 'implies' => ['products.view']],
            'products.settings' => ['label' => 'Configuración del Shop',    'routes' => [['*', '/admin/menu/settings']]],
        ],
        'orders' => [
            'orders.view'   => ['label' => 'Ver pedidos',        'routes' => [['GET', '/admin/shop/orders']]],
            'orders.manage' => ['label' => 'Aceptar / rechazar', 'routes' => [['POST', '/admin/shop/orders']], 'implies' => ['orders.view']],
        ],
        'users' => [
            'users.view'   => ['label' => 'Ver',            'routes' => [['GET', '/admin/users']]],
            'users.edit'   => ['label' => 'Crear y editar', 'routes' => [['*', '/admin/users/create'], ['*', '/admin/users/edit']], 'implies' => ['users.view']],
            'users.delete' => ['label' => 'Eliminar',       'routes' => [['POST', '/admin/users/delete']], 'implies' => ['users.view']],
        ],
        'seo' => [
            'seo.basic' => ['label' => 'SEO básico',           'routes' => [['*', '/admin/seo']]],
            'seo.faq'   => ['label' => 'Preguntas frecuentes', 'routes' => [['*', '/admin/seo/faq']]],
        ],
    ];

    /** Sección a la que pertenece una clave ("links.edit" → "links", "links" → "links"). */
    public static function sectionOf(string $key): string
    {
        return explode('.', $key, 2)[0];
    }

    /**
     * Función requerida para un pedido dentro de una sección con funciones.
     * null = la sección no tiene funciones, o ninguna ruta coincide (se exige la sección completa).
     */
    public static function featureFor(string $section, string $method, string $path): ?string
    {
        $path = '/' . trim($path, '/');
        $method = strtoupper($method);
        $best = null;
        $bestScore = -1;
        foreach (self::FEATURES[$section] ?? [] as $feature => $def) {
            foreach ($def['routes'] as [$m, $prefix]) {
                if ($m !== '*' && $m !== $method) {
                    continue;
                }
                if ($path !== $prefix && !str_starts_with($path, $prefix . '/')) {
                    continue;
                }
                $score = strlen($prefix) * 2 + ($m === '*' ? 0 : 1);
                if ($score > $bestScore) {
                    $best = $feature;
                    $bestScore = $score;
                }
            }
        }
        return $best;
    }

    /**
     * Normaliza lo que llega del formulario de roles: solo claves asignables;
     * si una sección tiene todas sus funciones tildadas se guarda la sección
     * entera ("Todas"), y las funciones implícitas se agregan.
     *
     * @return string[]
     */
    public static function normalizeGrants(array $keys): array
    {
        $keys = array_values(array_unique(array_map('strval', $keys)));
        $out = [];
        foreach (self::assignable() as $section => $_) {
            $features = self::FEATURES[$section] ?? [];
            if (in_array($section, $keys, true)) {
                $out[] = $section;
                continue;
            }
            $picked = array_values(array_intersect(array_keys($features), $keys));
            foreach ($picked as $f) {
                array_push($picked, ...($features[$f]['implies'] ?? []));
            }
            $picked = array_values(array_unique($picked));
            if ($features && count($picked) === count($features)) {
                $out[] = $section;
            } else {
                array_push($out, ...$picked);
            }
        }
        return $out;
    }

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
