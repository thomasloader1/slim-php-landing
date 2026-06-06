<?php

namespace App\Controllers\Admin;

use App\Models\Link;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LinkAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $links = Link::orderBy('id', 'desc')->get();
        return $this->render($response, 'admin/links/index', ['links' => $links]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->render($response, 'admin/links/create');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validación server-side
        $error = $this->validateLinkData($data);
        if ($error) {
            return $response->withHeader('Location', url('admin/links/create') . '?error=' . urlencode($error))->withStatus(302);
        }

        Link::create([
            'title'    => trim($data['title'] ?? ''),
            'url'      => trim($data['url'] ?? ''),
            'type'     => $data['type'] ?? 'url',
            'icon'     => $data['icon'] ?? 'fa-link',
            'color'    => $data['color'] ?? '#ffffff',
            'bg_color' => !empty($data['bg_color_enabled']) ? ($data['bg_color'] ?? null) : null,
            'active'   => isset($data['active']) ? 1 : 0
        ]);

        return $response->withHeader('Location', url('admin/links'))->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $args): Response
    {
        try {
            $link = Link::findOrFail($args['id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $response->withHeader('Location', url('admin/links') . '?error=' . urlencode('El enlace solicitado no existe.'))->withStatus(302);
        }

        return $this->render($response, 'admin/links/edit', ['link' => $link]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $link = Link::findOrFail($args['id']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $response->withHeader('Location', url('admin/links') . '?error=' . urlencode('El enlace solicitado no existe.'))->withStatus(302);
        }

        $data = $request->getParsedBody();

        // Validación server-side
        $error = $this->validateLinkData($data);
        if ($error) {
            return $response->withHeader('Location', url('admin/links/edit/' . $args['id']) . '?error=' . urlencode($error))->withStatus(302);
        }

        $link->update([
            'title'    => trim($data['title'] ?? ''),
            'url'      => trim($data['url'] ?? ''),
            'type'     => $data['type'] ?? 'url',
            'icon'     => $data['icon'] ?? 'fa-link',
            'color'    => $data['color'] ?? '#ffffff',
            'bg_color' => !empty($data['bg_color_enabled']) ? ($data['bg_color'] ?? null) : null,
            'active'   => isset($data['active']) ? 1 : 0
        ]);

        return $response->withHeader('Location', url('admin/links'))->withStatus(302);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $deleted = Link::destroy($args['id']);

        if ($deleted === 0) {
            return $response->withHeader('Location', url('admin/links') . '?error=' . urlencode('El enlace que intentaste eliminar no existe.'))->withStatus(302);
        }

        return $response->withHeader('Location', url('admin/links'))->withStatus(302);
    }

    /**
     * Valida los datos del formulario de enlace.
     *
     * @param array $data Datos del formulario
     * @return string|null Mensaje de error o null si es válido
     */
    private function validateLinkData(array $data): ?string
    {
        $title = trim($data['title'] ?? '');
        $url   = trim($data['url'] ?? '');
        $color = $data['color'] ?? '';
        $bgColor = $data['bg_color'] ?? '';

        // title: required, max 150
        if ($title === '') {
            return 'El título del enlace es obligatorio.';
        }
        if (mb_strlen($title) > 150) {
            return 'El título no puede superar los 150 caracteres.';
        }

        // url: required, valid URL
        if ($url === '') {
            return 'La URL del enlace es obligatoria.';
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'La URL ingresada no es válida.';
        }

        // color: if provided, must be valid hex
        if ($color !== '' && !preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            return 'El color de acento no tiene un formato hexadecimal válido.';
        }

        // bg_color: if provided, must be valid hex
        if ($bgColor !== '' && !preg_match('/^#[a-fA-F0-9]{6}$/', $bgColor)) {
            return 'El color de fondo no tiene un formato hexadecimal válido.';
        }

        return null;
    }
}
