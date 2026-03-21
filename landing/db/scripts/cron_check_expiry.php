<?php

/**
 * Cron de auto-suspensión de sitios vencidos.
 * Uso: php cron_check_expiry.php
 * Cron sugerido: 0 2 * * * php /path/to/landing/db/scripts/cron_check_expiry.php
 */

// Bootstrap: cargar autoload y Eloquent
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\SiteConnectionService;

// Cargar .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// Configurar Eloquent
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

// Verificar que el módulo está habilitado
$moduleEnabled = Capsule::table('settings')
    ->where('setting_key', 'module_site_manager_enabled')
    ->value('setting_value');

if ($moduleEnabled !== '1') {
    echo "[" . date('Y-m-d H:i:s') . "] Módulo site_manager no está habilitado. Saliendo.\n";
    exit(0);
}

// Obtener clave de encriptación
$encryptionKey = Capsule::table('settings')
    ->where('setting_key', 'site_manager_encryption_key')
    ->value('setting_value') ?? '';

if (empty($encryptionKey)) {
    echo "[" . date('Y-m-d H:i:s') . "] Clave de encriptación no configurada. Saliendo.\n";
    exit(1);
}

// Buscar sitios activos con auto_suspend y plan vencido
$expiredSites = Site::where('status', 'active')
    ->where('auto_suspend', 1)
    ->where('plan_end', '<', date('Y-m-d'))
    ->get();

if ($expiredSites->isEmpty()) {
    echo "[" . date('Y-m-d H:i:s') . "] No hay sitios vencidos para suspender.\n";
    exit(0);
}

$encryption = new EncryptionService();
$connectionService = new SiteConnectionService($encryption);

foreach ($expiredSites as $site) {
    echo "[" . date('Y-m-d H:i:s') . "] Suspendiendo: {$site->name} ({$site->domain}) - Plan venció el {$site->plan_end->format('Y-m-d')}\n";

    $site->update(['status' => 'suspended']);

    try {
        $connectionService->syncStatus($site, $encryptionKey);
        echo "  -> Sync remoto OK\n";
    } catch (\Exception $e) {
        echo "  -> Error sync remoto: " . $e->getMessage() . "\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Proceso completado. {$expiredSites->count()} sitio(s) suspendido(s).\n";
