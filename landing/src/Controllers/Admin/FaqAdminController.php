<?php

namespace App\Controllers\Admin;

use App\Models\FaqItem;
use App\Traits\AdminViewTrait;
use App\Traits\ReordersEntities;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FaqAdminController
{
    use AdminViewTrait;
    use ReordersEntities;

    public function index(Request $request, Response $response): Response
    {
        $items = FaqItem::orderBy('sort_order')->orderBy('id')->get();
        return $this->render($response, 'admin/seo/faq/index', ['items' => $items]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/seo/faq/create');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        FaqItem::create([
            'question'   => $data['question'] ?? '',
            'answer'     => $data['answer'] ?? '',
            'sort_order' => FaqItem::max('sort_order') + 1,
            'active'     => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/seo/faq'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $item = FaqItem::findOrFail($args['id']);
        return $this->render($response, 'admin/seo/faq/edit', ['item' => $item]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $item = FaqItem::findOrFail($args['id']);
        $data = $request->getParsedBody();

        $item->update([
            'question' => $data['question'] ?? '',
            'answer'   => $data['answer'] ?? '',
            'active'   => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/seo/faq'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        FaqItem::destroy($args['id']);
        return $response->withHeader('Location', url('admin/seo/faq'))->withStatus(302);
    }

    protected function getReorderModel(): string
    {
        return FaqItem::class;
    }
}
