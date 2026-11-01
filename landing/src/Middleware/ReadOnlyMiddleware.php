<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class ReadOnlyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getAttribute('demo_mode')) {
            $method = strtoupper($request->getMethod());
            if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
                $response = new SlimResponse();
                $response->getBody()->write(
                    json_encode(['error' => 'Modo demo: solo lectura'])
                );
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
            }
        }

        return $handler->handle($request);
    }
}
