<?php

namespace App\Controllers\Admin;

use App\Traits\ProcessesImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class MenuSettingsAdminController
{
    use ProcessesImages;
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
        $data  = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        // Checkboxes → '1' / '0'
        $data['menu_enabled']      = isset($data['menu_enabled'])      ? '1' : '0';
        $data['menu_show_payment'] = isset($data['menu_show_payment']) ? '1' : '0';
        $data['menu_bg_overlay']   = isset($data['menu_bg_overlay'])   ? '1' : '0';

        // Imagen de fondo del menú
        if (isset($files['menu_bg_image_file']) && $files['menu_bg_image_file']->getError() === UPLOAD_ERR_OK) {
            $file = $files['menu_bg_image_file'];
            if ($this->validateImageSize($file, 4 * 1024 * 1024)) {
                // Borrar imagen anterior si era local
                $oldUrl = Capsule::table('settings')
                    ->where('setting_key', 'menu_bg_image_url')->value('setting_value');
                if (!empty($oldUrl)) {
                    $this->deleteUploadedFile($oldUrl);
                }
                $extension  = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $filename   = 'menu_bg_' . time() . '.' . $extension;
                $targetPath = __DIR__ . '/../../../public/uploads/' . $filename;
                $file->moveTo($targetPath);
                $this->processAndSaveImage($targetPath, 1920, 1080, 80);
                $data['menu_bg_image_url'] = url('uploads/' . $filename);
            }
        }

        // Quitar imagen de fondo si se solicitó
        if (!empty($data['clear_menu_bg_image'])) {
            $oldUrl = Capsule::table('settings')
                ->where('setting_key', 'menu_bg_image_url')->value('setting_value');
            if (!empty($oldUrl)) {
                $this->deleteUploadedFile($oldUrl);
            }
            $data['menu_bg_image_url'] = '';
        }

        $allowedKeys = [
            'menu_enabled',
            'menu_header_text',
            'menu_footer_text',
            'menu_accent_color',
            'menu_bg_color',
            'menu_text_color',
            'menu_bg_image_url',
            'menu_bg_overlay',
            'menu_bg_overlay_opacity',
            'menu_brand_name',
            'menu_brand_subtitle',
            'menu_show_payment',
            'menu_payment_alias',
            'menu_payment_methods',
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

        return $response->withHeader('Location', url('admin/menu/settings'))->withStatus(302);
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
