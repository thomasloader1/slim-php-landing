<?php

namespace Tests\Regression;

use Tests\TestCase;
use App\Middleware\DemoModeMiddleware;
use App\Middleware\ReadOnlyMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Regression tests for DemoModeMiddleware and ReadOnlyMiddleware.
 *
 * DemoModeMiddleware: PSR-15 middleware that reads the demo_mode setting
 * and sets a request attribute so all downstream code can check it.
 *
 * ReadOnlyMiddleware: PSR-15 middleware that blocks destructive HTTP methods
 * (POST/PUT/DELETE) when demo mode is active, returning a 403 JSON response.
 *
 * @see src/Middleware/DemoModeMiddleware.php
 * @see src/Middleware/ReadOnlyMiddleware.php
 */
class DemoModeReadOnlyTest extends TestCase
{
    // ────────────────────────────────────────────────────────────
    // DemoModeMiddleware — attribute injection
    // ────────────────────────────────────────────────────────────

    /** @test */
    public function demo_middleware_sets_attribute_true_when_demo_active()
    {
        $middleware = new DemoModeMiddleware(true);
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with('demo_mode', true)
            ->willReturnSelf();

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $handler);
    }

    /** @test */
    public function demo_middleware_sets_attribute_false_when_demo_inactive()
    {
        $middleware = new DemoModeMiddleware(false);
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with('demo_mode', false)
            ->willReturnSelf();

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $handler);
    }

    /** @test */
    public function demo_middleware_passes_through_modified_request()
    {
        // The middleware must pass the request WITH the attribute to the handler,
        // not the original request without it.
        $middleware = new DemoModeMiddleware(true);
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $modifiedRequest = $this->createMock(ServerRequestInterface::class);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with('demo_mode', true)
            ->willReturn($modifiedRequest);

        $handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($modifiedRequest))
            ->willReturn(new Response());

        $middleware->process($request, $handler);
    }

    /** @test */
    public function demo_middleware_shared_demo_mode_with_view_factory()
    {
        $viewFactory = $this->createMock(\Illuminate\View\Factory::class);
        $middleware = new DemoModeMiddleware(true, $viewFactory);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects($this->once())
            ->method('withAttribute')
            ->with('demo_mode', true)
            ->willReturnSelf();

        // The middleware MUST share 'demoMode' with the view factory
        // so all Blade templates (including admin layout) have access to it
        $viewFactory->expects($this->once())
            ->method('share')
            ->with('demoMode', true);

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $handler);
    }

    // ────────────────────────────────────────────────────────────
    // ReadOnlyMiddleware — blocks destructive methods when demo=ON
    // ────────────────────────────────────────────────────────────

    /** @test */
    public function read_only_blocks_post_when_demo_on()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('POST');

        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    /** @test */
    public function read_only_blocks_put_when_demo_on()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('PUT');

        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function read_only_blocks_delete_when_demo_on()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('DELETE');

        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function read_only_allows_get_when_demo_on()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('GET');

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function read_only_allows_head_when_demo_on()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('HEAD');

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function read_only_allows_post_when_demo_off()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        // When demo_mode attribute is false or absent, pass through
        $request->method('getAttribute')->with('demo_mode')->willReturn(false);
        $request->method('getMethod')->willReturn('POST');

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function read_only_allows_post_when_attribute_not_set()
    {
        // When the attribute 'demo_mode' was never set (e.g. middleware stack
        // misconfiguration or DemoModeMiddleware not installed), ReadOnlyMiddleware
        // should default to allowing the request through.
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        // Simulate attribute never being set — returns null by default
        $request->method('getAttribute')->with('demo_mode')->willReturn(null);
        $request->method('getMethod')->willReturn('POST');

        $handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function read_only_returns_json_error_body_on_403()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('POST');

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertSame('Modo demo: solo lectura', $decoded['error']);
    }

    /** @test */
    public function read_only_returns_valid_json_content_type_on_403()
    {
        $middleware = new ReadOnlyMiddleware();
        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->method('getAttribute')->with('demo_mode')->willReturn(true);
        $request->method('getMethod')->willReturn('DELETE');

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }
}
