<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class RoleMiddleware implements MiddlewareInterface
{
    protected $requiredRole;

    public function __construct(string $requiredRole = 'admin')
    {
        $this->requiredRole = $requiredRole;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userRole = $_SESSION['user_role'] ?? 'editor';

        if ($userRole !== $this->requiredRole) {
            $response = new SlimResponse();
            return $response->withHeader('Location', '/admin')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
