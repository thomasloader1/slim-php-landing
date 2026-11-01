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
