<?php

namespace App\Controllers\Admin;

use App\Traits\AdminViewTrait;
use App\Traits\ProcessesImages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class FaviconAdminController
{
    use AdminViewTrait;
    use ProcessesImages;

    /** Carpeta física donde se guardan los favicons */
    private string $faviconDir;

    /** URL pública base para los favicons */
    private string $faviconUrlBase;

    /** Tamaños de favicon a generar [nombre => [ancho, alto]] */
    private const SIZES = [
        'favicon-16x16.png'          => [16, 16],
        'favicon-32x32.png'          => [32, 32],
        'apple-touch-icon.png'       => [180, 180],
        'android-chrome-192x192.png' => [192, 192],
        'android-chrome-512x512.png' => [512, 512],
    ];

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
        $this->faviconDir     = __DIR__ . '/../../../public/favicons';
        $this->faviconUrlBase = 'favicons';
    }

    /**
     * GET /admin/favicon — Formulario de subida
     */
    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')
            ->whereIn('setting_key', [
                'favicon_version',
                'landing_favicon_url',
                'site_name',
                'landing_accent_color',
            ])
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        // Verificar si ya existen favicons generados
        $hasGenerated = file_exists($this->faviconDir . '/favicon-32x32.png');

        return $this->render($response, 'admin/favicon', [
            'settings'     => $settings,
            'hasGenerated' => $hasGenerated,
            'sizes'        => self::SIZES,
        ]);
    }

    /**
     * POST /admin/favicon — Procesar subida y generar variantes
     */
    public function upload(Request $request, Response $response): Response
    {
        $files = $request->getUploadedFiles();

        if (!isset($files['favicon_source']) || $files['favicon_source']->getError() !== UPLOAD_ERR_OK) {
            return $this->redirect($response, 'admin/favicon', 'No se recibió un archivo válido.');
        }

        $file = $files['favicon_source'];

        // Validar tipo MIME
        $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
        $mime = $file->getClientMediaType();
        if (!in_array($mime, $allowedMimes)) {
            return $this->redirect($response, 'admin/favicon', 'Formato no permitido. Usá PNG, JPG, GIF o WebP.');
        }

        // Guardar archivo temporal
        $tmpPath = $this->faviconDir . '/tmp_source_' . time();
        $file->moveTo($tmpPath);

        // Validar que sea imagen cuadrada (relación de aspecto 1:1) — tolerancia 5%
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            @unlink($tmpPath);
            return $this->redirect($response, 'admin/favicon', 'El archivo no es una imagen válida.');
        }

        [$srcW, $srcH] = $imageInfo;
        $ratio = $srcW / max($srcH, 1);
        if ($ratio < 0.95 || $ratio > 1.05) {
            @unlink($tmpPath);
            return $this->redirect($response, 'admin/favicon', 'La imagen debe ser cuadrada (relación 1:1). Recibida: ' . $srcW . 'x' . $srcH);
        }

        // Crear imagen fuente con GD
        $sourceImage = $this->createGdFromFile($tmpPath, $imageInfo[2]);
        @unlink($tmpPath);

        if (!$sourceImage) {
            return $this->redirect($response, 'admin/favicon', 'No se pudo procesar la imagen. Verificá que la extensión GD esté habilitada.');
        }

        // Crear directorio si no existe
        if (!is_dir($this->faviconDir)) {
            mkdir($this->faviconDir, 0755, true);
        }

        // Limpiar favicons previos
        $this->cleanPreviousFavicons();

        // Generar cada tamaño
        foreach (self::SIZES as $filename => [$width, $height]) {
            $this->resizeAndSave($sourceImage, $srcW, $srcH, $width, $height, $this->faviconDir . '/' . $filename);
        }

        imagedestroy($sourceImage);

        // Generar site.webmanifest
        $this->generateWebManifest();

        // Actualizar versión en settings (cache busting)
        $version = (string) time();
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'favicon_version'],
            ['setting_value' => $version, 'updated_at' => date('Y-m-d H:i:s')]
        );

        // Actualizar landing_favicon_url para compatibilidad
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'landing_favicon_url'],
            ['setting_value' => url($this->faviconUrlBase . '/favicon-32x32.png'), 'updated_at' => date('Y-m-d H:i:s')]
        );

        return $this->redirect($response, 'admin/favicon', null, 'Favicons generados correctamente.');
    }

    /**
     * POST /admin/favicon/delete — Eliminar todos los favicons
     */
    public function delete(Request $request, Response $response): Response
    {
        $this->cleanPreviousFavicons();

        // Limpiar settings
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'favicon_version'],
            ['setting_value' => '', 'updated_at' => date('Y-m-d H:i:s')]
        );
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => 'landing_favicon_url'],
            ['setting_value' => '', 'updated_at' => date('Y-m-d H:i:s')]
        );

        return $this->redirect($response, 'admin/favicon', null, 'Favicons eliminados.');
    }

    // ─── Métodos privados ────────────────────────────────────────

    /**
     * Redimensiona la imagen y la guarda como PNG.
     */
    private function resizeAndSave(\GdImage $source, int $srcW, int $srcH, int $dstW, int $dstH, string $outputPath): void
    {
        $dest = imagecreatetruecolor($dstW, $dstH);

        // Preservar transparencia
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        imagepng($dest, $outputPath, 9); // Máxima compresión
        imagedestroy($dest);
    }

    /**
     * Genera el archivo site.webmanifest en la carpeta de favicons.
     */
    private function generateWebManifest(): void
    {
        $siteName = Capsule::table('settings')
            ->where('setting_key', 'site_name')
            ->value('setting_value') ?? 'Mi Sitio';

        $accentColor = Capsule::table('settings')
            ->where('setting_key', 'landing_accent_color')
            ->value('setting_value') ?? '#fec771';

        $bgColor = Capsule::table('settings')
            ->where('setting_key', 'landing_bg_color')
            ->value('setting_value') ?? '#020617';

        $manifest = [
            'name'             => $siteName,
            'short_name'       => $siteName,
            'icons'            => [
                [
                    'src'   => url($this->faviconUrlBase . '/android-chrome-192x192.png'),
                    'sizes' => '192x192',
                    'type'  => 'image/png',
                ],
                [
                    'src'   => url($this->faviconUrlBase . '/android-chrome-512x512.png'),
                    'sizes' => '512x512',
                    'type'  => 'image/png',
                ],
            ],
            'theme_color'      => $accentColor,
            'background_color' => $bgColor,
            'display'          => 'standalone',
        ];

        file_put_contents(
            $this->faviconDir . '/site.webmanifest',
            json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Elimina todos los archivos generados previamente.
     */
    private function cleanPreviousFavicons(): void
    {
        $filesToDelete = array_keys(self::SIZES);
        $filesToDelete[] = 'site.webmanifest';

        foreach ($filesToDelete as $file) {
            $path = $this->faviconDir . '/' . $file;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Redirección con mensaje flash (usando query string simple).
     */
    private function redirect(Response $response, string $path, ?string $error = null, ?string $success = null): Response
    {
        $query = '';
        if ($error) {
            $query = '?error=' . urlencode($error);
        } elseif ($success) {
            $query = '?success=' . urlencode($success);
        }

        return $response
            ->withHeader('Location', url($path) . $query)
            ->withStatus(302);
    }
}
