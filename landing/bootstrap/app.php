<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/helpers.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Dependency Injection Container
$containerBuilder = new ContainerBuilder();

// Add definitions if needed
// $containerBuilder->addDefinitions([...]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Configurar base path (para hosting en subpath, ej: /landing)
$basePath = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/');
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

// ─── Eloquent ORM Setup ──────────────────────────────────────
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? '',
    'username'  => $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root',
    'password'  => $_SERVER['DB_PASS'] ?? $_ENV['DB_PASS'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// ─── Services ────────────────────────────────────────────────
$container->set(\App\Services\AuthService::class, function () {
    return new \App\Services\AuthService();
});

$container->set(\App\Middleware\AuthMiddleware::class, function ($c) {
    return new \App\Middleware\AuthMiddleware($c->get(\App\Services\AuthService::class));
});

$container->set(\App\Middleware\RoleMiddleware::class, function () {
    return new \App\Middleware\RoleMiddleware('admin');
});

// ─── Blade Setup ─────────────────────────────────────────────
$container->set('view', function () {
    $viewPaths = [__DIR__ . '/../resources/views'];
    $cachePath = __DIR__ . '/../storage/cache';
    
    $filesystem = new \Illuminate\Filesystem\Filesystem();
    $eventDispatcher = new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container());
    
    $engineResolver = new \Illuminate\View\Engines\EngineResolver();
    $bladeCompiler = new \Illuminate\View\Compilers\BladeCompiler($filesystem, $cachePath);
    
    $engineResolver->register('blade', function () use ($bladeCompiler) {
        return new \Illuminate\View\Engines\CompilerEngine($bladeCompiler);
    });
    
    $viewFinder = new \Illuminate\View\FileViewFinder($filesystem, $viewPaths);
    $viewFactory = new \Illuminate\View\Factory($engineResolver, $viewFinder, $eventDispatcher);
    
    return $viewFactory;
});

// Middleware
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Routes
require __DIR__ . '/../src/routes.php';

return $app;
