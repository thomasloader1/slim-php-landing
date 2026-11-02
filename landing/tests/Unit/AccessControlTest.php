<?php

namespace Tests\Unit;

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\RoleAdminController;
use App\Middleware\PermissionMiddleware;
use App\Models\Role;
use App\Services\AccessService;
use App\Services\MigrationService;
use App\Support\Permissions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

/**
 * Roles y permisos: qué ve cada usuario y que las rutas se bloqueen de verdad.
 */
class AccessControlTest extends TestCase
{
    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            (new MigrationService())->migrate(Capsule::connection(), 'site');
            self::$migrated = true;
        }
        Capsule::connection()->beginTransaction();
        $_SESSION = [];
        AccessService::reset();
    }

    protected function tearDown(): void
    {
        Capsule::connection()->rollBack();
        $_SESSION = [];
        AccessService::reset();
        parent::tearDown();
    }

    private function as(string $role, string $context = 'master', bool $sso = false): void
    {
        $_SESSION = ['user_id' => 1, 'user_role' => $role, 'context' => $context];
        if ($sso) {
            $_SESSION['impersonated_by'] = 'superadmin';
        }
        AccessService::reset();
    }

    private function setModule(string $flag, string $value): void
    {
        Capsule::table('settings')->updateOrInsert(['setting_key' => $flag], ['setting_value' => $value]);
        AccessService::reset();
    }

    private function hit(string $path, array $headers = []): array
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', $path);
        foreach ($headers as $k => $v) {
            $request = $request->withHeader($k, $v);
        }
        $handler = new class implements RequestHandlerInterface {
            public bool $called = false;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return new \Slim\Psr7\Response(200);
            }
        };
        $response = (new PermissionMiddleware())->process($request, $handler);
        return [$handler->called, $response];
    }

    // ── Catálogo ─────────────────────────────────────────────────

    /** @test */
    public function paths_map_to_permissions_by_longest_prefix(): void
    {
        $this->assertNull(Permissions::forPath('/admin'));
        $this->assertNull(Permissions::forPath('/admin/'));
        $this->assertNull(Permissions::forPath('/admin/login'));
        $this->assertSame('products', Permissions::forPath('/admin/menu/items/import/confirm'));
        $this->assertSame('seo', Permissions::forPath('/admin/seo/faq/create'));
        $this->assertSame('modules', Permissions::forPath('/admin/demo/reset'));
        $this->assertSame('', Permissions::forPath('/admin/linksX'), 'el prefijo respeta el límite de segmento');
        $this->assertSame('', Permissions::forPath('/admin/nueva-seccion'), 'lo no registrado se bloquea');
    }

    /** @test */
    public function every_admin_route_is_registered_in_the_catalog(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2) . '/src/routes.php');
        $admin = substr($routes, strpos($routes, "\$app->group('/admin'"));
        // Solo rutas de primer nivel del grupo /admin ($group->...); los subgrupos heredan su prefijo
        preg_match_all("#\\\$group->(?:get|post|group)\\('/([a-z][a-z0-9-]*)#", $admin, $m);
        $this->assertGreaterThan(10, count(array_unique($m[1])), 'el test no encontró las rutas');

        foreach (array_unique($m[1]) as $segment) {
            if (in_array('/admin/' . $segment, Permissions::PUBLIC_PATHS, true) || in_array($segment, ['admin', 'reset'], true)) {
                continue;
            }
            $this->assertNotSame('', Permissions::forPath('/admin/' . $segment), "Falta /admin/{$segment} en Permissions::CATALOG");
        }
    }

    // ── AccessService ────────────────────────────────────────────

    /** @test */
    public function default_editor_sees_content_but_not_users_modules_or_master_tools(): void
    {
        $this->as('editor');
        foreach (['builder', 'links', 'locations', 'products', 'seo', 'favicon'] as $k) {
            $this->assertTrue(AccessService::can($k), $k);
        }
        foreach (['users', 'modules', 'settings', 'roles', 'sites', 'system', 'directories'] as $k) {
            $this->assertFalse(AccessService::can($k), $k);
        }
    }

    /** @test */
    public function nav_groups_related_sections_and_marks_the_active_item(): void
    {
        $this->as('root');
        $nav = AccessService::visibleNav('/admin/menu/sections/edit/3');

        $this->assertSame(['content', 'shop', 'users', 'site', 'platform', 'root'], array_keys($nav));
        $this->assertSame(['modules'], array_column($nav['root']['items'], 'key'), 'Módulos va solo, fuera de Sitio');
        $this->assertNotContains('modules', array_column($nav['site']['items'], 'key'));
        $this->assertSame(['Productos', 'Categorías', 'Configuración', 'Pedidos'], array_column($nav['shop']['items'], 'label'));
        $this->assertSame(['users', 'roles'], array_column($nav['users']['items'], 'key'));
        $this->assertContains('directories', array_column($nav['platform']['items'], 'key'));
        $this->assertNotContains('servers', array_keys(Permissions::CATALOG));

        $this->assertTrue($nav['shop']['active']);
        $this->assertSame(['Categorías'], array_column(array_filter($nav['shop']['items'], fn ($i) => $i['active']), 'label'));
        $this->assertFalse($nav['users']['active']);

        // Rutas de Productos sin sub-link propio (import) caen en "Productos"
        $nav = AccessService::visibleNav('/admin/menu/items/import');
        $this->assertSame(['Productos'], array_column(array_filter($nav['shop']['items'], fn ($i) => $i['active']), 'label'));
    }

    /** @test */
    public function every_route_of_a_section_with_features_maps_to_a_feature(): void
    {
        // Rutas de routes.php con su método y path completo (resolviendo grupos anidados)
        $src = file_get_contents(dirname(__DIR__, 2) . '/src/routes.php');
        $prefixes = ['group' => '/admin'];
        preg_match_all('#\$(\w+)->group\(\'([^\']*)\',\s*function\s*\(RouteCollectorProxy\s+\$(\w+)\)#', $src, $g, PREG_SET_ORDER);
        foreach ($g as [, $parent, $prefix, $child]) {
            $prefixes[$child] ??= ($prefixes[$parent] ?? '') . $prefix; // /admin/demo reusa \$group: vale el primero
        }
        preg_match_all('#\$(\w+)->(get|post)\(\'([^\']*)\'#', $src, $m, PREG_SET_ORDER);
        $checked = 0;
        foreach ($m as [, $var, $method, $path]) {
            if (!isset($prefixes[$var])) {
                continue; // rutas públicas ($app)
            }
            $full = preg_replace('#\{[^}]+\}#', '1', $prefixes[$var] . $path);
            $section = Permissions::forPath($full);
            if (!$section || empty(Permissions::FEATURES[$section])) {
                continue;
            }
            $checked++;
            $this->assertNotNull(
                Permissions::featureFor($section, strtoupper($method), $full),
                strtoupper($method) . " {$full} no está asignada a ninguna función de '{$section}'"
            );
        }
        $this->assertGreaterThan(30, $checked, 'el test no encontró las rutas');
    }

    /** @test */
    public function partial_role_only_reaches_its_features(): void
    {
        $role = Role::create(['slug' => 'catalogo-test', 'name' => 'Catálogo Test', 'is_system' => 0]);
        $role->syncPermissions(['products.view', 'products.sections']);
        $this->as('catalogo-test');

        $this->assertTrue(AccessService::can('products'));
        $this->assertTrue(AccessService::canRequest('products', 'GET', '/admin/menu/items'));
        $this->assertTrue(AccessService::canRequest('products', 'POST', '/admin/menu/sections/create'));
        $this->assertFalse(AccessService::canRequest('products', 'POST', '/admin/menu/items/delete/3'));
        $this->assertFalse(AccessService::canRequest('products', 'GET', '/admin/menu/items/import'));
        $this->assertFalse(AccessService::canRequest('products', 'POST', '/admin/menu/items/adjust-prices'));
        $this->assertFalse(AccessService::canRequest('products', 'GET', '/admin/menu/settings'));

        [$called] = $this->hit('/admin/menu/settings');
        $this->assertFalse($called, 'el middleware bloquea la función no otorgada');
        [$called] = $this->hit('/admin/menu/sections');
        $this->assertTrue($called);

        $nav = AccessService::visibleNav('/admin/menu/items');
        $this->assertSame(['Productos', 'Categorías'], array_column($nav['shop']['items'], 'label'), 'sin Configuración ni Pedidos');
    }

    /** @test */
    public function edit_implies_view_and_full_section_grants_everything(): void
    {
        $role = Role::create(['slug' => 'editor-links-test', 'name' => 'Links Test', 'is_system' => 0]);
        $role->syncPermissions(['links.edit', 'orders']);
        $this->as('editor-links-test');

        $this->assertTrue(AccessService::canRequest('links', 'GET', '/admin/links'), 'editar incluye ver');
        $this->assertTrue(AccessService::canRequest('links', 'POST', '/admin/links/edit/2'));
        $this->assertFalse(AccessService::canRequest('links', 'POST', '/admin/links/delete/2'));
        $this->assertTrue(AccessService::canRequest('orders', 'POST', '/admin/shop/orders/7/accept'), 'sección completa = todas');
    }

    /** @test */
    public function grants_are_normalized_when_saving_a_role(): void
    {
        $this->assertSame(['links'], Permissions::normalizeGrants(['links.view', 'links.edit', 'links.delete']), 'todas → sección');
        $this->assertSame(['links.delete', 'links.view'], Permissions::normalizeGrants(['links.delete']), 'eliminar agrega ver');
        $this->assertSame(['builder'], Permissions::normalizeGrants(['builder', 'roles', 'modules', 'system', 'basura.x']), 'solo asignables');
    }

    /** @test */
    public function nav_entry_goes_to_the_first_allowed_screen(): void
    {
        $role = Role::create(['slug' => 'faq-test', 'name' => 'FAQ Test', 'is_system' => 0]);
        $role->syncPermissions(['seo.faq']);
        $this->as('faq-test');
        $this->assertSame('admin/seo/faq', AccessService::entryUrl('seo'));

        $this->as('root');
        $this->assertSame('admin/seo', AccessService::entryUrl('seo'));
    }

    /** @test */
    public function breadcrumbs_follow_group_and_section(): void
    {
        $this->as('root');
        $labels = fn (string $path) => array_column(AccessService::breadcrumbs($path)['crumbs'], 'label');

        $this->assertSame(['Dashboard', 'Shop', 'Categorías'], $labels('/admin/menu/sections'));
        $this->assertTrue(AccessService::breadcrumbs('/admin/menu/sections')['leaf']);

        $edit = AccessService::breadcrumbs('/admin/menu/items/edit/4');
        $this->assertSame(['Dashboard', 'Shop', 'Productos'], array_column($edit['crumbs'], 'label'));
        $this->assertSame('admin/menu/items', end($edit['crumbs'])['url'], 'volver al listado');
        $this->assertFalse($edit['leaf'], 'la vista agrega su título como último nivel');

        $this->assertSame(['Dashboard', 'Usuarios'], $labels('/admin/users/create'), 'grupo y sección con el mismo nombre no se repiten');
        $this->assertSame([], $labels('/admin'), 'el dashboard no lleva migas');
    }

    /** @test */
    public function editor_nav_hides_platform_and_role_management(): void
    {
        $this->as('editor');
        $nav = AccessService::visibleNav('/admin/links');

        $this->assertArrayNotHasKey('platform', $nav);
        $this->assertArrayNotHasKey('root', $nav, 'Módulos es solo del root');
        $this->assertArrayNotHasKey('users', $nav, 'el editor por defecto no gestiona usuarios');
        $this->assertTrue($nav['content']['active']);
    }

    /** @test */
    public function root_sees_everything_in_master_but_not_master_tools_inside_a_site(): void
    {
        $this->as('root');
        $this->assertTrue(AccessService::isRoot());
        $this->assertTrue(AccessService::can('system'));
        $this->assertTrue(AccessService::can('roles'));

        $this->as('admin', 'site:5', true); // root de la master entrando por SSO
        $this->assertTrue(AccessService::isRoot());
        $this->assertTrue(AccessService::can('roles'));
        $this->assertFalse(AccessService::can('system'), 'herramientas de la master no existen en un sitio');

        $this->as('root', 'site:5'); // root propio de un sitio (creado por install.sql en su base)
        $this->assertTrue(AccessService::isRoot());
        $this->assertFalse(AccessService::can('sites'), 'pero no ve las herramientas de la master');
    }

    /** @test */
    public function master_admin_acts_as_root_only_while_the_database_has_no_root(): void
    {
        // Base master todavía sin migrar a roles: sin root, el admin puede entrar a Sistema
        Capsule::table('users')->where('role', 'root')->update(['role' => 'admin']);
        $this->as('admin');
        $this->assertTrue(AccessService::isRoot());
        $this->assertTrue(AccessService::can('system'));

        $this->as('admin', 'site:5');
        $this->assertFalse(AccessService::isRoot(), 'en un sitio no aplica');

        Capsule::table('users')->insert(['name' => 'R', 'email' => 'root-fallback@test.local', 'password_hash' => 'x', 'role' => 'root']);
        $this->as('admin');
        $this->assertFalse(AccessService::isRoot(), 'con un root existente, el admin vuelve a ser admin');
    }

    /** @test */
    public function a_disabled_module_is_hidden_for_users_but_root_sees_it_marked_off(): void
    {
        $this->setModule('module_links_enabled', '0');

        $this->as('admin', 'site:5');
        $this->assertFalse(AccessService::can('links'));
        $this->assertArrayNotHasKey('links', AccessService::visibleSections());
        [$called] = $this->hit('/admin/links');
        $this->assertFalse($called, 'la ruta también se bloquea');

        $this->as('root');
        $this->assertTrue(AccessService::can('links'));
        $this->assertTrue(AccessService::isOff('links'));
        $items = array_column(AccessService::visibleNav()['content']['items'], 'off', 'key');
        $this->assertTrue($items['links']);
        $this->assertFalse($items['builder']);
    }

    /** @test */
    public function orders_needs_the_shop_catalog_module(): void
    {
        $this->setModule('module_menu_enabled', '0');
        $this->assertTrue(AccessService::isOff('orders'));

        $this->setModule('module_menu_enabled', '1');
        $this->setModule('module_orders_enabled', '0');
        $this->assertFalse(AccessService::isOff('products'));
        $this->assertTrue(AccessService::isOff('orders'));
    }

    /** @test */
    public function modules_page_is_root_only(): void
    {
        $this->as('admin', 'site:5');
        $this->assertFalse(AccessService::can('modules'));
        $this->as('admin', 'site:5', true); // root por SSO
        $this->assertTrue(AccessService::can('modules'));
    }

    /** @test */
    public function custom_role_only_sees_its_permissions_and_changes_apply_immediately(): void
    {
        $role = Role::create(['slug' => 'vendedor-test', 'name' => 'Vendedor Test', 'is_system' => 0]);
        $role->syncPermissions(['products']);

        $this->as('vendedor-test');
        $this->assertSame(['products'], array_keys(AccessService::visibleSections()));

        $role->syncPermissions(['products', 'links']);
        AccessService::reset(); // lo hace AuthMiddleware en cada request
        $this->assertTrue(AccessService::can('links'));
    }

    /** @test */
    public function non_root_cannot_assign_admin_or_root(): void
    {
        $this->as('admin');
        $this->assertArrayNotHasKey('admin', AccessService::assignableRoles());
        $this->assertArrayNotHasKey('root', AccessService::assignableRoles());

        $this->as('root');
        $this->assertArrayHasKey('admin', AccessService::assignableRoles());
        $this->assertArrayHasKey('root', AccessService::assignableRoles());
    }

    // ── PermissionMiddleware ─────────────────────────────────────

    /** @test */
    public function middleware_blocks_by_url_redirecting_or_403_for_fetch(): void
    {
        $this->as('editor');

        [$called, $response] = $this->hit('/admin/users');
        $this->assertFalse($called);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('No tenés acceso a esa sección.', $_SESSION['flash_error']);

        [$called, $response] = $this->hit('/admin/users/create', ['Accept' => 'application/json']);
        $this->assertFalse($called);
        $this->assertSame(403, $response->getStatusCode());

        [$called] = $this->hit('/admin/links/reorder');
        $this->assertTrue($called);

        [$called] = $this->hit('/admin');
        $this->assertTrue($called, 'el dashboard siempre es accesible');
    }

    // ── SSO y helpers ────────────────────────────────────────────

    /** @test */
    public function sso_next_only_allows_internal_admin_paths(): void
    {
        $this->assertSame('admin/roles', AuthController::safeNext('admin/roles'));
        $this->assertSame('admin/roles', AuthController::safeNext('/admin/roles/'));
        foreach (['https://evil.com', '//evil.com/admin', 'admin/../x', 'admin/roles?x=1', 'javascript:alert(1)', ''] as $bad) {
            $this->assertSame('admin', AuthController::safeNext($bad), $bad);
        }
    }

    /** @test */
    public function role_form_only_keeps_assignable_permissions(): void
    {
        $this->assertSame(['links', 'products'], RoleAdminController::filterPermissions(['products', 'roles', 'sites', 'links', 'x']));
        $this->assertSame('vendedor-sucursal-norte', RoleAdminController::slugify('  Vendedor Sucursal Ñorte '));
    }
}
