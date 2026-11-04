<?php

namespace App\Controllers\Admin;

use App\Services\KeyRotationService;
use App\Services\MigrationService;
use App\Services\SystemCheckService;
use App\Services\TenantProvisioner;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * /admin/system — diagnóstico y reparación de la instalación master
 * (lo que antes requería phpMyAdmin o consola).
 */
class SystemAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $checks = (new SystemCheckService())->run();

        $migrations = [];
        try {
            $migrations = (new MigrationService())->status(Capsule::connection('default'), 'master');
        } catch (\Throwable $e) {
            // sin DB: los checks ya lo muestran
        }

        $log = $_SESSION['system_log'] ?? [];
        unset($_SESSION['system_log']);

        return $this->render($response, 'admin/system/index', [
            'checks'     => $checks,
            'migrations' => $migrations,
            'log'        => $log,
        ]);
    }

    public function migrate(Request $request, Response $response): Response
    {
        try {
            $log = (new MigrationService())->migrate(Capsule::connection('default'), 'master');

            // Las DBs de los sitios cliente también necesitan el esquema nuevo
            // (sin seed: no toca settings ni contenido de cada cliente)
            $sites = (new TenantProvisioner())->migrateAllSites();
            $_SESSION['system_log'] = array_merge($log, $sites['log']);

            if ($sites['failed'] > 0) {
                $_SESSION['flash_error'] = "Master migrada. Sitios: {$sites['ok']} OK, {$sites['failed']} con error (ver detalle abajo).";
            } else {
                $_SESSION['flash_success'] = "Migraciones aplicadas (master + {$sites['ok']} sitio/s).";
            }
        } catch (\Throwable $e) {
            log_error('[SystemAdmin] migrate: ' . $e->getMessage(), null, [], $e);
            $_SESSION['flash_error'] = 'Error aplicando migraciones: ' . $e->getMessage();
        }
        return $this->back($response);
    }

    public function rotateKeys(Request $request, Response $response): Response
    {
        try {
            $result = (new KeyRotationService())->rotate();
            $_SESSION['system_log'] = array_merge(["Re-cifrados: {$result['rotated']}"], $result['errors']);
            if ($result['errors']) {
                $_SESSION['flash_error'] = count($result['errors']) . ' secreto(s) no se pudieron re-cifrar (¿falta la clave anterior?).';
            } else {
                $_SESSION['flash_success'] = 'Secretos re-cifrados con la clave activa.';
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Error en la rotación: ' . $e->getMessage();
        }
        return $this->back($response);
    }

    private function back(Response $response): Response
    {
        return $response->withHeader('Location', url('admin/system'))->withStatus(302);
    }
}
