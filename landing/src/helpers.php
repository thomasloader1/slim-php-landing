<?php

// Genera URL absoluta respetando APP_BASE_PATH
if (!function_exists('url')) {
    function url(string $path = ''): string {
        $_rawBase = getenv('APP_BASE_PATH');
        $base = rtrim($_rawBase !== false ? $_rawBase : ($_ENV['APP_BASE_PATH'] ?? ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

// Verifica si el REQUEST_URI actual coincide con $path (soporta base path y wildcards)
if (!function_exists('request_is')) {
    function request_is(string $path): bool {
        $_rawBase2 = getenv('APP_BASE_PATH');
        $base  = rtrim($_rawBase2 !== false ? $_rawBase2 : ($_ENV['APP_BASE_PATH'] ?? ''), '/');
        $uri   = explode('?', $_SERVER['REQUEST_URI'] ?? '/')[0]; // quitar query string
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri  = $uri ?: '/';
        $path = '/' . ltrim($path, '/');
        if (str_contains($path, '*')) {
            $pattern = str_replace('*', '.*', preg_quote($path, '#'));
            return (bool) preg_match("#^{$pattern}#", $uri);
        }
        return $uri === $path;
    }
}

// ¿El usuario logueado puede usar esta sección del admin? (ver App\Support\Permissions)
if (!function_exists('can')) {
    function can(string $permission): bool {
        return \App\Services\AccessService::can($permission);
    }
}

// ¿La página pública /shop está disponible? (módulo Catálogo encendido + página publicada)
if (!function_exists('shop_is_public')) {
    function shop_is_public(array $settings): bool {
        return ($settings['module_menu_enabled'] ?? '1') === '1' && ($settings['menu_enabled'] ?? '0') === '1';
    }
}

if (!function_exists('is_light_color')) {
    /** ¿El color hex es claro? (luminancia percibida > 0.5). Inválido = oscuro. */
    function is_light_color(?string $hex): bool
    {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return false;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 > 0.5;
    }
}

if (!function_exists('bgContrastColor')) {
    /** Texto negro o blanco legible sobre el color dado (botones de acento, links con fondo). */
    function bgContrastColor(?string $hex): string
    {
        return is_light_color($hex) ? '#000000' : '#ffffff';
    }
}

if (!function_exists('overlay_opacity_percent')) {
    /**
     * Normaliza la opacidad del overlay de la landing a un entero 0-100.
     * Históricamente el seed/demo guardaban fracciones ("0.5") mientras el admin
     * y la landing esperan porcentaje ("50"): un "0.5" bloqueaba el form de Ajustes
     * (input step=1) y dejaba el overlay casi invisible (0.5/100).
     */
    function overlay_opacity_percent($value, int $default = 50): int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }
        $n = (float) $value;
        if ($n > 0 && $n <= 1 && str_contains((string) $value, '.')) {
            $n *= 100; // fracción heredada
        }
        return (int) max(0, min(100, round($n)));
    }
}

if (!function_exists('site_base_url')) {
    /**
     * URL base absoluta del sitio (sin "/" final) para canonical, OG, sitemap, robots y llms.txt.
     * Prioriza `seo_site_url` (dominio real configurado en /admin/seo); si no hay, la arma desde
     * el request respetando proxies TLS (X-Forwarded-Proto) y APP_BASE_PATH.
     */
    function site_base_url(array $settings): string
    {
        $configured = trim((string) ($settings['seo_site_url'] ?? ''));
        if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
            return rtrim($configured, '/');
        }

        $forwarded = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $https = $forwarded === 'https'
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
            $host = 'localhost'; // Host header manipulado: no reflejarlo en canonical/sitemap
        }

        return rtrim(($https ? 'https' : 'http') . '://' . $host . url(''), '/');
    }
}

if (!function_exists('absolute_url')) {
    /** Convierte una ruta del sitio (/uploads/x.jpg) en URL absoluta; deja intactas las http(s). */
    function absolute_url(?string $path, array $settings): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (str_starts_with($path, '//')) {
            return 'https:' . $path;
        }
        $base = site_base_url($settings);
        // seo_site_url ya incluye el subpath; url('') ya se agregó en el fallback
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('seo_text')) {
    /** Texto plano de una sola línea para metas/JSON-LD, recortado en palabra completa. */
    function seo_text(?string $html, int $max = 160): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " ,.;:-") . '…';
    }
}

if (!function_exists('seo_lang')) {
    /** es_AR → es-AR para <html lang> e inLanguage. */
    function seo_lang(array $settings): string
    {
        $locale = (string) ($settings['seo_locale'] ?? 'es_AR');
        return preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale) ? str_replace('_', '-', $locale) : 'es-AR';
    }
}

if (!function_exists('seo_title')) {
    /** "Página | Marca" sin repetir cuando ambas partes coinciden. */
    function seo_title(?string $page, array $settings): string
    {
        $page  = trim((string) $page);
        $brand = trim((string) ($settings['site_name'] ?? ''));
        if ($page === '' || mb_strtolower($page) === mb_strtolower($brand)) {
            return $brand !== '' ? $brand : ($page !== '' ? $page : 'Landing');
        }
        return $brand !== '' ? $page . ' | ' . $brand : $page;
    }
}

if (!function_exists('seo_indexable')) {
    /** El tenant permite indexación (no marcó "noindex" en /admin/seo). */
    function seo_indexable(array $settings): bool
    {
        return ($settings['seo_noindex'] ?? '0') !== '1';
    }
}

if (!function_exists('log_error')) {
    /**
     * Registra un error/evento en la tabla error_logs (ver App\Services\ErrorLogService).
     * Niveles: critical, error, warning, security, audit. Sin nivel explícito se
     * deduce del prefijo del mensaje ([SECURITY], [AUDIT]) o queda en 'error'.
     */
    function log_error(string $message, ?string $level = null, array $context = [], ?\Throwable $e = null): void
    {
        \App\Services\ErrorLogService::log(
            $level ?? \App\Services\ErrorLogService::levelFromMessage($message),
            $message,
            $context,
            $e
        );
    }
}
