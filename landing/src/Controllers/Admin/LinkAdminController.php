<?php

namespace App\Controllers\Admin;

use App\Models\Link;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LinkAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $links = Link::orderBy('id', 'desc')->get();
        $html = $this->view->make('admin/links/index', ['links' => $links])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $html = $this->view->make('admin/links/create')->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        Link::create([
            'title' => $data['title'] ?? '',
            'url'   => $data['url'] ?? '',
            'type'  => $data['type'] ?? 'url',
            'icon'  => $data['icon'] ?? 'fa-link',
            'color' => $data['color'] ?? '#ffffff',
            'active'=> isset($data['active']) ? 1 : 0
        ]);

        return $response->withHeader('Location', url('admin/links'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $link = Link::findOrFail($args['id']);
        $html = $this->view->make('admin/links/edit', ['link' => $link])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $link = Link::findOrFail($args['id']);
        $data = $request->getParsedBody();

        $link->update([
            'title' => $data['title'] ?? '',
            'url'   => $data['url'] ?? '',
            'type'  => $data['type'] ?? 'url',
            'icon'  => $data['icon'] ?? 'fa-link',
            'color' => $data['color'] ?? '#ffffff',
            'active'=> isset($data['active']) ? 1 : 0
        ]);

        return $response->withHeader('Location', url('admin/links'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        Link::destroy($args['id']);
        return $response->withHeader('Location', url('admin/links'))->withStatus(302);
    }
}
