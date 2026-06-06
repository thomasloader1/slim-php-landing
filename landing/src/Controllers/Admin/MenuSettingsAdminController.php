<?php

namespace App\Controllers\Admin;

use App\Traits\AdminViewTrait;
use App\Traits\ProcessesImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class MenuSettingsAdminController
{
    use AdminViewTrait;
    use ProcessesImages;

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        return $this->render($response, 'admin/menu/settings', ['settings' => $settings]);
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

            // Validar tamaño máximo (4MB)
            if (!$this->validateImageSize($file, 4 * 1024 * 1024)) {
                goto _skip_menu_bg;
            }

            // Validar extensión
            $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
            $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                goto _skip_menu_bg;
            }

            // Validar MIME type real con finfo
            $realMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer((string) $file->getStream());
            $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
            if (!in_array($realMime, $allowedMimes)) {
                goto _skip_menu_bg;
            }

            // Asegurar que el directorio uploads/ existe
            $uploadDir = __DIR__ . '/../../../public/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Borrar imagen anterior si era local
            $oldUrl = Capsule::table('settings')
                ->where('setting_key', 'menu_bg_image_url')->value('setting_value');
            if (!empty($oldUrl)) {
                $this->deleteUploadedFile($oldUrl);
            }

            $filename   = 'menu_bg_' . time() . '.' . $extension;
            $targetPath = $uploadDir . '/' . $filename;

            try {
                $file->moveTo($targetPath);
            } catch (\Exception $e) {
                goto _skip_menu_bg;
            }

            $this->processAndSaveImage($targetPath, 1920, 1080, 80);
            $data['menu_bg_image_url'] = url('uploads/' . $filename);
        }
        _skip_menu_bg:

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

}
