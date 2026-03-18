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
// getenv() lee el env del proceso Docker directamente; $_ENV como fallback para
// producción sin Docker (donde el valor viene del .env cargado por Dotenv).
$_rawBasePath = getenv('APP_BASE_PATH');
$basePath = rtrim($_rawBasePath !== false ? $_rawBasePath : ($_ENV['APP_BASE_PATH'] ?? ''), '/');
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

// ─── Eloquent ORM Setup (con auto-repair) ───────────────────
$dbConfig = [
    'driver'    => 'mysql',
    'host'      => $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? '',
    'username'  => $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root',
    'password'  => $_SERVER['DB_PASS'] ?? $_ENV['DB_PASS'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
];

$capsule = new Capsule;
$capsule->addConnection($dbConfig);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Health check — verificar que las tablas críticas existen
$needsRepair = false;
try {
    Capsule::select('SELECT 1 FROM settings LIMIT 1');
    Capsule::select('SELECT 1 FROM locations LIMIT 1');
} catch (\Exception $e) {
    $needsRepair = true;
}

if ($needsRepair) {
    $envFile = __DIR__ . '/../.env';

    // Sin .env o sin nombre de DB → redirigir al wizard de instalación
    if (!file_exists($envFile) || empty($dbConfig['database'])) {
        header('Location: ' . $basePath . '/install.php');
        exit;
    }

    // Con .env → auto-repair completo (schema + seed + módulos + migraciones)
    _autoRepairDatabase();

    // Re-boot Eloquent tras el repair
    $capsule = new Capsule;
    $capsule->addConnection($dbConfig);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

// Verificar migraciones pendientes (incluso si las tablas existen)
_applyPendingMigrations();

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

// ─── Helpers de auto-repair ─────────────────────────────────

/**
 * Lee las credenciales del .env y abre una conexión PDO directa.
 * @return array{pdo: \PDO, installer: \App\Install\DbInstaller}
 */
function _getRepairConnection(): array
{
    require_once __DIR__ . '/../src/Install/DbInstaller.php';
    require_once __DIR__ . '/../src/Install/UpdateManager.php';
    require_once __DIR__ . '/../src/Install/InstallLock.php';
    require_once __DIR__ . '/../src/Install/ModuleRegistry.php';

    $envFile = __DIR__ . '/../.env';
    $config  = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $config[trim($k)] = trim($v, '"\'');
    }
    $config['DB_PORT'] = $config['DB_PORT'] ?? '3306';

    $installer = new \App\Install\DbInstaller();
    $pdo       = $installer->connect($config);

    return ['pdo' => $pdo, 'installer' => $installer];
}

/**
 * Repair completo: schema + seed + módulos requeridos + migraciones + lock.
 */
function _autoRepairDatabase(): void
{
    ['pdo' => $pdo, 'installer' => $installer] = _getRepairConnection();

    // 1. Schema + seed (idempotentes: IF NOT EXISTS / ON DUPLICATE KEY)
    $dbInitDir = realpath(__DIR__ . '/../db/init') ?: (__DIR__ . '/../db/init');
    foreach (['01_schema.sql', '02_seed.sql'] as $sqlFile) {
        $installer->runSqlFile($pdo, $dbInitDir . '/' . $sqlFile);
    }

    // 2. Módulos requeridos
    $registry = new \App\Install\ModuleRegistry();
    foreach ($registry->discover() as $module) {
        if ($module['required'] && !empty($module['sqlPath'])) {
            $installer->runSqlFile($pdo, $module['sqlPath']);
        }
    }

    // 3. Migraciones pendientes
    $manager = new \App\Install\UpdateManager($pdo);
    $manager->applyAll($installer);

    // 4. Marcar como instalado
    (new \App\Install\InstallLock())->lock();
}

/**
 * Aplica solo migraciones pendientes (para cuando las tablas ya existen
 * pero faltan updates, ej: locations, nuevas settings, etc.).
 */
function _applyPendingMigrations(): void
{
    require_once __DIR__ . '/../src/Install/DbInstaller.php';
    require_once __DIR__ . '/../src/Install/UpdateManager.php';
    require_once __DIR__ . '/../src/Install/InstallLock.php';
    require_once __DIR__ . '/../src/Install/ModuleRegistry.php';

    // Usar Eloquent PDO ya conectado para evitar abrir otra conexión
    $pdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();

    $manager = new \App\Install\UpdateManager($pdo);
    $pending = $manager->getPendingMigrations();

    if (empty($pending)) {
        return;
    }

    $installer = new \App\Install\DbInstaller();
    $manager->applyAll($installer);
}
