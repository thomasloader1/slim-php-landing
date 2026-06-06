<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $body = $request->getParsedBody();
            $token = $body['_token'] ?? '';

            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                $referer = $request->getHeaderLine('Referer');
                $redirectTo = $referer ?: '/admin';
                $response = new \Slim\Psr7\Response();
                return $response
                    ->withHeader('Location', $redirectTo . '?error=' . urlencode('Token de seguridad inválido. Intentalo de nuevo.'))
                    ->withStatus(302);
            }
        }

        return $handler->handle($request);
    }
}
