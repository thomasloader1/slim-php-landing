<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class SettingsAdminController
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();

        $html = $this->view->make('admin/settings', ['settings' => $settings])->render();
        $response->getBody()->write($html);
        return $response;
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        // Allowed keys to update
        $allowedKeys = [
            'site_name', 'landing_title', 'landing_subtitle', 'landing_bio',
            'landing_accent_color', 'landing_bg_color', 'landing_text_color',
            'landing_avatar_url', 'landing_logo_url', 'landing_bg_image_url',
            'landing_bg_overlay', 'landing_bg_overlay_opacity',
            'seo_description', 'seo_keywords', 'seo_author',
            'seo_site_url', 'seo_og_image', 'seo_locale', 'seo_twitter_handle',
            'seo_schema_type', 'seo_business_type', 'seo_address', 'seo_noindex',
            'landing_maps_url', 'landing_maps_mode',
            'landing_accent_force'
        ];

        // Handle File Uploads
        $uploadMap = [
            'avatar_file' => 'landing_avatar_url',
            'logo_file'   => 'landing_logo_url',
            'bg_file'     => 'landing_bg_image_url'
        ];

        foreach ($uploadMap as $inputName => $settingKey) {
            if (isset($files[$inputName]) && $files[$inputName]->getError() === UPLOAD_ERR_OK) {
                $file = $files[$inputName];
                $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $filename = "{$settingKey}_" . time() . ".{$extension}";
                $targetPath = __DIR__ . "/../../../public/uploads/{$filename}";

                // Borrar archivo previo si era una imagen local subida
                $oldValue = Capsule::table('settings')->where('setting_key', $settingKey)->value('setting_value') ?? '';
                if ($oldValue) {
                    $this->deleteUploadedFile($oldValue);
                }

                $file->moveTo($targetPath);
                $data[$settingKey] = url("uploads/{$filename}");
            }
        }

        // Procesar flags de limpieza de imágenes
        $clearMap = [
            'clear_avatar' => 'landing_avatar_url',
            'clear_logo'   => 'landing_logo_url',
            'clear_bg'     => 'landing_bg_image_url',
        ];
        foreach ($clearMap as $flag => $settingKey) {
            if (!empty($data[$flag])) {
                // Borrar archivo físico antes de limpiar el registro en DB
                $oldValue = Capsule::table('settings')->where('setting_key', $settingKey)->value('setting_value') ?? '';
                if ($oldValue) {
                    $this->deleteUploadedFile($oldValue);
                }

                Capsule::table('settings')->updateOrInsert(
                    ['setting_key' => $settingKey],
                    ['setting_value' => '', 'updated_at' => date('Y-m-d H:i:s')]
                );
                unset($data[$settingKey]);
            }
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                Capsule::table('settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                );
            }
        }

        return $response->withHeader('Location', url('admin/settings'))->withStatus(302);
    }

    /**
     * Borra un archivo físico de uploads/ si la ruta es local (no URL externa).
     */
    private function deleteUploadedFile(string $settingValue): void
    {
        $urlPath = parse_url($settingValue, PHP_URL_PATH) ?? '';

        // Solo borrar si la ruta empieza con /uploads/ (ignorar URLs externas)
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
