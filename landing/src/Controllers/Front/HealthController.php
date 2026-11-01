<?php

namespace App\Controllers\Front;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /healthz — usado por "Verificar dominio": confirma que un dominio llega a esta
 * app y qué contexto resuelve. No expone datos (solo master | site:<id>).
 */
class HealthController
{
    public function __invoke(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode([
            'ok'      => true,
            'context' => $request->getAttribute('context') ?? 'master',
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store');
    }
}
