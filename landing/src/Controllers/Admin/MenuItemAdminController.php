<?php

namespace App\Controllers\Admin;

use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Traits\ProcessesImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MenuItemAdminController
{
    use ProcessesImages;
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $items    = MenuItem::with('section')->orderBy('sort_order')->orderBy('id')->get();
        $sections = MenuSection::active()->orderBy('sort_order')->get();
        $html = $this->view->make('admin/menu/items/index', compact('items', 'sections'))->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function create(Request $request, Response $response): Response
    {
        $sections = MenuSection::active()->orderBy('sort_order')->get();
        $html = $this->view->make('admin/menu/items/create', ['sections' => $sections])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function store(Request $request, Response $response): Response
    {
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $maxOrder = MenuItem::max('sort_order') ?? 0;

        $imageUrl = null;
        if (isset($files['image_file']) && $files['image_file']->getError() === UPLOAD_ERR_OK) {
            $file = $files['image_file'];
            if ($this->validateImageSize($file, 2 * 1024 * 1024)) {
                $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $filename  = 'menu_item_' . time() . '.' . $extension;
                $targetPath = __DIR__ . '/../../../public/uploads/' . $filename;
                $file->moveTo($targetPath);
                $this->processAndSaveImage($targetPath, 800, 800, 75);
                $imageUrl = url('uploads/' . $filename);
            }
        }

        MenuItem::create([
            'section_id'    => !empty($data['section_id']) ? (int) $data['section_id'] : null,
            'title'         => trim($data['title'] ?? ''),
            'price'         => (float) ($data['price'] ?? 0),
            'price_label_1' => trim($data['price_label_1'] ?? '') ?: null,
            'price_2'       => strlen(trim($data['price_2'] ?? '')) > 0 ? (float) $data['price_2'] : null,
            'price_label_2' => trim($data['price_label_2'] ?? '') ?: null,
            'price_3'       => strlen(trim($data['price_3'] ?? '')) > 0 ? (float) $data['price_3'] : null,
            'price_label_3' => trim($data['price_label_3'] ?? '') ?: null,
            'unit'          => trim($data['unit'] ?? '') ?: null,
            'description'   => trim($data['description'] ?? ''),
            'flavors'       => trim($data['flavors'] ?? '') ?: null,
            'gift_note'     => trim($data['gift_note'] ?? '') ?: null,
            'badge_type'    => in_array($data['badge_type'] ?? '', ['none','premium','preorder','special'])
                                    ? $data['badge_type'] : 'none',
            'image_url'     => $imageUrl,
            'sort_order'    => $maxOrder + 1,
            'active'        => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/menu/items'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $item     = MenuItem::findOrFail($args['id']);
        $sections = MenuSection::active()->orderBy('sort_order')->get();
        $html = $this->view->make('admin/menu/items/edit', compact('item', 'sections'))->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $item  = MenuItem::findOrFail($args['id']);
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $imageUrl = $item->image_url;

        if (!empty($data['clear_image'])) {
            $this->deleteUploadedFile($imageUrl ?? '');
            $imageUrl = null;
        }

        if (isset($files['image_file']) && $files['image_file']->getError() === UPLOAD_ERR_OK) {
            $file = $files['image_file'];
            if ($this->validateImageSize($file, 2 * 1024 * 1024)) {
                $this->deleteUploadedFile($imageUrl ?? '');
                $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $filename  = 'menu_item_' . time() . '.' . $extension;
                $targetPath = __DIR__ . '/../../../public/uploads/' . $filename;
                $file->moveTo($targetPath);
                $this->processAndSaveImage($targetPath, 800, 800, 75);
                $imageUrl = url('uploads/' . $filename);
            }
        }

        $item->update([
            'section_id'    => !empty($data['section_id']) ? (int) $data['section_id'] : null,
            'title'         => trim($data['title'] ?? ''),
            'price'         => (float) ($data['price'] ?? 0),
            'price_label_1' => trim($data['price_label_1'] ?? '') ?: null,
            'price_2'       => strlen(trim($data['price_2'] ?? '')) > 0 ? (float) $data['price_2'] : null,
            'price_label_2' => trim($data['price_label_2'] ?? '') ?: null,
            'price_3'       => strlen(trim($data['price_3'] ?? '')) > 0 ? (float) $data['price_3'] : null,
            'price_label_3' => trim($data['price_label_3'] ?? '') ?: null,
            'unit'          => trim($data['unit'] ?? '') ?: null,
            'description'   => trim($data['description'] ?? ''),
            'flavors'       => trim($data['flavors'] ?? '') ?: null,
            'gift_note'     => trim($data['gift_note'] ?? '') ?: null,
            'badge_type'    => in_array($data['badge_type'] ?? '', ['none','premium','preorder','special'])
                                    ? $data['badge_type'] : 'none',
            'image_url'     => $imageUrl,
            'active'        => isset($data['active']) ? 1 : 0,
        ]);

        return $response->withHeader('Location', url('admin/menu/items'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $item = MenuItem::find($args['id']);
        if ($item) {
            $this->deleteUploadedFile($item->image_url ?? '');
            $item->delete();
        }
        return $response->withHeader('Location', url('admin/menu/items'))->withStatus(302);
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
            MenuItem::where('id', (int) $id)->update(['sort_order' => $index]);
        }

        $response->getBody()->write(json_encode(['ok' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function deleteUploadedFile(string $settingValue): void
    {
        $urlPath = parse_url($settingValue, PHP_URL_PATH) ?? '';
        if (!str_contains($urlPath, '/uploads/')) {
            return;
        }
        $filename = basename($urlPath);
        if ($filename === '' || $filename === '.') {
            return;
        }
        $filePath = __DIR__ . '/../../../public/uploads/' . $filename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
