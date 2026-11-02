<?php

namespace App\Controllers\Admin;

use App\Services\DirectoryListingService;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * /admin/directories — listado de solo lectura de las carpetas de una ruta local
 * (ej. /home/cuenta/public_html/*). Superadmin, solo dominio master.
 */
class DirectoryAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $root = DirectoryListingService::configuredRoot();
        $dirs = [];
        $error = null;
        $resolved = null;

        if ($root !== '') {
            try {
                $resolved = DirectoryListingService::resolve($root);
                $dirs = DirectoryListingService::list($resolved, DirectoryListingService::siteDomains());
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render($response, 'admin/directories/index', [
            'root'     => $root,
            'resolved' => $resolved,
            'dirs'     => $dirs,
            'error'    => $error,
            'default'  => DirectoryListingService::defaultRoot(),
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $root = trim((string) (((array) $request->getParsedBody())['root'] ?? ''));

        try {
            $value = $root === '' ? '' : DirectoryListingService::resolve($root);
            Capsule::table('settings')->updateOrInsert(
                ['setting_key' => DirectoryListingService::SETTING],
                ['setting_value' => $value]
            );
            $_SESSION['flash_success'] = $value === ''
                ? 'Ruta reiniciada al valor por defecto.'
                : "Ruta guardada: {$value}";
        } catch (\InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        return $response->withHeader('Location', url('admin/directories'))->withStatus(302);
    }
}
