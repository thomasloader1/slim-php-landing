<?php

namespace App\Support;

/**
 * Normaliza links de imágenes cargados por el admin (logo, fondo, banners…).
 * Acepta https://…, rutas propias (/uploads/…) y "sitio.com/img.jpg" (se le agrega https://).
 */
final class ImageUrl
{
    /** @return array{0:string,1:bool} [valor normalizado, válido] */
    public static function normalize(string $value): array
    {
        $v = trim($value);
        if (str_starts_with($v, '//')) {
            $v = 'https:' . $v; // link sin esquema: //cdn.com/img.png
        } elseif ($v === '' || str_starts_with($v, '/')) {
            return [$v, true]; // vacío o archivo propio (/uploads/…)
        } elseif (!preg_match('#^[a-z][a-z0-9+.-]*:#i', $v) && preg_match('#^[\w-]+(\.[\w-]+)+(/|$)#', $v)) {
            $v = 'https://' . $v;
        }
        $ok = filter_var($v, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($v, PHP_URL_SCHEME)) === 'https'
            && mb_strlen($v) <= 1000;
        return [$v, $ok];
    }

    /** Devuelve el link normalizado o '' si no es válido. */
    public static function safe(string $value): string
    {
        [$v, $ok] = self::normalize($value);
        return $ok ? mb_substr($v, 0, 1000) : '';
    }
}
