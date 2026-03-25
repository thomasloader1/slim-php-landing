<?php

namespace App\Controllers\Admin;

use App\Models\MenuSection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MenuSectionAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $sections = MenuSection::orderBy('sort_order')->orderBy('id')->get();
        $html = $this->view->make('admin/menu/sections/index', ['sections' => $sections])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $html = $this->view->make('admin/menu/sections/create')->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $maxOrder = MenuSection::max('sort_order') ?? 0;

        MenuSection::create([
            'name'       => trim($data['name'] ?? ''),
            'icon'       => trim($data['icon'] ?? '') ?: null,
            'note'       => trim($data['note'] ?? '') ?: null,
            'note_type'  => in_array($data['note_type'] ?? '', ['none','info','preorder','time'])
                                ? $data['note_type'] : 'none',
            'sort_order' => $maxOrder + 1,
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/menu/sections'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $section = MenuSection::findOrFail($args['id']);
        $html = $this->view->make('admin/menu/sections/edit', ['section' => $section])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $section = MenuSection::findOrFail($args['id']);
        $data = $request->getParsedBody();

        $section->update([
            'name'      => trim($data['name'] ?? ''),
            'icon'      => trim($data['icon'] ?? '') ?: null,
            'note'      => trim($data['note'] ?? '') ?: null,
            'note_type' => in_array($data['note_type'] ?? '', ['none','info','preorder','time'])
                                ? $data['note_type'] : 'none',
            'active'    => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/menu/sections'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        MenuSection::destroy($args['id']);
        return $response->withHeader('Location', url('admin/menu/sections'))->withStatus(302);
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
            MenuSection::where('id', (int) $id)->update(['sort_order' => $index]);
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
