<?php

namespace App\Controllers\Admin;

use App\Middleware\TenantResolverMiddleware;
use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\SiteConnectionService;
use App\Services\SiteValidator;
use App\Services\SsoTokenService;
use App\Services\TenantProvisioner;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Gestor de sitios (solo superadmin en el dominio master; ver MasterOnlyMiddleware).
 * Ciclo de vida de un sitio:
 *   1. Registrar (dominio + credenciales de su DB propia)
 *   2. Crear DB/usuario (local/VPS) — en cPanel se crea desde cPanel
 *   3. Inicializar (esquema + admin del sitio)
 *   4. Apuntar el dominio (alias en cPanel) y verificarlo
 *   5. Administrar: entrar como, suspender, activar
 */
class SiteAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $sites = Site::orderBy('id', 'desc')->get();
        $moduleEnabled = Capsule::table('settings')
            ->where('setting_key', 'module_site_manager_enabled')->value('setting_value') === '1';
        return $this->render($response, 'admin/sites/index', ['sites' => $sites, 'moduleEnabled' => $moduleEnabled]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/sites/form', [
            'site'            => null,
            'defaultRedirect' => $this->getDefaultRedirectUrl(),
            'old'             => $this->pullOld(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $encryption = new EncryptionService();

        if (!$encryption->hasKey('v1') && !$encryption->hasKey('v2')) {
            return $this->backWithError($response, 'admin/sites/create', 'Falta TENANT_ENCRYPTION_KEY en el .env: no se pueden guardar credenciales. Ver Sistema.', $data);
        }

        if ($error = SiteValidator::validate($data, null, true)) {
            return $this->backWithError($response, 'admin/sites/create', $error, $data);
        }

        $site = Site::create(array_merge($this->fields($data), [
            'db_pass_encrypted' => $encryption->encrypt((string) $data['db_pass']),
            'status'            => 'active',
        ]));

        $_SESSION['flash_success'] = 'Sitio registrado. Siguiente paso: crear la base de datos (si no existe) e inicializarlo.';
        return $response->withHeader('Location', url('admin/sites/edit/' . $site->id))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $log = $_SESSION['site_log'] ?? [];
        unset($_SESSION['site_log']);

        return $this->render($response, 'admin/sites/form', [
            'site'            => $site,
            'defaultRedirect' => $this->getDefaultRedirectUrl(),
            'old'             => $this->pullOld(),
            'log'             => $log,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $data = (array) $request->getParsedBody();

        if ($error = SiteValidator::validate($data, (int) $site->id, false)) {
            return $this->backWithError($response, 'admin/sites/edit/' . $site->id, $error, $data);
        }

        $updateData = $this->fields($data);
        // Solo actualizar password si se ingresa uno nuevo
        if (!empty($data['db_pass'])) {
            $updateData['db_pass_encrypted'] = (new EncryptionService())->encrypt((string) $data['db_pass']);
        }
        $site->update($updateData);

        $_SESSION['flash_success'] = 'Sitio actualizado.';
        return $response->withHeader('Location', url('admin/sites/edit/' . $site->id))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Site::destroy($args['id']);
        $_SESSION['flash_success'] = 'Sitio eliminado del registro (su base de datos no se tocó).';
        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    public function suspend(Request $request, Response $response, array $args): Response
    {
        return $this->changeStatus($response, (int) $args['id'], 'suspended');
    }

    public function activate(Request $request, Response $response, array $args): Response
    {
        return $this->changeStatus($response, (int) $args['id'], 'active');
    }

    public function testConnection(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $encryption = new EncryptionService();

        $result = ['success' => false, 'message' => 'Clave de encriptación no configurada.'];

        if ($encryption->hasKey('v1') || $encryption->hasKey('v2')) {
            try {
                $connName = (new SiteConnectionService($encryption))->connect($site);
                Capsule::connection($connName)->getPdo();
                $result = ['success' => true, 'message' => 'Conexión exitosa.'];
            } catch (\Throwable $e) {
                $result = ['success' => false, 'message' => 'No se pudo conectar: ' . $this->safeError($e)];
            }
        }

        return $this->json($response, $result);
    }

    /** Crea DB + usuario MySQL con la conexión master (Docker local / VPS con permisos). */
    public function createDatabase(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        try {
            $plain = (new EncryptionService())->decrypt($site->db_pass_encrypted);
            $_SESSION['site_log'] = (new TenantProvisioner())->createDatabase($site, $plain);
            $_SESSION['flash_success'] = 'Base de datos y usuario creados. Ahora inicializá el sitio.';
        } catch (\Throwable $e) {
            error_log('[SiteAdmin] createDatabase site ' . $site->id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo crear la DB: ' . $this->safeError($e)
                . ' — En cPanel creala desde "Bases de datos MySQL".';
        }
        return $response->withHeader('Location', url('admin/sites/edit/' . $site->id))->withStatus(302);
    }

    /** Aplica esquema + settings base en la DB del sitio y crea/actualiza su admin. */
    public function initialize(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $data = (array) $request->getParsedBody();
        try {
            $_SESSION['site_log'] = (new TenantProvisioner())->initialize(
                $site,
                trim((string) ($data['admin_name'] ?? '')),
                trim((string) ($data['admin_email'] ?? '')),
                (string) ($data['admin_password'] ?? '')
            );
            $_SESSION['flash_success'] = 'Sitio inicializado. Ya podés entrar a su panel.';
        } catch (\Throwable $e) {
            error_log('[SiteAdmin] initialize site ' . $site->id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo inicializar: ' . $this->safeError($e);
        }
        return $response->withHeader('Location', url('admin/sites/edit/' . $site->id))->withStatus(302);
    }

    /** Verifica DNS y que el dominio llegue a esta app resolviendo este sitio (/healthz). */
    public function verifyDomain(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        $domain = $site->domain;

        $siteIps = $this->resolve($domain);
        $masterIps = [];
        foreach (TenantResolverMiddleware::masterDomains() as $md) {
            if (!in_array($md, ['localhost', '127.0.0.1'], true)) {
                $masterIps = array_merge($masterIps, $this->resolve($md));
            }
        }
        $masterIps = array_values(array_unique($masterIps));

        // Anti-SSRF: solo se consulta /healthz si el dominio resuelve a IPs públicas
        $publicIps = array_filter($siteIps, fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE));
        $local = (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production')) !== 'production';

        $health = null;
        $healthError = null;
        if ($siteIps !== [] && count($publicIps) !== count($siteIps) && !$local) {
            $healthError = 'El dominio resuelve a una IP privada/reservada: no se consulta.';
            $siteIpsBlocked = true;
        }
        foreach (empty($siteIpsBlocked) ? ['https', 'http'] : [] as $scheme) {
            $ctx = stream_context_create([
                'http' => ['timeout' => 6, 'ignore_errors' => true, 'follow_location' => 0, 'header' => "Accept: application/json\r\n"],
                'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $body = @file_get_contents("{$scheme}://{$domain}/healthz", false, $ctx);
            if ($body !== false) {
                $decoded = json_decode($body, true);
                // Solo se devuelve lo esperado, nunca el cuerpo crudo
                $health = ['context' => is_array($decoded) && is_string($decoded['context'] ?? null) ? $decoded['context'] : null, 'scheme' => $scheme];
                break;
            }
            $healthError = error_get_last()['message'] ?? 'sin respuesta';
        }

        $reachesThisSite = is_array($health) && ($health['context'] ?? null) === 'site:' . $site->id;

        return $this->json($response, [
            'domain'        => $domain,
            'dns'           => $siteIps,
            'master_ips'    => $masterIps,
            'dns_ok'        => $siteIps !== [] && ($masterIps === [] || array_intersect($siteIps, $masterIps) !== []),
            'reaches_app'   => $reachesThisSite,
            'health'        => $health,
            'health_error'  => $health === null ? $healthError : null,
            'success'       => $reachesThisSite,
            'message'       => $reachesThisSite
                ? "OK: {$domain} llega a esta app y resuelve este sitio ({$health['scheme']})."
                : ($siteIps === []
                    ? "{$domain} no resuelve en DNS todavía."
                    : "{$domain} resuelve a " . implode(', ', $siteIps) . ' pero /healthz no confirma este sitio. Revisá el alias en cPanel y que el sitio esté activo.'),
        ]);
    }

    /** Superadmin entra al panel del sitio con un token de un solo uso. */
    public function impersonate(Request $request, Response $response, array $args): Response
    {
        $site = Site::findOrFail($args['id']);
        if ($site->status !== 'active') {
            $_SESSION['flash_error'] = 'Solo se puede entrar a sitios activos.';
            return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
        }

        try {
            $token = (new SsoTokenService())->issue((int) $site->id, Capsule::connection('default'));
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo generar el acceso: ' . $e->getMessage();
            return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
        }

        $uri = $request->getUri();
        $port = $uri->getPort();
        $hostPort = $site->domain . ($port && !in_array($port, [80, 443], true) ? ':' . $port : '');
        $base = rtrim(getenv('APP_BASE_PATH') ?: ($_ENV['APP_BASE_PATH'] ?? ''), '/');
        $scheme = (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production')) === 'production' ? 'https' : $uri->getScheme();
        $target = $scheme . '://' . $hostPort . $base . '/admin/sso?token=' . urlencode($token);
        $next = AuthController::safeNext((string) (((array) $request->getParsedBody())['next'] ?? ''));
        if ($next !== 'admin') {
            $target .= '&next=' . urlencode($next);
        }

        error_log("[SiteAdmin][AUDIT] {$_SESSION['user_name']} (master) entró como admin del sitio {$site->id} ({$site->domain})");
        return $response->withHeader('Location', $target)->withStatus(302);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function changeStatus(Response $response, int $id, string $status): Response
    {
        $site = Site::findOrFail($id);
        $site->update(['status' => $status]);

        try {
            (new SiteConnectionService(new EncryptionService()))->syncStatus($site);
            $_SESSION['flash_success'] = $status === 'active' ? 'Sitio activado.' : 'Sitio suspendido.';
        } catch (\Throwable $e) {
            // El estado en master ya cambió (el resolver deja de servirlo si no está activo)
            error_log('[SiteAdmin] syncStatus site ' . $site->id . ': ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Estado guardado, pero no se pudo sincronizar con la DB del sitio: ' . $this->safeError($e);
        }

        return $response->withHeader('Location', url('admin/sites'))->withStatus(302);
    }

    private function fields(array $data): array
    {
        return [
            'name'          => trim((string) $data['name']),
            'domain'        => strtolower(trim((string) $data['domain'])),
            'db_host'       => trim((string) ($data['db_host'] ?? 'localhost')),
            'db_port'       => (int) ($data['db_port'] ?? 3306),
            'db_name'       => (string) $data['db_name'],
            'db_user'       => (string) $data['db_user'],
            'plan_name'     => in_array($data['plan_name'] ?? '', SiteValidator::PLANS, true) ? $data['plan_name'] : 'basico',
            'plan_start'    => $data['plan_start'],
            'plan_end'      => $data['plan_end'],
            'domain_expiry' => SiteValidator::isDate((string) ($data['domain_expiry'] ?? '')) ? $data['domain_expiry'] : null,
            'redirect_url'  => $this->sanitizeRedirectUrl((string) ($data['redirect_url'] ?? '')),
            'auto_suspend'  => !empty($data['auto_suspend']) ? 1 : 0,
            'notes'         => ($data['notes'] ?? '') !== '' ? $data['notes'] : null,
        ];
    }

    private function backWithError(Response $response, string $path, string $error, array $data): Response
    {
        unset($data['db_pass'], $data['_token']);
        $_SESSION['flash_error'] = $error;
        $_SESSION['site_old'] = $data;
        return $response->withHeader('Location', url($path))->withStatus(302);
    }

    private function pullOld(): array
    {
        $old = $_SESSION['site_old'] ?? [];
        unset($_SESSION['site_old']);
        return $old;
    }

    private function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A) ?: [];
        return array_values(array_unique(array_filter(array_map(fn ($r) => $r['ip'] ?? null, $records))));
    }

    /** Mensaje de error sin credenciales (PDO puede incluir el usuario en el texto). */
    private function safeError(\Throwable $e): string
    {
        return preg_replace("/'[^']*'@'[^']*'/", "'***'@'***'", $e->getMessage());
    }

    private function json(Response $response, array $data): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getDefaultRedirectUrl(): string
    {
        return '/';
    }

    private function sanitizeRedirectUrl(string $redirectUrl): string
    {
        if ($redirectUrl === '') {
            return '';
        }
        // Relativa (mismo origen); rechaza protocol-relative //evil.com
        if (str_starts_with($redirectUrl, '/')) {
            return str_starts_with($redirectUrl, '//') ? '/' : $redirectUrl;
        }
        $parsed = parse_url($redirectUrl);
        if ($parsed === false || empty($parsed['host'])) {
            return '/';
        }
        if (!in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) {
            return '/';
        }
        return $redirectUrl;
    }
}
