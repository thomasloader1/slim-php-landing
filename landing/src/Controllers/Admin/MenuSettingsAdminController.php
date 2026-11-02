<?php

namespace App\Controllers\Admin;

use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class MenuSettingsAdminController
{
    use AdminViewTrait;

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        return $this->render($response, 'admin/menu/settings', ['settings' => $settings]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data  = $request->getParsedBody();

        // Checkboxes → '1' / '0'
        $data['menu_enabled']      = isset($data['menu_enabled'])      ? '1' : '0';
        $data['menu_show_payment'] = isset($data['menu_show_payment']) ? '1' : '0';

        // Shop: la tienda necesita un WhatsApp válido para recibir los pedidos
        $data['shop_enabled'] = isset($data['shop_enabled']) ? '1' : '0';
        $flash = 'Configuración del Shop guardada.';
        if ($data['shop_enabled'] === '1') {
            $wa = (string) Capsule::table('settings')->where('setting_key', 'whatsapp_number')->value('setting_value');
            if (!\App\Support\WhatsApp::isValidNumber($wa)) {
                $data['shop_enabled'] = '0';
                $_SESSION['flash_error'] = 'Para habilitar la tienda primero cargá el número de WhatsApp en UX Builder → Botón de WhatsApp. El resto se guardó.';
            }
        }
        $data['shop_max_items'] = (string) max(1, min(500, (int) ($data['shop_max_items'] ?? 30)));

        $allowedKeys = [
            'menu_enabled',
            'menu_header_text',
            'menu_footer_text',
            'menu_brand_name',
            'menu_brand_subtitle',
            'menu_show_payment',
            'menu_payment_alias',
            'menu_payment_methods',
            'shop_enabled',
            'shop_max_items',
        ];

        foreach ($allowedKeys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            Capsule::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $data[$key] ?? '', 'updated_at' => date('Y-m-d H:i:s')]
            );
        }

        if (empty($_SESSION['flash_error'])) {
            $_SESSION['flash_success'] = $flash;
        }
        return $response->withHeader('Location', url('admin/menu/settings'))->withStatus(302);
    }

    /**
     * Un clic: publica /shop y habilita la tienda (carrito + pedidos por WhatsApp).
     * Necesita el número de WhatsApp de la landing (UX Builder).
     */
    public function enableShop(Request $request, Response $response): Response
    {
        $wa = (string) Capsule::table('settings')->where('setting_key', 'whatsapp_number')->value('setting_value');
        if (!\App\Support\WhatsApp::isValidNumber($wa)) {
            $_SESSION['flash_error'] = 'Para recibir pedidos primero cargá el número de WhatsApp en UX Builder → Botón de WhatsApp.';
            return $response->withHeader('Location', url('admin/builder'))->withStatus(302);
        }
        foreach (['menu_enabled' => '1', 'shop_enabled' => '1'] as $key => $value) {
            Capsule::table('settings')->updateOrInsert(['setting_key' => $key], ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
        }
        $_SESSION['flash_success'] = 'Tienda habilitada: en /shop ya se pueden elegir productos y hacer pedidos.';
        return $response->withHeader('Location', url('admin/menu/items'))->withStatus(302);
    }
}
