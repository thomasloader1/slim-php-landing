<?php

namespace Tests\Unit\Controllers;

use App\Controllers\Admin\ErrorLogAdminController;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\TestCase;

class ErrorLogAdminControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Capsule::table('error_logs')->delete();
        Capsule::table('error_logs')->insert([
            ['level' => 'error',    'message' => 'csv roto',        'created_at' => '2026-11-01 10:00:00'],
            ['level' => 'security', 'message' => 'token reusado',   'created_at' => '2026-11-02 10:00:00'],
            ['level' => 'critical', 'message' => 'Explotó 100%_x',  'created_at' => '2026-11-03 10:00:00'],
        ]);
    }

    protected function tearDown(): void
    {
        Capsule::table('error_logs')->delete();
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        parent::tearDown();
    }

    /** @test */
    public function index_merges_sources_newest_first_and_reports_unreachable_ones(): void
    {
        $data = $this->index([]);

        $this->assertSame(['Explotó 100%_x', 'token reusado', 'csv roto'], array_map(fn ($r) => $r->message, $data['rows']));
        $this->assertSame('master', $data['rows'][0]->source);
        $this->assertSame('cliente.com', $data['status']['9']['label']);
        $this->assertNotNull($data['status']['9']['error']);
    }

    /** @test */
    public function index_filters_by_level_text_and_date(): void
    {
        $this->assertSame(['token reusado'], $this->messages(['level' => 'security']));
        // % y _ se buscan literalmente, no como comodines
        $this->assertSame(['Explotó 100%_x'], $this->messages(['q' => '%_']));
        $this->assertSame([], $this->messages(['q' => 'o_o']));
        $this->assertSame(['Explotó 100%_x', 'token reusado'], $this->messages(['from' => '2026-11-02']));
        // nivel inválido se ignora
        $this->assertCount(3, $this->messages(['level' => 'nope']));
    }

    /** @test */
    public function clear_empties_reachable_sources_and_reports_failures(): void
    {
        $controller = $this->controller();
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/admin/system/logs/clear')->withParsedBody([]);

        $response = $controller->clear($request, (new ResponseFactory())->createResponse());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(0, Capsule::table('error_logs')->count());
        $this->assertStringContainsString('cliente.com', $_SESSION['flash_error']);
    }

    private function messages(array $query): array
    {
        return array_map(fn ($r) => $r->message, $this->index($query)['rows']);
    }

    private function index(array $query): array
    {
        $captured = [];
        $controller = $this->controller();
        $controller->method('render')->willReturnCallback(function ($response, $view, $data) use (&$captured) {
            $captured = $data;
            return $response;
        });
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin/system/logs')->withQueryParams($query);
        $controller->index($request, (new ResponseFactory())->createResponse());
        return $captured;
    }

    private function controller(): ErrorLogAdminController
    {
        $controller = $this->createPartialMock(ErrorLogAdminController::class, ['sources', 'render']);
        $controller->method('sources')->willReturn([
            'master' => ['label' => 'Master', 'conn' => Capsule::connection('default'), 'error' => null],
            '9'      => ['label' => 'cliente.com', 'conn' => null, 'error' => 'No se pudo conectar a la base del sitio.'],
        ]);
        return $controller;
    }
}
