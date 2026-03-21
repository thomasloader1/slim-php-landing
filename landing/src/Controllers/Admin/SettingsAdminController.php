<?php

namespace App\Controllers\Admin;

use App\Traits\ProcessesImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class SettingsAdminController
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
            'landing_accent_force',
            'landing_favicon_url',
            'landing_links_display',
            'landing_logo_size'
        ];

        // Handle File Uploads
        $uploadMap = [
            'avatar_file'  => 'landing_avatar_url',
            'logo_file'    => 'landing_logo_url',
            'bg_file'      => 'landing_bg_image_url',
            'favicon_file' => 'landing_favicon_url'
        ];

        // Dimensiones máximas por tipo de imagen
        $maxDimensions = [
            'avatar_file'  => [400, 400],
            'logo_file'    => [600, 600],
            'bg_file'      => [1920, 1920],
        ];

        foreach ($uploadMap as $inputName => $settingKey) {
            if (isset($files[$inputName]) && $files[$inputName]->getError() === UPLOAD_ERR_OK) {
                $file = $files[$inputName];

                // Validar tamaño máximo (5MB)
                if (!$this->validateImageSize($file, 5 * 1024 * 1024)) {
                    continue;
                }

                $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
                $filename = "{$settingKey}_" . time() . ".{$extension}";
                $targetPath = __DIR__ . "/../../../public/uploads/{$filename}";

                // Borrar archivo previo si era una imagen local subida
                $oldValue = Capsule::table('settings')->where('setting_key', $settingKey)->value('setting_value') ?? '';
                if ($oldValue) {
                    $this->deleteUploadedFile($oldValue);
                }

                $file->moveTo($targetPath);

                // Procesar imagen (redimensionar + comprimir) si no es favicon
                if (isset($maxDimensions[$inputName])) {
                    [$maxW, $maxH] = $maxDimensions[$inputName];
                    $this->processAndSaveImage($targetPath, $maxW, $maxH, 80);
                }

                $data[$settingKey] = url("uploads/{$filename}");
            }
        }

        // Procesar flags de limpieza de imágenes
        $clearMap = [
            'clear_avatar'  => 'landing_avatar_url',
            'clear_logo'    => 'landing_logo_url',
            'clear_bg'      => 'landing_bg_image_url',
            'clear_favicon' => 'landing_favicon_url',
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

        //$this->clearBladeCache();

        return $response->withHeader('Location', url('admin/settings'))->withStatus(302);
    }

    /**
     * Limpia la caché de templates Blade compilados.
     */
    private function clearBladeCache(): void
    {
        $cacheDir = __DIR__ . '/../../../storage/cache';
        $files = glob($cacheDir . '/*.php');
        if ($files) {
            array_map('unlink', $files);
        }
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
