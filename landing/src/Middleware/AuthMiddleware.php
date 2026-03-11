<?php

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    protected $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!$this->auth->isAuthenticated()) {
            $response = new SlimResponse();
            return $response->withHeader('Location', url('admin/login'))->withStatus(302);
        }

        return $handler->handle($request);
    }
}
