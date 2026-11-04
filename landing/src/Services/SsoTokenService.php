<?php

namespace App\Services;

use Illuminate\Database\Connection;

/**
 * Token de un solo uso para que el superadmin (master) entre al panel de un sitio.
 *
 * Formato: base64url(json{s:site_id, e:exp, n:nonce}) . "." . base64url(hmac_sha256)
 * - Clave HMAC derivada de TENANT_ENCRYPTION_KEY (master y sitios comparten la clave).
 * - Vence en 60 s. El nonce se registra en la DB master (tabla sso_nonces) al emitirlo
 *   y se borra al consumirlo: un token no se puede reusar.
 * - En una copia de sitio hijo el consume recibe la conexión `master` (MASTER_DB_*),
 *   porque el token vive en la master, no en la DB del sitio.
 */
class SsoTokenService
{
    public const TTL = 60;

    private string $key;

    public function __construct(?string $secret = null)
    {
        $secret = $secret ?? (getenv('TENANT_ENCRYPTION_KEY') ?: ($_ENV['TENANT_ENCRYPTION_KEY'] ?? ''));
        if ($secret === '') {
            throw new \RuntimeException('TENANT_ENCRYPTION_KEY no configurada: no se puede emitir SSO.');
        }
        $this->key = hash_hmac('sha256', 'landing-sso-v1', $secret, true);
    }

    public function issue(int $siteId, Connection $master): string
    {
        $nonce = bin2hex(random_bytes(16));
        $exp = time() + self::TTL;

        $master->table('sso_nonces')->insert([
            'nonce'      => $nonce,
            'expires_at' => date('Y-m-d H:i:s', $exp),
        ]);

        $payload = self::b64(json_encode(['s' => $siteId, 'e' => $exp, 'n' => $nonce]));
        return $payload . '.' . self::b64(hash_hmac('sha256', $payload, $this->key, true));
    }

    /** Valida y consume. Devuelve site_id o null si es inválido/vencido/reusado. */
    public function consume(string $token, int $expectedSiteId, Connection $master): ?int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$payload, $sig] = $parts;
        if (!hash_equals(self::b64(hash_hmac('sha256', $payload, $this->key, true)), $sig)) {
            return null;
        }

        $data = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        if (!is_array($data) || !isset($data['s'], $data['e'], $data['n'])) {
            return null;
        }
        if ((int) $data['e'] < time() || (int) $data['s'] !== $expectedSiteId) {
            return null;
        }
        if (!preg_match('/^[a-f0-9]{32}$/', (string) $data['n'])) {
            return null;
        }

        // Consumo atómico: solo una request puede borrar el nonce
        $deleted = $master->table('sso_nonces')->where('nonce', $data['n'])->delete();
        if ($deleted !== 1) {
            return null;
        }

        // Limpieza oportunista de nonces vencidos
        $master->table('sso_nonces')->where('expires_at', '<', date('Y-m-d H:i:s'))->delete();

        return (int) $data['s'];
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
