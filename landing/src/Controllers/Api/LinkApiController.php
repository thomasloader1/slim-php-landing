<?php

namespace App\Controllers\Api;

use App\Models\Link;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LinkApiController
{
    public function list(Request $request, Response $response): Response
    {
        $links = Link::active()->orderBy('sort_order')->get();
        
        $response->getBody()->write($links->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function detail(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $link = Link::find($id);

        if (!$link) {
            $response->getBody()->write(json_encode(['error' => 'Link not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write($link->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }
}
