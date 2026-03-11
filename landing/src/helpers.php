<?php

if (!function_exists('request_is')) {
    function request_is($path)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = '/' . ltrim($path, '/');
        
        if (strpos($path, '*') !== false) {
            $pattern = str_replace('*', '.*', $path);
            return preg_match("#^$pattern#", $uri);
        }
        
        return $uri === $path;
    }
}
