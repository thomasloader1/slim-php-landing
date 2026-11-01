<?php

namespace App\Controllers\Admin;

use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Services\ProductCsvService;
use App\Services\ProductPriceService;
use App\Traits\AdminViewTrait;
use App\Traits\ProcessesImages;
use App\Traits\ReordersEntities;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Productos (tabla menu_items). La foto puede subirse o indicarse como link.
 */
class MenuItemAdminController
{
    use AdminViewTrait;
    use ProcessesImages;
    use ReordersEntities;

    private const BADGES = ['none', 'premium', 'preorder', 'special'];
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    private const IMAGE_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public function index(Request $request, Response $response): Response
    {
        $items    = MenuItem::with('section')->orderBy('sort_order')->orderBy('id')->get();
        $sections = MenuSection::active()->orderBy('sort_order')->get();
        return $this->render($response, 'admin/menu/items/index', compact('items', 'sections'));
    }

    public function create(Request $request, Response $response): Response
    {
        $sections = MenuSection::active()->orderBy('sort_order')->get();
        return $this->render($response, 'admin/menu/items/create', ['sections' => $sections]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $back = url('admin/menu/items/create');

        [$attrs, $error] = $this->collectAttributes($data, null);
        if ($error) {
            return $this->fail($response, $back, $error);
        }

        [$imageUrl, $error] = $this->resolveImage($request, $data, null);
        if ($error) {
            return $this->fail($response, $back, $error);
        }

        MenuItem::create($attrs + [
            'image_url'  => $imageUrl,
            'sort_order' => (MenuItem::max('sort_order') ?? 0) + 1,
        ]);

        return $response->withHeader('Location', url('admin/menu/items'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        $item     = MenuItem::findOrFail($args['id']);
        $sections = MenuSection::active()->orderBy('sort_order')->get();
        return $this->render($response, 'admin/menu/items/edit', compact('item', 'sections'));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $item = MenuItem::findOrFail($args['id']);
        $data = (array) $request->getParsedBody();
        $back = url('admin/menu/items/edit/' . $item->id);

        [$attrs, $error] = $this->collectAttributes($data, $item->id);
        if ($error) {
            return $this->fail($response, $back, $error);
        }

        [$imageUrl, $error] = $this->resolveImage($request, $data, $item->image_url);
        if ($error) {
            return $this->fail($response, $back, $error);
        }

        $item->update($attrs + ['image_url' => $imageUrl]);

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

    /**
     * Ajuste masivo de precios por porcentaje: a todos, a una categoría o a
     * los productos tildados en la lista.
     */
    public function adjustPrices(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $back = url('admin/menu/items');

        $raw = str_replace([' ', '%', ','], ['', '', '.'], (string) ($data['percent'] ?? ''));
        $percent = is_numeric($raw) ? (float) $raw : null;
        if ($percent === null || $percent == 0.0) {
            return $this->fail($response, $back, 'Ingresá un porcentaje distinto de 0 (ej: 10 para aumentar, -5 para bajar).');
        }
        if ($percent < ProductPriceService::MIN_PERCENT || $percent > ProductPriceService::MAX_PERCENT) {
            return $this->fail($response, $back, 'El porcentaje debe estar entre -90 % y 1000 %.');
        }

        $roundTo = (int) ($data['round_to'] ?? 0);
        if (!in_array($roundTo, ProductPriceService::ROUNDINGS, true)) {
            $roundTo = 0;
        }

        $ids = null;
        $sectionId = null;
        $scope = (string) ($data['scope'] ?? 'all');
        if ($scope === 'selected') {
            $ids = array_values(array_filter(array_map('intval', (array) ($data['ids'] ?? []))));
            if (!$ids) {
                return $this->fail($response, $back, 'Tildá al menos un producto de la lista o elegí otra opción en "Aplicar a".');
            }
        } elseif ($scope === 'section') {
            $sectionId = (int) ($data['section_id'] ?? 0);
            if (!MenuSection::whereKey($sectionId)->exists()) {
                return $this->fail($response, $back, 'Elegí una categoría.');
            }
        }

        $count = (new ProductPriceService())->adjust($ids, $sectionId, $percent, $roundTo, !empty($data['include_alt']));

        $label = ($percent > 0 ? '+' : '') . rtrim(rtrim(number_format($percent, 2, ',', ''), '0'), ',') . ' %';
        $_SESSION['flash_success'] = $count === 0
            ? "No cambió ningún precio con {$label}."
            : "Precios actualizados {$label}: " . ($count === 1 ? '1 producto' : "{$count} productos") . '.';
        return $response->withHeader('Location', $back)->withStatus(302);
    }

    protected function getReorderModel(): string
    {
        return MenuItem::class;
    }

    /**
     * Valida y arma los atributos del formulario (sin imagen ni orden).
     * @return array{0: array, 1: ?string} [atributos, error]
     */
    private function collectAttributes(array $data, ?int $ignoreId): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            return [[], 'El nombre del producto es obligatorio.'];
        }

        $sku = trim((string) ($data['sku'] ?? ''));
        if (mb_strlen($sku) > 64) {
            return [[], 'El código no puede superar 64 caracteres.'];
        }
        if ($sku !== '') {
            $taken = MenuItem::where('sku', $sku)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->first();
            if ($taken) {
                return [[], "El código \"{$sku}\" ya lo usa el producto \"{$taken->title}\"."];
            }
        }

        $stockRaw = trim((string) ($data['stock'] ?? ''));
        if ($stockRaw !== '' && !preg_match('/^\d{1,9}$/', $stockRaw)) {
            return [[], 'El stock debe ser un número entero (0 o más) o quedar vacío.'];
        }

        $optionalPrice = fn (string $key) => strlen(trim((string) ($data[$key] ?? ''))) > 0 ? (float) $data[$key] : null;

        return [[
            'section_id'    => !empty($data['section_id']) ? (int) $data['section_id'] : null,
            'sku'           => $sku !== '' ? $sku : null,
            'title'         => $title,
            'price'         => (float) ($data['price'] ?? 0),
            'stock'         => $stockRaw !== '' ? (int) $stockRaw : null,
            'price_label_1' => trim((string) ($data['price_label_1'] ?? '')) ?: null,
            'price_2'       => $optionalPrice('price_2'),
            'price_label_2' => trim((string) ($data['price_label_2'] ?? '')) ?: null,
            'price_3'       => $optionalPrice('price_3'),
            'price_label_3' => trim((string) ($data['price_label_3'] ?? '')) ?: null,
            'unit'          => trim((string) ($data['unit'] ?? '')) ?: null,
            'description'   => trim((string) ($data['description'] ?? '')),
            'flavors'       => trim((string) ($data['flavors'] ?? '')) ?: null,
            'gift_note'     => trim((string) ($data['gift_note'] ?? '')) ?: null,
            'badge_type'    => in_array($data['badge_type'] ?? '', self::BADGES, true) ? $data['badge_type'] : 'none',
            'active'        => isset($data['active']) ? 1 : 0,
        ], null];
    }

    /**
     * Decide la foto final. Prioridad: archivo subido > link nuevo > quitar > actual.
     * Si se reemplaza una foto subida antes, se borra el archivo viejo.
     * @return array{0: ?string, 1: ?string} [image_url, error]
     */
    private function resolveImage(Request $request, array $data, ?string $current): array
    {
        $file = $request->getUploadedFiles()['image_file'] ?? null;
        $link = trim((string) ($data['image_link'] ?? ''));

        if ($file instanceof UploadedFileInterface && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            [$url, $error] = $this->storeUpload($file);
            if ($error) {
                return [$current, $error];
            }
            $this->deleteUploadedFile($current ?? '');
            return [$url, null];
        }

        if ($link !== '' && $link !== $current) {
            if (!ProductCsvService::isHttpUrl($link) || mb_strlen($link) > 500) {
                return [$current, 'El link de la foto debe empezar con http:// o https:// (máx. 500 caracteres).'];
            }
            $this->deleteUploadedFile($current ?? '');
            return [$link, null];
        }

        if (!empty($data['clear_image'])) {
            $this->deleteUploadedFile($current ?? '');
            return [null, null];
        }

        return [$current, null];
    }

    /** @return array{0: ?string, 1: ?string} [url pública, error] */
    private function storeUpload(UploadedFileInterface $file): array
    {
        $invalid = 'La imagen debe ser JPG, PNG, GIF o WebP de hasta 2 MB.';

        if ($file->getError() !== UPLOAD_ERR_OK || !$this->validateImageSize($file, 2 * 1024 * 1024)) {
            return [null, $invalid];
        }
        $extension = strtolower(pathinfo((string) $file->getClientFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return [null, $invalid];
        }
        $realMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer((string) $file->getStream());
        if (!in_array($realMime, self::IMAGE_MIMES, true)) {
            return [null, $invalid];
        }

        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'menu_item_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;
        try {
            $file->moveTo($targetPath);
        } catch (\Throwable $e) {
            return [null, 'No se pudo guardar la imagen. Intentá de nuevo.'];
        }

        $this->processAndSaveImage($targetPath, 800, 800, 75);
        return [url('uploads/' . $filename), null];
    }

    private function fail(Response $response, string $location, string $message): Response
    {
        $_SESSION['flash_error'] = $message;
        return $response->withHeader('Location', $location)->withStatus(302);
    }
}
