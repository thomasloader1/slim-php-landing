<?php

namespace App\Controllers\Admin;

use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class ModuleAdminController
{
    use AdminViewTrait;

    public const MODULE_KEYS = [
        'module_title_enabled',
        'module_avatar_enabled',
        'module_links_enabled',
        'module_bio_enabled',
        'module_locations_enabled',
        'module_menu_enabled',
        'module_site_manager_enabled',
        'demo_mode',
    ];

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        return $this->render($response, 'admin/modules', ['settings' => $settings]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $moduleKeys = self::MODULE_KEYS;

        foreach ($moduleKeys as $key) {
            $value = isset($data[$key]) ? $data[$key] : '0';
            Capsule::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
            );
        }

        return $response->withHeader('Location', url('admin/modules'))->withStatus(302);
    }

}
