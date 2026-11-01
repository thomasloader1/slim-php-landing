<?php

namespace App\Support;

/**
 * Convierte un link de Google Maps, coordenadas o una dirección en una URL
 * apta para <iframe> sin necesidad de API key ni de pegar el código "Insertar mapa".
 */
final class MapEmbed
{
    /** Hosts de embed que acepta el CSP (frame-src). */
    private const ALLOWED_EMBED_HOSTS = ['www.google.com', 'maps.google.com', 'google.com'];

    /** Extrae y valida el src de un <iframe> pegado por el usuario. */
    public static function srcFromIframe(?string $embedCode): ?string
    {
        if (!$embedCode || !preg_match('/src=["\']([^"\']+)["\']/i', $embedCode, $m)) {
            return null;
        }
        $src = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        if (parse_url($src, PHP_URL_SCHEME) !== 'https' || !in_array($host, self::ALLOWED_EMBED_HOSTS, true)) {
            return null;
        }
        return $src;
    }

    /**
     * Arma un src embebible a partir de un link de Google Maps y/o una dirección.
     * Prioridad: coordenadas del link > búsqueda (q=) del link > lugar (/place/) > dirección.
     * Los links cortos (maps.app.goo.gl) no exponen datos: se usa la dirección.
     */
    public static function fromUrlOrAddress(?string $url, ?string $address = null): ?string
    {
        $query = self::queryFromUrl((string) $url);
        if ($query === null && $address !== null && trim($address) !== '') {
            $query = trim($address);
        }
        if ($query === null && $url !== null && trim($url) !== '' && !preg_match('#^https?://#i', trim($url))) {
            // El usuario escribió una dirección en el campo URL
            $query = trim($url);
        }
        return $query === null ? null : self::fromQuery($query);
    }

    public static function fromQuery(string $query): string
    {
        return 'https://www.google.com/maps?q=' . rawurlencode($query) . '&output=embed';
    }

    private static function queryFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (!preg_match('/(^|\.)google\.[a-z.]+$/', $host)) {
            return null; // maps.app.goo.gl, goo.gl, otros: no se pueden resolver sin red
        }

        // .../@-34.6037,-58.3816,17z
        if (preg_match('/@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/', $url, $m)) {
            return $m[1] . ',' . $m[2];
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        foreach (['q', 'query', 'destination', 'daddr', 'll'] as $key) {
            if (!empty($params[$key]) && is_string($params[$key])) {
                return $params[$key];
            }
        }

        // .../maps/place/Obelisco,+Buenos+Aires/...
        if (preg_match('#/place/([^/@?]+)#', (string) parse_url($url, PHP_URL_PATH), $m)) {
            return str_replace('+', ' ', rawurldecode($m[1]));
        }

        return null;
    }
}
