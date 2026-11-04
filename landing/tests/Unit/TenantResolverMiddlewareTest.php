<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Site;
use App\Services\EncryptionService;
use App\Services\SiteConnectionService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;

class TenantResolverMiddlewareTest extends TestCase
{
    private string $encryptionKey = 'test-encryption-key-32chars-long!';
    private EncryptionService $encryption;
    private SiteConnectionService $connectionService;
    private ServerRequestFactory $requestFactory;
    private string $testDomain;

    protected function setUp(): void
    {
        parent::setUp();
        // Set env vars for encryption
        $_ENV['TENANT_ENCRYPTION_KEY'] = $this->encryptionKey;
        $_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] = 'v1';
        $_ENV['APP_BASE_PATH'] = '';
        putenv('APP_BASE_PATH=');
        
        $this->encryption = new EncryptionService();
        $this->connectionService = new SiteConnectionService($this->encryption);
        $this->requestFactory = new ServerRequestFactory();
        // Generate unique domain for this test run
        $this->testDomain = 'tenant-' . uniqid() . '.example.com';
    }

    /**
     * Datos de conexión del "tenant" de prueba: la propia DB de test
     * (las credenciales reales de la conexión default, cifradas).
     */
    private function tenantDb(): array
    {
        $conn = Capsule::connection('default');
        return [
            'db_host'           => $conn->getConfig('host'),
            'db_port'           => (int) ($conn->getConfig('port') ?? 3306),
            'db_name'           => $conn->getConfig('database'),
            'db_user'           => $conn->getConfig('username'),
            'db_pass_encrypted' => $this->encryption->encrypt((string) $conn->getConfig('password')),
        ];
    }

    private function captureHandler(): \Psr\Http\Server\RequestHandlerInterface
    {
        return new class implements \Psr\Http\Server\RequestHandlerInterface {
            public ?ServerRequestInterface $capturedRequest = null;
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                return new \Slim\Psr7\Response();
            }
        };
    }

    private function setModule(string $value): void
    {
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => $value]
        );
    }

    /** @test */
    public function master_domain_is_master_context_even_with_module_enabled(): void
    {
        putenv('MASTER_DOMAINS=principal.example.com, otro-master.example.com');
        $this->setModule('1');

        foreach (['principal.example.com', 'www.principal.example.com', 'OTRO-MASTER.example.com', 'localhost'] as $host) {
            $handler = $this->captureHandler();
            $request = $this->requestFactory->createServerRequest('GET', '/admin/settings');
            $request = $request->withUri($request->getUri()->withHost($host));

            (new \App\Middleware\TenantResolverMiddleware())->process($request, $handler);

            $this->assertNotNull($handler->capturedRequest, "host {$host} debería llegar al handler");
            $this->assertSame('master', $handler->capturedRequest->getAttribute('context'));
            $this->assertTrue($handler->capturedRequest->getAttribute('is_master'));
            $this->assertNull($handler->capturedRequest->getAttribute('tenant_connection'));
        }

        putenv('MASTER_DOMAINS');
        $this->setModule('0');
    }

    /** @test */
    public function admin_routes_on_unknown_host_are_not_skipped_when_module_enabled(): void
    {
        // Antes /admin/login y /admin/sites se salteaban la resolución en cualquier host:
        // el login de un sitio cliente autenticaba contra usuarios master.
        $this->setModule('1');
        $handler = $this->captureHandler();
        $request = $this->requestFactory->createServerRequest('GET', '/admin/login');
        $request = $request->withUri($request->getUri()->withHost($this->testDomain));

        $response = (new \App\Middleware\TenantResolverMiddleware())->process($request, $handler);

        $this->assertNull($handler->capturedRequest);
        $this->assertSame(404, $response->getStatusCode());
        $this->setModule('0');
    }

    /** @test */
    public function module_disabled_any_host_is_master_context(): void
    {
        $this->setModule('0');
        $handler = $this->captureHandler();
        $request = $this->requestFactory->createServerRequest('GET', '/admin');
        $request = $request->withUri($request->getUri()->withHost($this->testDomain));

        (new \App\Middleware\TenantResolverMiddleware())->process($request, $handler);

        $this->assertSame('master', $handler->capturedRequest->getAttribute('context'));
    }

    /** @test */
    public function middleware_skips_when_module_disabled(): void
    {
        // Ensure module is disabled
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => '0']
        );

        $middleware = new \App\Middleware\TenantResolverMiddleware();

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($request->getUri()->withHost($this->testDomain));
        $capturedRequest = null;

        $handler = new class($capturedRequest) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertNull($handler->capturedRequest->getAttribute('resolved_site'));
        $this->assertNull($handler->capturedRequest->getAttribute('tenant_connection'));
    }

    /** @test */
    public function middleware_blocks_with_503_when_encryption_key_missing(): void
    {
        // Unset encryption key (también del entorno real del proceso, que EncryptionService lee primero)
        $prevKey = getenv('TENANT_ENCRYPTION_KEY');
        $prevKeyV2 = getenv('TENANT_ENCRYPTION_KEY_V2');
        unset($_ENV['TENANT_ENCRYPTION_KEY'], $_ENV['TENANT_ENCRYPTION_KEY_V2']);
        putenv('TENANT_ENCRYPTION_KEY');
        putenv('TENANT_ENCRYPTION_KEY_V2');
        
        // Enable module
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => '1']
        );

        $middleware = new \App\Middleware\TenantResolverMiddleware();

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($request->getUri()->withHost($this->testDomain));
        $capturedRequest = null;

        $handler = new class($capturedRequest) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $response = $middleware->process($request, $handler);

        // Fail-closed (SDD 6.2): el handler no se ejecuta
        $this->assertNull($handler->capturedRequest);
        $this->assertSame(503, $response->getStatusCode());

        if ($prevKey !== false) putenv('TENANT_ENCRYPTION_KEY=' . $prevKey);
        if ($prevKeyV2 !== false) putenv('TENANT_ENCRYPTION_KEY_V2=' . $prevKeyV2);
    }

    /** @test */
    public function middleware_resolves_site_by_domain(): void
    {
        // Enable module and set encryption key
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => '1']
        );
        $_ENV['TENANT_ENCRYPTION_KEY'] = $this->encryptionKey;
        $_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] = 'v1';

        // Create test site in master DB - use test DB for tenant connection
        $site = Site::create([
            'name' => 'Test Site',
            'domain' => $this->testDomain,
            ...$this->tenantDb(),
            'plan_name' => 'basico',
            'plan_start' => date('Y-m-d'),
            'plan_end' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'active',
        ]);

        $middleware = new \App\Middleware\TenantResolverMiddleware();

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($request->getUri()->withHost($this->testDomain));
        $capturedRequest = null;

        $handler = new class($capturedRequest) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $response = $middleware->process($request, $handler);

        $resolvedSite = $handler->capturedRequest->getAttribute('resolved_site');
        $this->assertNotNull($resolvedSite);
        $this->assertSame($site->id, $resolvedSite->id);
        $this->assertSame($this->testDomain, $resolvedSite->domain);

        $connectionName = $handler->capturedRequest->getAttribute('tenant_connection');
        $this->assertNotNull($connectionName);
        $this->assertStringStartsWith('site_', $connectionName);
        $this->assertSame('site:' . $site->id, $handler->capturedRequest->getAttribute('context'));
        $this->assertFalse($handler->capturedRequest->getAttribute('is_master'));

        // Cleanup
        $site->delete();
    }

    /** @test */
    public function middleware_normalizes_host_removes_www_and_port(): void
    {
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => '1']
        );
        $_ENV['TENANT_ENCRYPTION_KEY'] = $this->encryptionKey;
        $_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] = 'v1';

        $site = Site::create([
            'name' => 'Test Site',
            'domain' => $this->testDomain,
            ...$this->tenantDb(),
            'plan_name' => 'basico',
            'plan_start' => date('Y-m-d'),
            'plan_end' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'active',
        ]);

        $middleware = new \App\Middleware\TenantResolverMiddleware();

        // Test with www prefix
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($request->getUri()->withHost('www.' . $this->testDomain));
        $capturedRequest = null;

        $handler = new class($capturedRequest) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $middleware->process($request, $handler);
        $this->assertNotNull($handler->capturedRequest->getAttribute('resolved_site'));

        // Reset default connection for next request
        $mgr = self::getDatabaseManager();
        if ($mgr) {
            $mgr->setDefaultConnection('default');
        }

        // Test with port
        $request2 = $this->requestFactory->createServerRequest('GET', '/');
        $request2 = $request2->withUri($request2->getUri()->withHost($this->testDomain . ':8080'));
        $capturedRequest2 = null;

        $handler2 = new class($capturedRequest2) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $middleware->process($request2, $handler2);
        $this->assertNotNull($handler2->capturedRequest->getAttribute('resolved_site'));

        // Cleanup
        $site->delete();
    }

    /** @test */
    public function middleware_returns_404_when_site_not_found(): void
    {
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => '1']
        );
        $_ENV['TENANT_ENCRYPTION_KEY'] = $this->encryptionKey;
        $_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] = 'v1';

        $middleware = new \App\Middleware\TenantResolverMiddleware();

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($request->getUri()->withHost('nonexistent-' . uniqid() . '.example.com'));
        $capturedRequest = null;

        $handler = new class($capturedRequest) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $response = $middleware->process($request, $handler);

        // Fail-closed (SDD 6.2): el handler no se ejecuta
        $this->assertNull($handler->capturedRequest);
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function middleware_returns_404_when_site_inactive(): void
    {
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'module_site_manager_enabled'],
            ['setting_value' => '1']
        );
        $_ENV['TENANT_ENCRYPTION_KEY'] = $this->encryptionKey;
        $_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] = 'v1';

        $site = Site::create([
            'name' => 'Inactive Site',
            'domain' => 'inactive-' . uniqid() . '.example.com',
            ...$this->tenantDb(),
            'plan_name' => 'basico',
            'plan_start' => date('Y-m-d'),
            'plan_end' => date('Y-m-d', strtotime('+1 year')),
            'status' => 'suspended',
        ]);

        $middleware = new \App\Middleware\TenantResolverMiddleware();

        $request = $this->requestFactory->createServerRequest('GET', '/');
        $request = $request->withUri($request->getUri()->withHost($site->domain));
        $capturedRequest = null;

        $handler = new class($capturedRequest) implements \Psr\Http\Server\RequestHandlerInterface {
            public $capturedRequest;
            public function __construct(&$capturedRequest) {
                $this->capturedRequest = &$capturedRequest;
            }
            public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->capturedRequest = $request;
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write('ok');
                return $response;
            }
        };

        $response = $middleware->process($request, $handler);

        // Fail-closed (SDD 6.2): el handler no se ejecuta
        $this->assertNull($handler->capturedRequest);
        $this->assertSame(404, $response->getStatusCode());

        // Cleanup
        $site->delete();
    }

    /** @test */
    public function site_id_env_resolves_site_context_without_host_lookup(): void
    {
        // Copia dedicada (public_html/<cliente>/ con SITE_ID): la identidad viene del
        // .env, sin consultar dominio ni la tabla sites. El módulo apagado no aplica.
        $this->setModule('0');
        putenv('SITE_ID=42');
        $_ENV['SITE_ID'] = '42';

        try {
            $handler = $this->captureHandler();
            $request = $this->requestFactory->createServerRequest('GET', '/');
            $request = $request->withUri($request->getUri()->withHost('no-importa.example.com'));

            (new \App\Middleware\TenantResolverMiddleware())->process($request, $handler);

            $this->assertNotNull($handler->capturedRequest, 'debe llegar al handler');
            $this->assertSame('site:42', $handler->capturedRequest->getAttribute('context'));
            $this->assertFalse($handler->capturedRequest->getAttribute('is_master'));
            $this->assertFalse($handler->capturedRequest->getAttribute('is_master_admin'));
            // La conexión default ya es la del sitio (DB_* del .env): no se cambia
            $this->assertNull($handler->capturedRequest->getAttribute('tenant_connection'));
            $this->assertNull($handler->capturedRequest->getAttribute('resolved_site'));
        } finally {
            putenv('SITE_ID');
            unset($_ENV['SITE_ID']);
        }
    }

    /** @test */
    public function invalid_site_id_fails_closed_with_503(): void
    {
        $this->setModule('1');
        putenv('SITE_ID=abc');
        $_ENV['SITE_ID'] = 'abc';

        try {
            $handler = $this->captureHandler();
            $request = $this->requestFactory->createServerRequest('GET', '/');
            $request = $request->withUri($request->getUri()->withHost('cualquier.example.com'));

            $response = (new \App\Middleware\TenantResolverMiddleware())->process($request, $handler);

            $this->assertNull($handler->capturedRequest, 'fail-closed: el handler no se ejecuta');
            $this->assertSame(503, $response->getStatusCode());
        } finally {
            putenv('SITE_ID');
            unset($_ENV['SITE_ID']);
        }
    }
}
