<?php

use App\Controllers\Front\LandingController;
use App\Controllers\Front\SitemapController;
use App\Controllers\Api\LinkApiController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DemoAdminController;
use App\Middleware\AuthMiddleware;
use App\Middleware\ReadOnlyMiddleware;
use App\Middleware\CsrfMiddleware;
use Slim\Routing\RouteCollectorProxy;

/** @var \Slim\App $app */

// Web Routes
$app->get('/', LandingController::class);
$app->get('/menu', \App\Controllers\Front\MenuController::class);
$app->get('/sitemap.xml', [SitemapController::class, 'sitemap']);
$app->get('/robots.txt',  [SitemapController::class, 'robots']);
$app->get('/healthz', \App\Controllers\Front\HealthController::class);

// API Routes
$app->group('/api', function (RouteCollectorProxy $group) {
    $group->get('/links', [LinkApiController::class, 'list']);
    $group->get('/links/{id}', [LinkApiController::class, 'detail']);
});

// Admin Protected Routes
$app->group('/admin', function (RouteCollectorProxy $group) {
    // Auth routes FIRST (before middleware that needs them)
    $group->get('/login', [AuthController::class, 'showLogin']);
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/logout', [AuthController::class, 'logout']);
    $group->get('/sso', [AuthController::class, 'sso']); // SSO superadmin -> sitio (token de un solo uso)

    $group->get('', DashboardController::class);
    $group->get('/', DashboardController::class);
    
    // Módulos (admin only)
    $group->group('/modules', function (RouteCollectorProxy $modGroup) {
        $modGroup->get('', [\App\Controllers\Admin\ModuleAdminController::class, 'index']);
        $modGroup->post('', [\App\Controllers\Admin\ModuleAdminController::class, 'update']);
    })->add(\App\Middleware\RoleMiddleware::class);

    // Links CRUD
    $group->get('/links', [\App\Controllers\Admin\LinkAdminController::class, 'index']);
    $group->get('/links/create', [\App\Controllers\Admin\LinkAdminController::class, 'create']);
    $group->post('/links/create', [\App\Controllers\Admin\LinkAdminController::class, 'store']);
    $group->get('/links/edit/{id}', [\App\Controllers\Admin\LinkAdminController::class, 'edit']);
    $group->post('/links/edit/{id}', [\App\Controllers\Admin\LinkAdminController::class, 'update']);
    $group->post('/links/delete/{id}', [\App\Controllers\Admin\LinkAdminController::class, 'delete']);
    
    // Locations CRUD
    $group->get('/locations', [\App\Controllers\Admin\LocationAdminController::class, 'index']);
    $group->get('/locations/create', [\App\Controllers\Admin\LocationAdminController::class, 'create']);
    $group->post('/locations/create', [\App\Controllers\Admin\LocationAdminController::class, 'store']);
    $group->get('/locations/edit/{id}', [\App\Controllers\Admin\LocationAdminController::class, 'edit']);
    $group->post('/locations/edit/{id}', [\App\Controllers\Admin\LocationAdminController::class, 'update']);
    $group->post('/locations/delete/{id}', [\App\Controllers\Admin\LocationAdminController::class, 'delete']);
    $group->post('/locations/reorder',  [\App\Controllers\Admin\LocationAdminController::class, 'reorder']);
    $group->post('/locations/display',  [\App\Controllers\Admin\LocationAdminController::class, 'updateDisplay']);
    $group->post('/locations/{id:[0-9]+}/toggle-mode', [\App\Controllers\Admin\LocationAdminController::class, 'toggleMode']);

    // UX Builder: orden de bloques, textos, componentes y botón de WhatsApp
    $group->get('/builder',  [\App\Controllers\Admin\BuilderAdminController::class, 'index']);
    $group->post('/builder', [\App\Controllers\Admin\BuilderAdminController::class, 'update']);

    // Users CRUD
    $group->group('/users', function (RouteCollectorProxy $userGroup) {
        $userGroup->get('', [\App\Controllers\Admin\UserAdminController::class, 'index']);
        $userGroup->get('/create', [\App\Controllers\Admin\UserAdminController::class, 'create']);
        $userGroup->post('/create', [\App\Controllers\Admin\UserAdminController::class, 'store']);
        $userGroup->get('/edit/{id}', [\App\Controllers\Admin\UserAdminController::class, 'edit']);
        $userGroup->post('/edit/{id}', [\App\Controllers\Admin\UserAdminController::class, 'update']);
        $userGroup->post('/delete/{id}', [\App\Controllers\Admin\UserAdminController::class, 'delete']);
    })->add(\App\Middleware\RoleMiddleware::class);
    
    // Settings
    $group->get('/settings', [\App\Controllers\Admin\SettingsAdminController::class, 'index']);
    $group->post('/settings', [\App\Controllers\Admin\SettingsAdminController::class, 'update']);

    // Favicon
    $group->get('/favicon', [\App\Controllers\Admin\FaviconAdminController::class, 'index']);
    $group->post('/favicon', [\App\Controllers\Admin\FaviconAdminController::class, 'upload']);
    $group->post('/favicon/delete', [\App\Controllers\Admin\FaviconAdminController::class, 'delete']);

    // SEO
    $group->get('/seo', [\App\Controllers\Admin\SeoAdminController::class, 'index']);
    $group->post('/seo', [\App\Controllers\Admin\SeoAdminController::class, 'update']);

    // FAQ
    $group->get('/seo/faq', [\App\Controllers\Admin\FaqAdminController::class, 'index']);
    $group->get('/seo/faq/create', [\App\Controllers\Admin\FaqAdminController::class, 'create']);
    $group->post('/seo/faq/create', [\App\Controllers\Admin\FaqAdminController::class, 'store']);
    $group->get('/seo/faq/edit/{id}', [\App\Controllers\Admin\FaqAdminController::class, 'edit']);
    $group->post('/seo/faq/edit/{id}', [\App\Controllers\Admin\FaqAdminController::class, 'update']);
    $group->post('/seo/faq/delete/{id}', [\App\Controllers\Admin\FaqAdminController::class, 'delete']);
    $group->post('/seo/faq/reorder', [\App\Controllers\Admin\FaqAdminController::class, 'reorder']);

    // Menú – Configuración
    $group->get('/menu/settings',  [\App\Controllers\Admin\MenuSettingsAdminController::class, 'index']);
    $group->post('/menu/settings', [\App\Controllers\Admin\MenuSettingsAdminController::class, 'update']);

    // Menú – Secciones
    $group->get('/menu/sections',              [\App\Controllers\Admin\MenuSectionAdminController::class, 'index']);
    $group->get('/menu/sections/create',       [\App\Controllers\Admin\MenuSectionAdminController::class, 'create']);
    $group->post('/menu/sections/create',      [\App\Controllers\Admin\MenuSectionAdminController::class, 'store']);
    $group->get('/menu/sections/edit/{id}',    [\App\Controllers\Admin\MenuSectionAdminController::class, 'edit']);
    $group->post('/menu/sections/edit/{id}',   [\App\Controllers\Admin\MenuSectionAdminController::class, 'update']);
    $group->post('/menu/sections/delete/{id}', [\App\Controllers\Admin\MenuSectionAdminController::class, 'delete']);
    $group->post('/menu/sections/reorder',     [\App\Controllers\Admin\MenuSectionAdminController::class, 'reorder']);

    // Menú – Ítems
    $group->get('/menu/items',              [\App\Controllers\Admin\MenuItemAdminController::class, 'index']);
    $group->get('/menu/items/create',       [\App\Controllers\Admin\MenuItemAdminController::class, 'create']);
    $group->post('/menu/items/create',      [\App\Controllers\Admin\MenuItemAdminController::class, 'store']);
    $group->get('/menu/items/edit/{id}',    [\App\Controllers\Admin\MenuItemAdminController::class, 'edit']);
    $group->post('/menu/items/edit/{id}',   [\App\Controllers\Admin\MenuItemAdminController::class, 'update']);
    $group->post('/menu/items/delete/{id}', [\App\Controllers\Admin\MenuItemAdminController::class, 'delete']);
    $group->post('/menu/items/reorder',     [\App\Controllers\Admin\MenuItemAdminController::class, 'reorder']);

    // Sistema: diagnóstico y reparación (superadmin, solo dominio master)
    $group->group('/system', function (RouteCollectorProxy $sys) {
        $sys->get('', [\App\Controllers\Admin\SystemAdminController::class, 'index']);
        $sys->post('/migrate', [\App\Controllers\Admin\SystemAdminController::class, 'migrate']);
        $sys->post('/rotate-keys', [\App\Controllers\Admin\SystemAdminController::class, 'rotateKeys']);
    })->add(\App\Middleware\MasterOnlyMiddleware::class);

    // Servidores remotos por SSH (superadmin, solo dominio master)
    $group->group('/servers', function (RouteCollectorProxy $srv) {
        $c = \App\Controllers\Admin\ServerAdminController::class;
        $srv->get('', [$c, 'index']);
        $srv->get('/create', [$c, 'create']);
        $srv->post('/create', [$c, 'store']);
        $srv->get('/edit/{id:[0-9]+}', [$c, 'edit']);
        $srv->post('/edit/{id:[0-9]+}', [$c, 'update']);
        $srv->post('/delete/{id:[0-9]+}', [$c, 'delete']);
        $srv->get('/{id:[0-9]+}', [$c, 'show']);
        $srv->post('/{id:[0-9]+}/fingerprint', [$c, 'fingerprint']);
        $srv->post('/{id:[0-9]+}/pin', [$c, 'pin']);
        $srv->post('/{id:[0-9]+}/health', [$c, 'health']);
        $srv->post('/{id:[0-9]+}/logs', [$c, 'logs']);
        $srv->post('/{id:[0-9]+}/provision', [$c, 'provision']);
    })->add(\App\Middleware\MasterOnlyMiddleware::class);

    // Gestor de Sitios (admin only)
    $group->group('/sites', function (RouteCollectorProxy $siteGroup) {
        $siteGroup->get('', [\App\Controllers\Admin\SiteAdminController::class, 'index']);
        $siteGroup->get('/create', [\App\Controllers\Admin\SiteAdminController::class, 'create']);
        $siteGroup->post('/create', [\App\Controllers\Admin\SiteAdminController::class, 'store']);
        $siteGroup->get('/edit/{id}', [\App\Controllers\Admin\SiteAdminController::class, 'edit']);
        $siteGroup->post('/edit/{id}', [\App\Controllers\Admin\SiteAdminController::class, 'update']);
        $siteGroup->post('/delete/{id}', [\App\Controllers\Admin\SiteAdminController::class, 'delete']);
        $siteGroup->post('/{id}/suspend', [\App\Controllers\Admin\SiteAdminController::class, 'suspend']);
        $siteGroup->post('/{id}/activate', [\App\Controllers\Admin\SiteAdminController::class, 'activate']);
        $siteGroup->post('/{id}/test', [\App\Controllers\Admin\SiteAdminController::class, 'testConnection']);
        $siteGroup->post('/{id}/create-db', [\App\Controllers\Admin\SiteAdminController::class, 'createDatabase']);
        $siteGroup->post('/{id}/initialize', [\App\Controllers\Admin\SiteAdminController::class, 'initialize']);
        $siteGroup->post('/{id}/verify-domain', [\App\Controllers\Admin\SiteAdminController::class, 'verifyDomain']);
        $siteGroup->post('/{id}/impersonate', [\App\Controllers\Admin\SiteAdminController::class, 'impersonate']);
    })->add(\App\Middleware\MasterOnlyMiddleware::class);
})
    ->add(\App\Middleware\CsrfMiddleware::class)
    ->add(ReadOnlyMiddleware::class)
    ->add(AuthMiddleware::class);

// Demo reset — requires auth but bypasses ReadOnlyMiddleware so
// the POST /admin/demo/reset works even when demo mode is ON.
$app->group('/admin/demo', function (RouteCollectorProxy $group) {
    $group->post('/reset', [DemoAdminController::class, 'reset']);
})
    ->add(CsrfMiddleware::class)
    ->add(AuthMiddleware::class);
