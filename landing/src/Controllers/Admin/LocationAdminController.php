<?php

namespace App\Controllers\Admin;

use App\Models\Location;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LocationAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $locations = Location::orderBy('sort_order')->get();
        $html = $this->view->make('admin/locations/index', ['locations' => $locations])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $html = $this->view->make('admin/locations/create')->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        Location::create([
            'name'       => $data['name'] ?? '',
            'address'    => $data['address'] ?? null,
            'embed_code' => $data['embed_code'] ?? null,
            'url'        => $data['url'] ?? null,
            'mode'       => $data['mode'] ?? 'embed',
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/locations'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $location = Location::findOrFail($args['id']);
        $html = $this->view->make('admin/locations/edit', ['location' => $location])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $location = Location::findOrFail($args['id']);
        $data = $request->getParsedBody();

        $location->update([
            'name'       => $data['name'] ?? '',
            'address'    => $data['address'] ?? null,
            'embed_code' => $data['embed_code'] ?? null,
            'url'        => $data['url'] ?? null,
            'mode'       => $data['mode'] ?? 'embed',
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/locations'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Location::destroy($args['id']);
        return $response->withHeader('Location', url('admin/locations'))->withStatus(302);
    }

    public function reorder(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!isset($data['order']) || !is_array($data['order'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid payload']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        foreach ($data['order'] as $index => $id) {
            Location::where('id', (int) $id)->update(['sort_order' => $index]);
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
