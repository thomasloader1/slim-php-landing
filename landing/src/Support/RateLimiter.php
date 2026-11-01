<?php

namespace App\Support;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Límite simple de intentos por clave (ej. IP) sobre la tabla rate_limits.
 * Sirve para formularios públicos sin sesión, como el pedido de /shop.
 */
final class RateLimiter
{
    /**
     * Registra un intento. @return bool true si está permitido, false si superó $max en la ventana.
     */
    public static function hit(string $key, int $max, int $seconds): bool
    {
        $key = substr(hash('sha256', $key), 0, 64);
        $now = date('Y-m-d H:i:s');

        return Capsule::connection()->transaction(function () use ($key, $max, $seconds, $now) {
            // Limpieza oportunista de ventanas vencidas
            Capsule::table('rate_limits')->where('reset_at', '<', $now)->delete();

            $row = Capsule::table('rate_limits')->where('key', $key)->lockForUpdate()->first();
            if (!$row) {
                Capsule::table('rate_limits')->insert([
                    'key' => $key, 'hits' => 1, 'reset_at' => date('Y-m-d H:i:s', time() + $seconds),
                ]);
                return true;
            }
            if ((int) $row->hits >= $max) {
                return false;
            }
            Capsule::table('rate_limits')->where('key', $key)->increment('hits');
            return true;
        });
    }
}
