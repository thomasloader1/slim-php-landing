<?php

use App\Controllers\Front\LandingController;
use App\Controllers\Api\LinkApiController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Middleware\AuthMiddleware;
use Slim\Routing\RouteCollectorProxy;

/** @var \Slim\App $app */

// Web Routes
$app->get('/', LandingController::class);

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
    
    // Links CRUD
    $group->get('/links', [\App\Controllers\Admin\LinkAdminController::class, 'index']);
    $group->get('/links/create', [\App\Controllers\Admin\LinkAdminController::class, 'create']);
    $group->post('/links/create', [\App\Controllers\Admin\LinkAdminController::class, 'store']);
    $group->get('/links/edit/{id}', [\App\Controllers\Admin\LinkAdminController::class, 'edit']);
    $group->post('/links/edit/{id}', [\App\Controllers\Admin\LinkAdminController::class, 'update']);
    $group->post('/links/delete/{id}', [\App\Controllers\Admin\LinkAdminController::class, 'delete']);
    
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
})->add(AuthMiddleware::class);
