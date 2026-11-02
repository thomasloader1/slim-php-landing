<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Rutas del root (gestor de sitios, directorios, sistema):
 * solo en el contexto master y con rol root. En un sitio cliente responden 404
 * para no revelar que existen.
 */
class MasterOnlyMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getAttribute('context') !== TenantResolverMiddleware::CONTEXT_MASTER) {
            return (new SlimResponse())->withStatus(404);
        }

        if (!\App\Services\AccessService::isRoot()) {
            return (new SlimResponse())->withHeader('Location', url('admin'))->withStatus(302);
        }

        return $handler->handle($request);
    }
}
