#!/usr/bin/env php
<?php
/**
 * Crea la DB de un cliente, la registra en `sites` y la inicializa.
 * Equivale a "Nuevo sitio" → "Crear DB y usuario" → "Inicializar" en /admin/sites.
 *
 * Uso (Docker local / VPS con un usuario MySQL master con permisos CREATE USER):
 *   php create_client_db.php <dominio> <nombre_site> <db_name> <db_user> [plan_name] [plan_end_days]
 *
 * Las contraseñas se piden por STDIN (no por argumento, quedarían en `ps` y en el historial):
 *   1ª línea: contraseña de la DB del cliente
 *   2ª línea: contraseña del admin del sitio (usuario admin@<dominio>)
 *
 * En cPanel NO sirve (no hay CREATE USER): usá Admin → Servidores → Provisionar
 * desde el panel local, o creá la DB en cPanel y usá "Inicializar" en el panel.
 */

require __DIR__ . '/../../bootstrap/app.php';

use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\SiteValidator;
use App\Services\TenantProvisioner;

if ($argc < 5) {
    fwrite(STDERR, "Uso: php create_client_db.php <dominio> <nombre_site> <db_name> <db_user> [plan_name] [plan_end_days]\n");
    fwrite(STDERR, "Contraseñas por STDIN: 1ª línea DB, 2ª línea admin del sitio.\n");
    exit(1);
}

[, $domain, $name, $dbName, $dbUser] = $argv;
$domain = strtolower($domain);
$planName = $argv[5] ?? 'basico';
$planDays = max(1, (int) ($argv[6] ?? 365));

$readLine = function (string $prompt): string {
    fwrite(STDERR, "{$prompt}: ");
    $v = rtrim((string) fgets(STDIN), "\r\n");
    if ($v === '') {
        throw new RuntimeException("{$prompt} vacía");
    }
    return $v;
};

try {
    $dbPass = $readLine('Contraseña de la DB del cliente');
    $adminPass = $readLine("Contraseña de admin@{$domain}");

    $data = [
        'name' => $name, 'domain' => $domain, 'db_host' => getenv('DB_HOST') ?: 'localhost',
        'db_port' => (int) (getenv('DB_PORT') ?: 3306), 'db_name' => $dbName, 'db_user' => $dbUser,
        'db_pass' => $dbPass, 'plan_start' => date('Y-m-d'),
        'plan_end' => date('Y-m-d', strtotime("+{$planDays} days")),
    ];
    if ($error = SiteValidator::validate($data)) {
        throw new RuntimeException($error);
    }

    $site = Site::create([
        'name' => $name, 'domain' => $domain, 'db_host' => $data['db_host'], 'db_port' => $data['db_port'],
        'db_name' => $dbName, 'db_user' => $dbUser,
        'db_pass_encrypted' => (new EncryptionService())->encrypt($dbPass),
        'plan_name' => in_array($planName, SiteValidator::PLANS, true) ? $planName : 'basico',
        'plan_start' => $data['plan_start'], 'plan_end' => $data['plan_end'],
        'status' => 'active', 'redirect_url' => '', 'auto_suspend' => 1,
    ]);
    echo "Sitio registrado: #{$site->id} {$domain}\n";

    $provisioner = new TenantProvisioner();
    foreach (array_merge(
        $provisioner->createDatabase($site, $dbPass),
        $provisioner->initialize($site, "{$name} Admin", "admin@{$domain}", $adminPass)
    ) as $line) {
        echo $line, "\n";
    }

    echo "\nListo. Apuntá el DNS de {$domain} a este servidor (misma raíz de documentos que el master).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
