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
            // fetch()/AJAX manda el token por header (los botones "Probar conexión"
            // hacían POST sin token y siempre fallaban)
            $headerToken = $request->getHeaderLine('X-CSRF-Token');
            $token = $headerToken !== '' ? $headerToken : ($body['_token'] ?? '');
            if (!is_string($token)) {
                $token = '';
            }

            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                if ($headerToken !== '' || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
                    $response = new \Slim\Psr7\Response();
                    $response->getBody()->write(json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Recargá la página.']));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(403); // 419 no es estándar: Apache lo convierte en 500
                }
                $referer = $request->getHeaderLine('Referer');
                $redirectTo = $referer ?: '/admin';
                $_SESSION['flash_error'] = 'Token de seguridad inválido. Inténtelo de nuevo.';
                $response = new \Slim\Psr7\Response();
                return $response
                    ->withHeader('Location', $redirectTo)
                    ->withStatus(302);
            }
            // Token por sesión (patrón "synchronizer token" de OWASP): NO se rota en
            // cada POST. Rotarlo invalidaba los formularios abiertos en otras pestañas
            // y el "volver atrás" del navegador ("Token de seguridad inválido" al guardar).
            // Se regenera al iniciar sesión (AuthService::login / loginAs).
        }

        return $handler->handle($request);
    }
}
