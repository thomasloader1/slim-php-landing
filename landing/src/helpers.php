<?php

// Genera URL absoluta respetando APP_BASE_PATH
if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

// Verifica si el REQUEST_URI actual coincide con $path (soporta base path y wildcards)
if (!function_exists('request_is')) {
    function request_is(string $path): bool {
        $base  = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/');
        $uri   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // quitar query string
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
