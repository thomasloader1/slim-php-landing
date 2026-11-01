<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Illuminate\View\Factory as ViewFactory;

class DemoModeMiddleware implements MiddlewareInterface
{
    private bool $demoMode;

    private ?ViewFactory $viewFactory;

    public function __construct(bool $demoMode = false, ?ViewFactory $viewFactory = null)
    {
        $this->demoMode = $demoMode;
        $this->viewFactory = $viewFactory;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $request = $request->withAttribute('demo_mode', $this->demoMode);

        // Share globally with all Blade views so $demoMode is available
        // in every template (including admin layout) without each controller
        // having to pass it explicitly.
        if ($this->viewFactory) {
            $this->viewFactory->share('demoMode', $this->demoMode);
        }

        return $handler->handle($request);
    }
}
