#!/usr/bin/env php
<?php
/**
 * Rotación de clave de cifrado de credenciales (sites).
 *
 * Uso:
 *   php rotate_tenant_key.php [--dry-run] [--to=v2]
 *
 * Re-cifra con la clave destino (--to, por defecto v2) todos los secretos cifrados con
 * otra versión. Requiere TENANT_ENCRYPTION_KEY y TENANT_ENCRYPTION_KEY_V2 configuradas.
 * Equivale al botón "Re-cifrar con clave activa" de /admin/system.
 *
 * Nota: antes este script volvía a cifrar con v1 aunque pidiera v2 (EncryptionService
 * ignoraba la versión explícita); ahora usa KeyRotationService.
 */

require __DIR__ . '/../../bootstrap/app.php';

use App\Services\EncryptionService;
use App\Services\KeyRotationService;

$dryRun = in_array('--dry-run', $argv ?? [], true);
$to = 'v2';
foreach ($argv ?? [] as $a) {
    if (str_starts_with($a, '--to=')) {
        $to = substr($a, 5);
    }
}
if (!in_array($to, ['v1', 'v2'], true)) {
    fwrite(STDERR, "--to debe ser v1 o v2\n");
    exit(1);
}

$log = fn (string $m) => print('[' . date('Y-m-d H:i:s') . "] {$m}\n");

try {
    $enc = new EncryptionService(null, null, $to);
    if (!$enc->hasKey('v1') || !$enc->hasKey('v2')) {
        throw new RuntimeException('Se requieren TENANT_ENCRYPTION_KEY y TENANT_ENCRYPTION_KEY_V2.');
    }
    $svc = new KeyRotationService($enc, $to);
    $log("Secretos a re-cifrar hacia {$to}: " . $svc->pendingCount());
    if ($dryRun) {
        $log('Modo dry-run: no se realizarán cambios.');
        exit(0);
    }
    $r = $svc->rotate();
    foreach ($r['errors'] as $e) {
        $log("ERROR {$e}");
    }
    $log("Rotación completada. Procesados: {$r['rotated']}, Errores: " . count($r['errors']));
    if ($to !== (getenv('TENANT_ENCRYPTION_KEY_ACTIVE') ?: 'v1')) {
        $log("Recordá poner TENANT_ENCRYPTION_KEY_ACTIVE={$to} en el .env.");
    }
    exit($r['errors'] ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
