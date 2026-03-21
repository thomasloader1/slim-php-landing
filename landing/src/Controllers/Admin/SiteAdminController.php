<?php

namespace App\Controllers\Admin;

use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\SiteConnectionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class SiteAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    private function getEncryptionKey(): string
    {
        return Capsule::table('settings')
            ->where('setting_key', 'site_manager_encryption_key')
            ->value('setting_value') ?? '';
    }

    private function getDefaultRedirectUrl(): string
    {
        return Capsule::table('settings')
            ->where('setting_key', 'site_manager_default_redirect_url')
            ->value('setting_value') ?? '';
    }

    public function index(Request $request, Response $response): Response
    {
        $sites = Site::orderBy('id', 'desc')->get();
        $html = $this->view->make('admin/sites/index', ['sites' => $sites])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $defaultRedirect = $this->getDefaultRedirectUrl();
        $html = $this->view->make('admin/sites/form', [
            'site'            => null,
            'defaultRedirect' => $defaultRedirect,
        ])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $encryptionKey = $this->getEncryptionKey();

        if (empty($encryptionKey)) {
            // Sin clave de encriptación no se puede guardar
            return $response->withHeader('Location', url('admin/sites/create'))->withStatus(302);
        }

        $encryption = new EncryptionService();
        $encryptedPass = $encryption->encrypt($data['db_pass'] ?? '', $encryptionKey);

        Site::create([
            'name'              => $data['name'] ?? '',
            'domain'            => $data['domain'] ?? '',
            'db_host'           => $data['db_host'] ?? 'localhost',
            'db_port'           => (int)($data['db_port'] ?? 3306),
            'db_name'           => $data['db_name'] ?? '',
            'db_user'           => $data['db_user'] ?? '',
            'db_pass_encrypted' => $encryptedPass,
            'plan_name'         => $data['plan_name'] ?? 'basico',
            'plan_start'        => $data['plan_start'] ?? date('Y-m-d'),
            'plan_end'          => $data['plan_end'] ?? date('Y-m-d'),
            'domain_expiry'     => !empty($data['domain_expiry']) ? $data['domain_expiry'] : null,
            'status'            => 'active',
            'redirect_url'      => $data['redirect_url'] ?? '',
            'auto_suspend'      => isset($data['auto_suspend']) ? 1 : 0,
            'notes'             => $data['notes'] ?? null,
        ]);

        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $html = $this->view->make('admin/sites/form', [
            'site'            => $site,
            'defaultRedirect' => $this->getDefaultRedirectUrl(),
        ])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $data = $request->getParsedBody();
        $encryptionKey = $this->getEncryptionKey();

        $updateData = [
            'name'          => $data['name'] ?? '',
            'domain'        => $data['domain'] ?? '',
            'db_host'       => $data['db_host'] ?? 'localhost',
            'db_port'       => (int)($data['db_port'] ?? 3306),
            'db_name'       => $data['db_name'] ?? '',
            'db_user'       => $data['db_user'] ?? '',
            'plan_name'     => $data['plan_name'] ?? 'basico',
            'plan_start'    => $data['plan_start'] ?? date('Y-m-d'),
            'plan_end'      => $data['plan_end'] ?? date('Y-m-d'),
            'domain_expiry' => !empty($data['domain_expiry']) ? $data['domain_expiry'] : null,
            'redirect_url'  => $data['redirect_url'] ?? '',
            'auto_suspend'  => isset($data['auto_suspend']) ? 1 : 0,
            'notes'         => $data['notes'] ?? null,
        ];

        // Solo actualizar password si se ingresa uno nuevo
        if (!empty($data['db_pass'])) {
            $encryption = new EncryptionService();
            $updateData['db_pass_encrypted'] = $encryption->encrypt($data['db_pass'], $encryptionKey);
        }

        $site->update($updateData);

        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Site::destroy($args['id']);
        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    public function suspend(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $site->update(['status' => 'suspended']);

        $encryptionKey = $this->getEncryptionKey();
        if (!empty($encryptionKey)) {
            try {
                $encryption = new EncryptionService();
                $connectionService = new SiteConnectionService($encryption);
                $connectionService->syncStatus($site, $encryptionKey);
            } catch (\Exception $e) {
                // Sync falló pero el estado local se actualizó
            }
        }

        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    public function activate(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $site->update(['status' => 'active']);

        $encryptionKey = $this->getEncryptionKey();
        if (!empty($encryptionKey)) {
            try {
                $encryption = new EncryptionService();
                $connectionService = new SiteConnectionService($encryption);
                $connectionService->syncStatus($site, $encryptionKey);
            } catch (\Exception $e) {
                // Sync falló pero el estado local se actualizó
            }
        }

        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    public function testConnection(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $encryptionKey = $this->getEncryptionKey();

        $result = ['success' => false, 'message' => 'Clave de encriptación no configurada.'];

        if (!empty($encryptionKey)) {
            try {
                $encryption = new EncryptionService();
                $connectionService = new SiteConnectionService($encryption);
                $ok = $connectionService->testConnection($site, $encryptionKey);
                $result = $ok
                    ? ['success' => true,  'message' => 'Conexión exitosa.']
                    : ['success' => false, 'message' => 'No se pudo conectar a la base de datos.'];
            } catch (\Exception $e) {
                $result = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
