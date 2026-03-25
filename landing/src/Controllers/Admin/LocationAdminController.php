<?php

namespace App\Controllers\Admin;

use App\Models\Location;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

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
        $settings  = Capsule::table('settings')
            ->whereIn('setting_key', ['locations_display', 'locations_columns'])
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $html = $this->view->make('admin/locations/index', compact('locations', 'settings'))->render();
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

        $maxOrder = Location::max('sort_order') ?? 0;

        Location::create([
            'name'             => trim($data['name'] ?? ''),
            'address'          => trim($data['address'] ?? '') ?: null,
            'whatsapp'         => trim($data['whatsapp'] ?? '') ?: null,
            'whatsapp_message' => trim($data['whatsapp_message'] ?? '') ?: null,
            'embed_code'       => trim($data['embed_code'] ?? '') ?: null,
            'url'              => trim($data['url'] ?? '') ?: null,
            'mode'             => in_array($data['mode'] ?? '', ['embed', 'button']) ? $data['mode'] : 'embed',
            'sort_order'       => $maxOrder + 1,
            'active'           => isset($data['active']) ? 1 : 0,
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
            'name'             => trim($data['name'] ?? ''),
            'address'          => trim($data['address'] ?? '') ?: null,
            'whatsapp'         => trim($data['whatsapp'] ?? '') ?: null,
            'whatsapp_message' => trim($data['whatsapp_message'] ?? '') ?: null,
            'embed_code'       => trim($data['embed_code'] ?? '') ?: null,
            'url'              => trim($data['url'] ?? '') ?: null,
            'mode'             => in_array($data['mode'] ?? '', ['embed', 'button']) ? $data['mode'] : 'embed',
            'active'           => isset($data['active']) ? 1 : 0,
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

    /** Guarda configuración de display (display mode + columns) */
    public function updateDisplay(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $display = in_array($data['locations_display'] ?? '', ['list', 'grid'])
            ? $data['locations_display'] : 'list';

        $columns = in_array((int)($data['locations_columns'] ?? 2), [1, 2, 3])
            ? (string)(int)$data['locations_columns'] : '2';

        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'locations_display'],
            ['setting_value' => $display, 'updated_at' => date('Y-m-d H:i:s')]
        );
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'locations_columns'],
            ['setting_value' => $columns, 'updated_at' => date('Y-m-d H:i:s')]
        );

        return $response->withHeader('Location', url('admin/locations'))->withStatus(302);
    }
}
