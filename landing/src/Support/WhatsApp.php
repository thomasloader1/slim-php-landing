<?php

namespace App\Support;

/**
 * Construye los enlaces de WhatsApp a partir de un número y un mensaje.
 *
 * - web: https://web.whatsapp.com/send?phone=...&text=...  (WhatsApp Web, escritorio)
 * - app: https://wa.me/...?text=...                         (móvil: abre la app)
 *
 * web.whatsapp.com en un celular redirige a la página de descarga, por eso la
 * landing elige uno u otro según el dispositivo (modo "auto").
 */
final class WhatsApp
{
    public const OPEN_MODES = ['auto', 'web', 'app'];

    /** Deja solo dígitos ("+54 9 11 1234-5678" -> "5491112345678"). */
    public static function normalizeNumber(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);
        // Prefijo internacional "00" -> se descarta (00549... -> 549...)
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        return $digits;
    }

    /** Un número E.164 tiene entre 8 y 15 dígitos (incluyendo código de país). */
    public static function isValidNumber(?string $number): bool
    {
        $len = strlen(self::normalizeNumber($number));
        return $len >= 8 && $len <= 15;
    }

    public static function webUrl(string $number, ?string $message = null): string
    {
        $query = ['phone' => self::normalizeNumber($number)];
        if ($message !== null && trim($message) !== '') {
            $query['text'] = $message;
        }
        return 'https://web.whatsapp.com/send?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public static function appUrl(string $number, ?string $message = null): string
    {
        $url = 'https://wa.me/' . self::normalizeNumber($number);
        if ($message !== null && trim($message) !== '') {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
    }
}
