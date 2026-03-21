<?php

namespace App\Controllers\Admin;

use App\Models\FaqItem;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FaqAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $items = FaqItem::orderBy('sort_order')->orderBy('id')->get();
        $html = $this->view->make('admin/seo/faq/index', ['items' => $items])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $html = $this->view->make('admin/seo/faq/create')->render();
        $response->getBody()->write($html);
        return $response;
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
        $html = $this->view->make('admin/seo/faq/edit', ['item' => $item])->render();
        $response->getBody()->write($html);
        return $response;
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

    public function reorder(Request $request, Response $response): Response
    {
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (!isset($data['order']) || !is_array($data['order'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid payload']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        foreach ($data['order'] as $index => $id) {
            FaqItem::where('id', (int) $id)->update(['sort_order' => $index]);
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
