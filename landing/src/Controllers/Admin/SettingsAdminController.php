<?php

namespace App\Controllers\Admin;

use App\Traits\AdminViewTrait;
use App\Traits\ProcessesImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class SettingsAdminController
{
    use AdminViewTrait;
    use ProcessesImages;

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        return $this->render($response, 'admin/settings', ['settings' => $settings]);
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

        // Extensiones de imagen permitidas
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico']; // SVG removido por seguridad: XSS almacenado

        // Asegurar que el directorio uploads/ existe
        $uploadDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $uploadLabels = ['avatar_file' => 'avatar', 'logo_file' => 'logo', 'bg_file' => 'imagen de fondo', 'favicon_file' => 'favicon'];
        $rejected = [];

        foreach ($uploadMap as $inputName => $settingKey) {
            if (isset($files[$inputName]) && !in_array($files[$inputName]->getError(), [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
                // Ej: supera upload_max_filesize de PHP
                $rejected[] = $uploadLabels[$inputName] . ' (no se pudo subir)';
                continue;
            }
            if (isset($files[$inputName]) && $files[$inputName]->getError() === UPLOAD_ERR_OK) {
                $file = $files[$inputName];

                // Validar tamaño máximo (5MB)
                if (!$this->validateImageSize($file, 5 * 1024 * 1024)) {
                    $rejected[] = $uploadLabels[$inputName] . ' (supera 5 MB)';
                    continue;
                }

                // Validar extensión
                $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions)) {
                    $rejected[] = $uploadLabels[$inputName] . ' (formato .' . $extension . ' no permitido)';
                    continue;
                }

                // Validar MIME type real con finfo (no confiar en el cliente)
                $realMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer((string) $file->getStream());
                $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon']; // image/svg+xml removido por seguridad: XSS almacenado
                if (!in_array($realMime, $allowedMimes)) {
                    $rejected[] = $uploadLabels[$inputName] . ' (el archivo no es una imagen válida)';
                    continue;
                }

                $filename = "{$settingKey}_" . time() . ".{$extension}";
                $targetPath = $uploadDir . "/{$filename}";

                // Mover archivo primero — si falla, el original se conserva
                try {
                    $file->moveTo($targetPath);
                } catch (\Exception $e) {
                    continue;
                }

                // Procesar imagen (redimensionar + comprimir) si no es favicon
                if (isset($maxDimensions[$inputName])) {
                    [$maxW, $maxH] = $maxDimensions[$inputName];
                    $processed = $this->processAndSaveImage($targetPath, $maxW, $maxH, 80);
                    // Si GD no pudo procesar, eliminar el archivo subido y no actualizar
                    if (!$processed) {
                        @unlink($targetPath);
                        $rejected[] = $uploadLabels[$inputName] . ' (no se pudo procesar la imagen)';
                        continue;
                    }
                }

                // Borrar archivo previo si era una imagen local subida (solo después del éxito)
                $oldValue = Capsule::table('settings')->where('setting_key', $settingKey)->value('setting_value') ?? '';
                if ($oldValue) {
                    $this->deleteUploadedFile($oldValue);
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

        if (array_key_exists('landing_bg_overlay_opacity', $data)) {
            $data['landing_bg_overlay_opacity'] = (string) overlay_opacity_percent($data['landing_bg_overlay_opacity']);
        }

        // La bio llega como HTML del editor: solo se guarda HTML de la lista blanca
        if (array_key_exists('landing_bio', $data)) {
            $data['landing_bio'] = \App\Support\HtmlSanitizer::bio((string) $data['landing_bio']);
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                Capsule::table('settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                );
            }
        }

        if ($rejected) {
            $_SESSION['flash_error'] = 'Se guardaron los cambios, pero no se subió: ' . implode(', ', $rejected) . '. Formatos: PNG, JPG, GIF, WEBP o ICO.';
        } else {
            $_SESSION['flash_success'] = 'Cambios guardados.';
        }

        return $response->withHeader('Location', url('admin/settings'))->withStatus(302);
    }

}
