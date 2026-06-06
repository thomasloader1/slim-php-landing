<?php

namespace App\Traits;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Trait que agrega un método `reorder()` para ordenar entidades
 * vía drag & drop (AJAX). Cada controller debe implementar
 * `getReorderModel(): string` devolviendo el FQCN del modelo.
 */
trait ReordersEntities
{
    public function reorder(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!isset($data['order']) || !is_array($data['order'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid payload']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $modelClass = $this->getReorderModel();
        foreach ($data['order'] as $index => $id) {
            $modelClass::where('id', (int) $id)->update(['sort_order' => $index]);
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Devuelve el FQCN del modelo a reordenar.
     */
    abstract protected function getReorderModel(): string;
}
