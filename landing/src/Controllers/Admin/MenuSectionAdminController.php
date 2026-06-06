<?php

namespace App\Controllers\Admin;

use App\Models\MenuSection;
use App\Traits\AdminViewTrait;
use App\Traits\ReordersEntities;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MenuSectionAdminController
{
    use AdminViewTrait;
    use ReordersEntities;

    public function index(Request $request, Response $response): Response
    {
        $sections = MenuSection::orderBy('sort_order')->orderBy('id')->get();
        return $this->render($response, 'admin/menu/sections/index', ['sections' => $sections]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/menu/sections/create');
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
        return $this->render($response, 'admin/menu/sections/edit', ['section' => $section]);
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

    protected function getReorderModel(): string
    {
        return MenuSection::class;
    }
}
