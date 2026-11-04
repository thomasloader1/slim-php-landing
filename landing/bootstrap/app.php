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

// Session cookie hardening (before session is started)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Dependency Injection Container
$containerBuilder = new ContainerBuilder();

// Add definitions if needed
// $containerBuilder->addDefinitions([...]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Configurar base path (para hosting en subpath, ej: /landing)
// getenv() lee el env del proceso Docker directamente; $_ENV como fallback para
// producción sin Docker (donde el valor viene del .env cargado por Dotenv).
$_rawBasePath = getenv('APP_BASE_PATH');
$basePath = rtrim($_rawBasePath !== false ? $_rawBasePath : ($_ENV['APP_BASE_PATH'] ?? ''), '/');
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

// ─── Eloquent ORM Setup ─────────────────────────────────────
$dbConfig = [
    'driver'    => 'mysql',
    'host'      => getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost'),
    'port'      => getenv('DB_PORT') ?: ($_SERVER['DB_PORT'] ?? $_ENV['DB_PORT'] ?? '3306'),
    'database'  => getenv('DB_NAME') ?: ($_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? ''),
    'username'  => getenv('DB_USER') ?: ($_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root'),
    'password'  => getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_SERVER['DB_PASS'] ?? $_ENV['DB_PASS'] ?? ''),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
];

// Conexión "master": en la master es su propia DB; en una copia de sitio hijo
// (public_html/<cliente>/ con SITE_ID) apunta a la DB master vía MASTER_DB_* para
// validar el login del root master y consumir tokens SSO sin tocar su propia DB.
$masterConfig = $dbConfig;
$masterDbName = getenv('MASTER_DB_NAME') !== false ? getenv('MASTER_DB_NAME') : ($_ENV['MASTER_DB_NAME'] ?? '');
if (trim((string) $masterDbName) !== '') {
    $masterConfig = array_merge($dbConfig, [
        'host'     => getenv('MASTER_DB_HOST') ?: ($_SERVER['MASTER_DB_HOST'] ?? $_ENV['MASTER_DB_HOST'] ?? $dbConfig['host']),
        'port'     => getenv('MASTER_DB_PORT') ?: ($_SERVER['MASTER_DB_PORT'] ?? $_ENV['MASTER_DB_PORT'] ?? $dbConfig['port']),
        'database' => trim((string) $masterDbName),
        'username' => getenv('MASTER_DB_USER') ?: ($_SERVER['MASTER_DB_USER'] ?? $_ENV['MASTER_DB_USER'] ?? $dbConfig['username']),
        'password' => getenv('MASTER_DB_PASS') !== false ? getenv('MASTER_DB_PASS') : ($_SERVER['MASTER_DB_PASS'] ?? $_ENV['MASTER_DB_PASS'] ?? ''),
    ]);
}

$capsule = new Capsule;
$capsule->addConnection($dbConfig);
$capsule->addConnection($masterConfig, 'master');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// ─── Services ────────────────────────────────────────────────
$container->set(\App\Services\AuthService::class, function () {
    return new \App\Services\AuthService();
});

$container->set(\App\Middleware\AuthMiddleware::class, function ($c) {
    return new \App\Middleware\AuthMiddleware($c->get(\App\Services\AuthService::class));
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
// Slim ejecuta los middlewares en orden inverso al add() (LIFO):
// RobotsHeader -> SecurityHeaders -> TenantResolver -> SiteStatus -> rutas.
// SiteStatus debe correr DESPUÉS de resolver el tenant para leer los settings de su DB.
$app->add(new \App\Middleware\SiteStatusMiddleware());
$app->add(new \App\Middleware\TenantResolverMiddleware());
$app->add(new \App\Middleware\SecurityHeadersMiddleware());
// Más externo: marca noindex todo lo que no sea / o /shop (incluye 404 del tenant resolver)
$app->add(new \App\Middleware\RobotsHeaderMiddleware());
$app->addRoutingMiddleware();

// Error handling: pantalla técnica en dev, amigable en prod,
// logs ultra detallados SIEMPRE
$_isDev = (getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'production')) === 'development';
$errorMiddleware = $app->addErrorMiddleware($_isDev, true, true);
$errorMiddleware->setDefaultErrorHandler(
    new \App\Handlers\DetailedErrorHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $container->get('view')
    )
);

// Routes
require __DIR__ . '/../src/routes.php';

return $app;
