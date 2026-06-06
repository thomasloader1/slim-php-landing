<?php

namespace App\Traits;

/**
 * Trait para procesamiento y optimización de imágenes con GD.
 * Redimensiona y comprime imágenes para minimizar uso de disco.
 */
trait ProcessesImages
{
    /**
     * Valida que el archivo no exceda el tamaño máximo.
     *
     * @return bool true si es válido
     */
    protected function validateImageSize($file, int $maxBytes = 2 * 1024 * 1024): bool
    {
        return $file->getSize() <= $maxBytes;
    }

    /**
     * Procesa una imagen: redimensiona y comprime.
     * Si GD falla, no hace nada (el archivo original queda intacto).
     *
     * @return bool true si se procesó, false si se usó fallback
     */
    protected function processAndSaveImage(string $filePath, int $maxW, int $maxH, int $jpegQuality = 80): bool
    {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return false;
        }

        [$srcW, $srcH, $type] = $imageInfo;

        $source = $this->createGdFromFile($filePath, $type);
        if (!$source) {
            return false;
        }

        // Calcular nuevas dimensiones manteniendo aspecto
        $newW = $srcW;
        $newH = $srcH;
        if ($srcW > $maxW || $srcH > $maxH) {
            $ratio = min($maxW / $srcW, $maxH / $srcH);
            $newW = (int) round($srcW * $ratio);
            $newH = (int) round($srcH * $ratio);
        }

        $dest = imagecreatetruecolor($newW, $newH);

        // Detectar si tiene transparencia (PNG/GIF)
        $hasAlpha = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF]);

        if ($hasAlpha) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefill($dest, 0, 0, $transparent);
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        imagedestroy($source);

        // Guardar según tipo
        if ($hasAlpha) {
            imagepng($dest, $filePath, 8);
        } else {
            imagejpeg($dest, $filePath, $jpegQuality);
        }

        imagedestroy($dest);
        return true;
    }

    /**
     * Borra un archivo físico de uploads/ si la ruta es local (no URL externa).
     */
    protected function deleteUploadedFile(string $settingValue): void
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

        $filePath = __DIR__ . '/../../public/uploads/' . $filename;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Crea un recurso GD desde archivo según su tipo IMAGETYPE_*.
     */
    protected function createGdFromFile(string $path, int $type): \GdImage|false
    {
        return match ($type) {
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default        => false,
        };
    }
}
