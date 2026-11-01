<?php

namespace Tests\Regression;

use App\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;

/**
 * Regresión: el token CSRF rotaba en cada POST, así que un formulario abierto en
 * otra pestaña (o tras "volver atrás") fallaba al guardar con "Token inválido".
 * Además los reorder por fetch() no mandaban token y se descartaban en silencio.
 */
class CsrfTokenPerSessionTest extends TestCase
{
    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStatus(200);
            }
        };
    }

    private function post(array $body = [], array $headers = []): ServerRequestInterface
    {
        $req = (new ServerRequestFactory())->createServerRequest('POST', '/admin/settings')->withParsedBody($body);
        foreach ($headers as $k => $v) {
            $req = $req->withHeader($k, $v);
        }
        return $req;
    }

    protected function setUp(): void
    {
        $_SESSION = ['csrf_token' => str_repeat('a', 64)];
    }

    public function test_token_is_not_rotated_after_valid_form_post(): void
    {
        $mw = new CsrfMiddleware();
        $first = $mw->process($this->post(['_token' => str_repeat('a', 64)]), $this->handler());
        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(str_repeat('a', 64), $_SESSION['csrf_token']);

        // Segunda pestaña con el mismo token: también debe guardar
        $second = $mw->process($this->post(['_token' => str_repeat('a', 64)]), $this->handler());
        $this->assertSame(200, $second->getStatusCode());
    }

    public function test_ajax_with_header_token_passes(): void
    {
        $res = (new CsrfMiddleware())->process(
            $this->post([], ['X-CSRF-Token' => str_repeat('a', 64), 'Accept' => 'application/json']),
            $this->handler()
        );
        $this->assertSame(200, $res->getStatusCode());
    }

    public function test_ajax_without_token_gets_json_error_not_silent_redirect(): void
    {
        $res = (new CsrfMiddleware())->process($this->post([], ['Accept' => 'application/json']), $this->handler());
        $this->assertSame(403, $res->getStatusCode());
    }
}
