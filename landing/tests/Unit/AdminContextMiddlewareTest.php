<?php

namespace Tests\Unit;

use App\Middleware\AuthMiddleware;
use App\Middleware\MasterOnlyMiddleware;
use App\Services\AuthService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

/**
 * Regresión del bug "Tenant context required": tras el login, todo /admin respondía 403
 * porque AuthMiddleware exigía un tenant aunque el request fuera del dominio master.
 */
class AdminContextMiddlewareTest extends TestCase
{
    private ServerRequestFactory $requests;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requests = new ServerRequestFactory();
        $_SESSION = [];

        $this->userId = (int) Capsule::table('users')->insertGetId([
            'name' => 'Ctx Test',
            'email' => 'ctx-' . uniqid() . '@example.com',
            'password_hash' => password_hash('x', PASSWORD_BCRYPT),
            'role' => 'admin',
            'active' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Capsule::table('users')->where('id', $this->userId)->delete();
        $_SESSION = [];
        parent::tearDown();
    }

    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public bool $called = false;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return new \Slim\Psr7\Response(200);
            }
        };
    }

    private function auth(): AuthService
    {
        $auth = $this->getMockBuilder(AuthService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['logout'])
            ->getMock();
        $auth->method('logout')->willReturnCallback(function () {
            $_SESSION = [];
        });
        return $auth;
    }

    private function request(string $path, ?string $context): ServerRequestInterface
    {
        $r = $this->requests->createServerRequest('GET', $path);
        return $context === null ? $r : $r->withAttribute('context', $context);
    }

    /** @test */
    public function master_session_reaches_dashboard_without_tenant(): void
    {
        $_SESSION = ['user_id' => $this->userId, 'context' => 'master'];
        $handler = $this->handler();

        $response = (new AuthMiddleware($this->auth()))->process($this->request('/admin', 'master'), $handler);

        $this->assertTrue($handler->called);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin', $_SESSION['user_role']);
    }

    /** @test */
    public function legacy_session_without_context_is_treated_as_master(): void
    {
        $_SESSION = ['user_id' => $this->userId];
        $handler = $this->handler();

        (new AuthMiddleware($this->auth()))->process($this->request('/admin/settings', 'master'), $handler);

        $this->assertTrue($handler->called);
    }

    /** @test */
    public function session_from_other_context_is_logged_out(): void
    {
        $_SESSION = ['user_id' => $this->userId, 'context' => 'master'];
        $handler = $this->handler();

        $response = (new AuthMiddleware($this->auth()))->process($this->request('/admin', 'site:99'), $handler);

        $this->assertFalse($handler->called);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('admin/login', $response->getHeaderLine('Location'));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    /** @test */
    public function unauthenticated_redirects_to_login(): void
    {
        $handler = $this->handler();
        $response = (new AuthMiddleware($this->auth()))->process($this->request('/admin', 'master'), $handler);

        $this->assertFalse($handler->called);
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function master_only_allows_root_in_master_context(): void
    {
        $_SESSION = ['user_id' => 1, 'user_role' => 'root', 'context' => 'master'];
        $handler = $this->handler();
        (new MasterOnlyMiddleware())->process($this->request('/admin/sites', 'master'), $handler);
        $this->assertTrue($handler->called);
    }

    /** @test */
    public function master_only_redirects_master_admins_that_are_not_root(): void
    {
        $_SESSION = ['user_id' => 1, 'user_role' => 'admin', 'context' => 'master'];
        $handler = $this->handler();
        $response = (new MasterOnlyMiddleware())->process($this->request('/admin/sites', 'master'), $handler);

        $this->assertFalse($handler->called);
        $this->assertSame(302, $response->getStatusCode());
    }

    /** @test */
    public function master_only_hides_routes_in_site_context(): void
    {
        $_SESSION = ['user_role' => 'admin'];
        $handler = $this->handler();
        $response = (new MasterOnlyMiddleware())->process($this->request('/admin/sites', 'site:3'), $handler);

        $this->assertFalse($handler->called);
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function master_only_redirects_editors(): void
    {
        $_SESSION = ['user_role' => 'editor'];
        $handler = $this->handler();
        $response = (new MasterOnlyMiddleware())->process($this->request('/admin/sites', 'master'), $handler);

        $this->assertFalse($handler->called);
        $this->assertSame(302, $response->getStatusCode());
    }
}
