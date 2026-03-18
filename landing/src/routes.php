<?php

use App\Controllers\Front\LandingController;
use App\Controllers\Front\SitemapController;
use App\Controllers\Api\LinkApiController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

/** @var \Slim\App $app */

// Web Routes
$app->get('/', LandingController::class);
$app->get('/menu', \App\Controllers\Front\MenuController::class);
$app->get('/sitemap.xml', [SitemapController::class, 'sitemap']);
$app->get('/robots.txt',  [SitemapController::class, 'robots']);

// API Routes
$app->group('/api', function (RouteCollectorProxy $group) {
    $group->get('/links', [LinkApiController::class, 'list']);
    $group->get('/links/{id}', [LinkApiController::class, 'detail']);
});

// Admin Auth Routes
$app->get('/admin/login', [AuthController::class, 'showLogin']);
$app->post('/admin/login', [AuthController::class, 'login']);
$app->post('/admin/logout', [AuthController::class, 'logout']);

// Admin Protected Routes
$app->group('/admin', function (RouteCollectorProxy $group) {
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
    $group->post('/locations/reorder', [\App\Controllers\Admin\LocationAdminController::class, 'reorder']);

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
})->add(AuthMiddleware::class);
