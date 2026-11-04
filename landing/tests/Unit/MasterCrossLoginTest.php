<?php

namespace Tests\Unit;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\MigrationService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

/**
 * Login del root de la master en un sitio hijo (copia en public_html/<cliente>/ con
 * SITE_ID + MASTER_DB_*): las credenciales se validan contra la conexión `master`
 * y la sesión queda atada a esa fuente, sin necesidad de usuarios en la DB del sitio.
 *
 * La "DB master" de test es project_landing_test_master (se crea si no existe), así
 * master y sitio tienen bases distintas como en producción.
 */
class MasterCrossLoginTest extends TestCase
{
    private const MASTER_DB = 'project_landing_test_master';

    private static bool $masterReady = false;
    private string $email;
    private string $password = 'Root-Secret-123!';
    private int $rootId = 0;
    private ?int $siteUserId = null;
    /** @var string[] claves de rate limit generadas por esta clase, para limpiar */
    private array $attemptKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];

        // Conexión `master` apuntando a una DB separada (modelo copia-por-carpeta)
        self::$capsule->addConnection($this->dbConfig(self::MASTER_DB), 'master');
        $this->ensureMasterDb();

        // MASTER_DB_* habilita el fallback de credenciales en AuthService
        putenv('MASTER_DB_NAME=' . self::MASTER_DB);
        $_ENV['MASTER_DB_NAME'] = self::MASTER_DB;

        $this->email = 'root-' . uniqid() . '@master.test';
        $this->rootId = (int) Capsule::connection('master')->table('users')->insertGetId([
            'name' => 'Root Master',
            'email' => $this->email,
            'password_hash' => password_hash($this->password, PASSWORD_BCRYPT),
            'role' => 'root',
            'active' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->rootId > 0) {
                Capsule::connection('master')->table('users')->where('id', $this->rootId)->delete();
            }
            if ($this->siteUserId !== null) {
                Capsule::table('users')->where('id', $this->siteUserId)->delete();
            }
            foreach (array_unique($this->attemptKeys) as $key) {
                Capsule::table('settings')->where('setting_key', $key)->delete();
            }
        } catch (\Throwable $e) {
            // la limpieza nunca debe enmascarar el resultado del test
        }

        putenv('MASTER_DB_NAME');
        unset($_ENV['MASTER_DB_NAME']);
        self::getDatabaseManager()->purge('master');
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    private function dbConfig(string $database): array
    {
        return [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'mysql-server',
            'database'  => $database,
            'username'  => $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'main_user',
            'password'  => $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? 'password',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ];
    }

    /** Crea la DB master de test (una sola vez por proceso) con el esquema completo. */
    private function ensureMasterDb(): void
    {
        if (self::$masterReady) {
            return;
        }
        Capsule::connection('default')->statement(
            'CREATE DATABASE IF NOT EXISTS `' . self::MASTER_DB . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        (new MigrationService())->migrate(Capsule::connection('master'), 'master');
        self::$masterReady = true;
    }

    private function rememberAttempts(string $ip, string $email): void
    {
        $this->attemptKeys[] = 'auth_attempts:' . sha1($ip . '|' . strtolower($email));
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

    private function mockAuth(): AuthService
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

    private function request(string $path, string $context): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', $path)
            ->withAttribute('context', $context);
    }

    /** @test */
    public function master_root_logs_into_child_site_without_site_user(): void
    {
        // La DB del sitio no tiene ningún usuario con ese email
        $this->assertNull(\App\Models\User::where('email', $this->email)->first());

        $this->rememberAttempts('127.0.0.1', $this->email);
        $auth = new AuthService();

        $this->assertTrue($auth->login($this->email, $this->password, '127.0.0.1', 'site:42'));
        $this->assertSame($this->rootId, (int) $_SESSION['user_id']);
        $this->assertSame('site:42', $_SESSION['context']);
        $this->assertSame('master', $_SESSION['auth_source']);
        $this->assertSame('master', $_SESSION['impersonated_by']);
        $this->assertSame('root', $_SESSION['user_role']);
        $this->assertSame('Root Master', $_SESSION['user_name']);
    }

    /** @test */
    public function child_login_rejects_wrong_master_password(): void
    {
        $this->rememberAttempts('127.0.0.1', $this->email);
        $auth = new AuthService();

        $this->assertFalse($auth->login($this->email, 'wrong-pass', '127.0.0.1', 'site:42'));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
        $this->assertArrayNotHasKey('auth_source', $_SESSION);
    }

    /** @test */
    public function master_context_never_uses_master_fallback(): void
    {
        // Mismas credenciales que en el test de sitio: en contexto master no hay fallback
        $this->rememberAttempts('127.0.0.1', $this->email);
        $auth = new AuthService();

        $this->assertFalse($auth->login($this->email, $this->password, '127.0.0.1', 'master'));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    /** @test */
    public function session_with_master_source_is_validated_against_master_connection(): void
    {
        $_SESSION = [
            'user_id' => $this->rootId,
            'user_name' => 'stale',
            'user_role' => 'stale',
            'context' => 'site:42',
            'auth_source' => 'master',
        ];
        $handler = $this->handler();

        $response = (new AuthMiddleware($this->mockAuth()))->process($this->request('/admin', 'site:42'), $handler);

        $this->assertTrue($handler->called);
        $this->assertSame(200, $response->getStatusCode());
        // Los datos de la sesión se refrescan desde la DB master
        $this->assertSame('Root Master', $_SESSION['user_name']);
        $this->assertSame('root', $_SESSION['user_role']);
    }

    /** @test */
    public function tampered_master_session_is_logged_out(): void
    {
        // El usuario existe en la DB del sitio pero la sesión dice auth_source=master:
        // no está en la DB master → logout forzado (fail-closed)
        $this->siteUserId = (int) Capsule::table('users')->insertGetId([
            'name' => 'Site Only',
            'email' => 'site-only-' . uniqid() . '@example.com',
            'password_hash' => password_hash('x', PASSWORD_BCRYPT),
            'role' => 'editor',
            'active' => 1,
        ]);
        $_SESSION = [
            'user_id' => $this->siteUserId,
            'context' => 'site:42',
            'auth_source' => 'master',
        ];
        $handler = $this->handler();

        $response = (new AuthMiddleware($this->mockAuth()))->process($this->request('/admin', 'site:42'), $handler);

        $this->assertFalse($handler->called);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringEndsWith('admin/login', $response->getHeaderLine('Location'));
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    /** @test */
    public function missing_master_connection_fails_closed(): void
    {
        // Apuntar la conexión master a una inexistente: no se puede validar → logout
        self::$capsule->addConnection($this->dbConfig('no_such_db_master_test'), 'master');
        self::getDatabaseManager()->purge('master');
        $_SESSION = [
            'user_id' => $this->rootId,
            'context' => 'site:42',
            'auth_source' => 'master',
        ];
        $handler = $this->handler();

        try {
            $response = (new AuthMiddleware($this->mockAuth()))->process($this->request('/admin', 'site:42'), $handler);
        } finally {
            // Restaurar la conexión válida para el resto de la suite
            self::$capsule->addConnection($this->dbConfig(self::MASTER_DB), 'master');
            self::getDatabaseManager()->purge('master');
        }

        $this->assertFalse($handler->called);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
}
