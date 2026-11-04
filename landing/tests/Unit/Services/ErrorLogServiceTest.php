<?php

namespace Tests\Unit\Services;

use App\Handlers\DetailedErrorHandler;
use App\Services\ErrorLogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Psr\Log\NullLogger;
use Slim\CallableResolver;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class ErrorLogServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Capsule::table('error_logs')->delete();
        ErrorLogService::$purgeOdds = 0;
    }

    protected function tearDown(): void
    {
        Capsule::table('error_logs')->delete();
        ErrorLogService::$purgeOdds = 200;
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
        parent::tearDown();
    }

    /** @test */
    public function log_writes_row_with_request_and_exception_data(): void
    {
        $e = new \RuntimeException('falló algo', 42, new \LogicException('causa'));

        $ok = ErrorLogService::log('error', 'mensaje', ['pedido' => 7], $e, [
            'uri' => '/admin/x', 'method' => 'POST', 'ip' => '10.0.0.1', 'user_agent' => 'UA',
        ]);

        $this->assertTrue($ok);
        $row = Capsule::table('error_logs')->first();
        $this->assertSame('error', $row->level);
        $this->assertSame('mensaje', $row->message);
        $this->assertSame(\RuntimeException::class, $row->exception_class);
        $this->assertSame(42, (int) $row->code);
        $this->assertSame('tests/Unit/Services/ErrorLogServiceTest.php', $row->file);
        $this->assertSame('/admin/x', $row->uri);
        $this->assertSame('POST', $row->method);
        $this->assertSame('10.0.0.1', $row->ip);

        $ctx = json_decode($row->context, true);
        $this->assertSame(7, $ctx['pedido']);
        $this->assertArrayHasKey('trace', $ctx);
        $this->assertStringContainsString('LogicException: causa', $ctx['previous'][0]);
    }

    /** @test */
    public function sensitive_context_keys_are_redacted(): void
    {
        ErrorLogService::log('warning', 'x', [
            'body'    => ['email' => 'a@b.c', 'password' => 'secreto1', 'nested' => ['api_key' => 'k']],
            'headers' => ['Cookie' => 'PHPSESSID=abc', 'Authorization' => 'Bearer t', 'Accept' => 'text/html'],
        ]);

        $raw = Capsule::table('error_logs')->value('context');
        $this->assertStringNotContainsString('secreto1', $raw);
        $this->assertStringNotContainsString('PHPSESSID', $raw);
        $this->assertStringNotContainsString('Bearer', $raw);
        $ctx = json_decode($raw, true);
        $this->assertSame('a@b.c', $ctx['body']['email']);
        $this->assertSame('[REDACTED]', $ctx['body']['nested']['api_key']);
        $this->assertSame('text/html', $ctx['headers']['Accept']);
    }

    /** @test */
    public function session_user_is_attached_to_context(): void
    {
        $_SESSION['user_id'] = 5;
        $_SESSION['user_name'] = 'Ana';
        ErrorLogService::log('audit', 'evento');
        $ctx = json_decode(Capsule::table('error_logs')->value('context'), true);
        $this->assertSame(5, $ctx['user']['id']);
    }

    /** @test */
    public function unknown_level_falls_back_to_error(): void
    {
        ErrorLogService::log('banana', 'x');
        $this->assertSame('error', Capsule::table('error_logs')->value('level'));
    }

    /** @test */
    public function log_error_helper_infers_level_from_legacy_prefix(): void
    {
        log_error('[AuthController][SECURITY] token reusado');
        log_error('[SiteAdmin][AUDIT] entró');
        log_error('[ProductImport] csv roto');
        log_error('[X] algo', 'warning');

        $this->assertSame(
            ['security', 'audit', 'error', 'warning'],
            Capsule::table('error_logs')->orderBy('id')->pluck('level')->all()
        );
    }

    /** @test */
    public function failure_to_write_never_throws(): void
    {
        $broken = $this->createMock(Connection::class);
        $broken->method('table')->willThrowException(new \RuntimeException('DB caída'));

        $prev = ini_set('error_log', '/dev/null');
        try {
            $this->assertFalse(ErrorLogService::log('critical', 'x', [], null, [], $broken));
        } finally {
            ini_set('error_log', $prev ?: '');
        }
    }

    /** @test */
    public function purge_removes_only_entries_older_than_retention(): void
    {
        $old = date('Y-m-d H:i:s', time() - (ErrorLogService::RETENTION_DAYS + 1) * 86400);
        Capsule::table('error_logs')->insert([
            ['level' => 'error', 'message' => 'viejo', 'created_at' => $old],
            ['level' => 'error', 'message' => 'nuevo', 'created_at' => date('Y-m-d H:i:s')],
        ]);

        $this->assertSame(1, ErrorLogService::purge(Capsule::connection()));
        $this->assertSame(['nuevo'], Capsule::table('error_logs')->pluck('message')->all());
    }

    /** @test */
    public function opportunistic_purge_runs_when_odds_hit(): void
    {
        Capsule::table('error_logs')->insert([
            'level' => 'error', 'message' => 'viejo',
            'created_at' => date('Y-m-d H:i:s', time() - 60 * 86400),
        ]);
        ErrorLogService::$purgeOdds = 1;

        ErrorLogService::log('error', 'nuevo');

        $this->assertSame(['nuevo'], Capsule::table('error_logs')->pluck('message')->all());
    }

    /** @test */
    public function error_handler_stores_500_as_critical(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', 'http://localhost/admin/boom')
            ->withParsedBody(['name' => 'x', 'password' => 'secreto1']);

        $response = $this->handler()($request, new \RuntimeException('Explotó'), false, true, false);

        $this->assertSame(500, $response->getStatusCode());
        $row = Capsule::table('error_logs')->first();
        $this->assertNotNull($row);
        $this->assertSame('critical', $row->level);
        $this->assertSame('Explotó', $row->message);
        $this->assertSame('http://localhost/admin/boom', $row->uri);
        $this->assertStringNotContainsString('secreto1', $row->context);
        $this->assertSame(500, json_decode($row->context, true)['status']);
    }

    /** @test */
    public function error_handler_skips_404(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost/wp-login.php');

        $response = $this->handler()($request, new HttpNotFoundException($request), false, true, false);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(0, Capsule::table('error_logs')->count());
    }

    private function handler(): DetailedErrorHandler
    {
        $views = new class {
            public function make(string $view)
            {
                throw new \RuntimeException('sin vistas en test');
            }
        };
        // Evita ensuciar storage/logs real durante los tests
        return new class(new CallableResolver(), new ResponseFactory(), $views, new NullLogger()) extends DetailedErrorHandler {
            protected function writeToErrorLog(): void
            {
                $m = new \ReflectionMethod(DetailedErrorHandler::class, 'writeToDatabase');
                $m->setAccessible(true);
                $m->invoke($this);
            }
        };
    }
}
