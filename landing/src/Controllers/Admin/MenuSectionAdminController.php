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
        $data = (array) $request->getParsedBody();
        $name = self::cleanName($data['name'] ?? '');

        if ($error = $this->validateName($name, null)) {
            return $this->fail($response, url('admin/menu/sections/create'), $error);
        }

        $maxOrder = MenuSection::max('sort_order') ?? 0;

        MenuSection::create([
            'name'       => $name,
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
        $data = (array) $request->getParsedBody();
        $name = self::cleanName($data['name'] ?? '');

        if ($error = $this->validateName($name, $section->id)) {
            return $this->fail($response, url('admin/menu/sections/edit/' . $section->id), $error);
        }

        $section->update([
            'name'      => $name,
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

    /** Quita espacios de más: "  Pan   dulce " → "Pan dulce" */
    private static function cleanName($value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    /**
     * Nombre obligatorio y único. La comparación la hace MySQL (collation *_ci):
     * "Panadería", "panaderia" y "PANADERÍA" cuentan como la misma categoría.
     */
    private function validateName(string $name, ?int $ignoreId): ?string
    {
        if ($name === '') {
            return 'El nombre de la categoría es obligatorio.';
        }
        if (mb_strlen($name) > 150) {
            return 'El nombre no puede superar 150 caracteres.';
        }
        $taken = MenuSection::where('name', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->value('name');
        return $taken !== null ? "Ya existe una categoría llamada \"{$taken}\"." : null;
    }

    private function fail(Response $response, string $location, string $message): Response
    {
        $_SESSION['flash_error'] = $message;
        return $response->withHeader('Location', $location)->withStatus(302);
    }

    protected function getReorderModel(): string
    {
        return MenuSection::class;
    }
}
