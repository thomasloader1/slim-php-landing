<?php

namespace App\Middleware;

use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\SiteConnectionService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Resuelve el contexto de cada request:
 *
 *  - "master": el host está en MASTER_DOMAINS o el módulo site_manager está apagado.
 *    Se usa la DB master (conexión default). Es la landing principal + panel completo.
 *  - "site:<id>": cualquier otro host. Se busca en `sites` por dominio y la conexión
 *    default pasa a ser la DB propia del sitio (incluido /admin/login, así cada sitio
 *    autentica contra sus propios usuarios).
 *
 * Atributos que deja en el request: context, is_master, resolved_site, tenant_connection.
 */
class TenantResolverMiddleware implements MiddlewareInterface
{
    public const CONTEXT_MASTER = 'master';

    private EncryptionService $encryption;
    private SiteConnectionService $connectionService;

    public function __construct(?EncryptionService $encryption = null, ?SiteConnectionService $connectionService = null)
    {
        $this->encryption = $encryption ?? new EncryptionService();
        $this->connectionService = $connectionService ?? new SiteConnectionService($this->encryption);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $host = self::normalizeHost($request->getUri()->getHost());

        if (self::isMasterHost($host) || !$this->moduleEnabled()) {
            return $handler->handle($this->asMaster($request));
        }

        // Módulo activo pero sin clave de cifrado: FAIL CLOSED
        if (!$this->encryption->hasKey('v1') && !$this->encryption->hasKey('v2')) {
            error_log('[TenantResolver][SECURITY] Module site_manager enabled but no encryption key configured (TENANT_ENCRYPTION_KEY missing). Request blocked.');
            return $this->json(503, 'Tenant resolution unavailable: encryption key not configured');
        }

        $site = Site::where('domain', $host)->where('status', 'active')->first();
        if (!$site) {
            error_log('[TenantResolver][SECURITY] No active site found for host: ' . $host . '. Request blocked.');
            return $this->json(404, 'No active site configured for this domain');
        }

        try {
            $connectionName = $this->connectionService->connect($site);
            Capsule::connection($connectionName)->getPdo(); // falla rápido si la DB no responde
            self::setDefaultConnection($connectionName);
        } catch (\Throwable $e) {
            error_log('[TenantResolver][SECURITY] Failed to connect to tenant DB for site ' . $site->id . ': ' . $e->getMessage());
            return $this->json(503, 'Tenant database connection failed');
        }

        $request = $request
            ->withAttribute('context', 'site:' . $site->id)
            ->withAttribute('is_master', false)
            ->withAttribute('is_master_admin', false)
            ->withAttribute('tenant_connection', $connectionName)
            ->withAttribute('resolved_site', $site);

        return $handler->handle($request);
    }

    private function asMaster(Request $request): Request
    {
        return $request
            ->withAttribute('context', self::CONTEXT_MASTER)
            ->withAttribute('is_master', true)
            // compatibilidad con código previo que lee este atributo
            ->withAttribute('is_master_admin', true);
    }

    private function moduleEnabled(): bool
    {
        try {
            return Capsule::table('settings')
                ->where('setting_key', 'module_site_manager_enabled')
                ->value('setting_value') === '1';
        } catch (\Throwable $e) {
            // Sin tabla settings (instalación incompleta): tratar como master para poder reparar
            error_log('[TenantResolver] No se pudo leer settings: ' . $e->getMessage());
            return false;
        }
    }

    /** Dominios que sirven la DB master. CSV en MASTER_DOMAINS; localhost siempre incluido. */
    public static function masterDomains(): array
    {
        $raw = getenv('MASTER_DOMAINS');
        if ($raw === false) {
            $raw = $_ENV['MASTER_DOMAINS'] ?? '';
        }
        $domains = array_filter(array_map(
            fn ($d) => self::normalizeHost(trim($d)),
            explode(',', (string) $raw)
        ));
        // En producción no: un "Host: localhost" contra la IP pública daría el panel master
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
        if ($env !== 'production') {
            $domains[] = 'localhost';
            $domains[] = '127.0.0.1';
        }
        return array_values(array_unique($domains));
    }

    public static function isMasterHost(string $host): bool
    {
        return in_array(self::normalizeHost($host), self::masterDomains(), true);
    }

    /** Quita puerto y prefijo www., pasa a minúsculas. */
    public static function normalizeHost(string $host): string
    {
        if (($pos = strpos($host, ':')) !== false) {
            $host = substr($host, 0, $pos);
        }
        $host = preg_replace('/^www\./i', '', $host);
        return strtolower($host);
    }

    private static function setDefaultConnection(string $name): void
    {
        $reflection = new \ReflectionClass(Capsule::class);
        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->getValue(null)->getDatabaseManager()->setDefaultConnection($name);
    }

    private function json(int $status, string $error): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => $error]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
