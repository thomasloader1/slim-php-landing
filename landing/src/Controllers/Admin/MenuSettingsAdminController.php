<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class MenuSettingsAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $html = $this->view->make('admin/menu/settings', ['settings' => $settings])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $allowedKeys = ['menu_enabled', 'menu_header_text', 'menu_footer_text'];

        // menu_enabled viene como checkbox: presente=1, ausente=0
        $data['menu_enabled'] = isset($data['menu_enabled']) ? '1' : '0';

        foreach ($allowedKeys as $key) {
            $value = $data[$key] ?? '';
            Capsule::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
            );
        }

        return $response->withHeader('Location', url('admin/menu/settings'))->withStatus(302);
    }
}
