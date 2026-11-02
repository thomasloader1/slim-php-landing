<?php

namespace App\Services;

use App\Middleware\TenantResolverMiddleware;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Diagnóstico y reparación del error
 *   {"error":"Tenant resolution unavailable: encryption key not configured"}
 *
 * Reproduce la misma decisión que TenantResolverMiddleware para un dominio y propone
 * (o aplica, con --fix) el arreglo seguro:
 *   - el dominio es el sitio principal → se agrega a MASTER_DOMAINS en el .env
 *   - gestor de sitios activo, sin clave y sin sitios → se apaga el gestor
 *   - con sitios registrados y sin clave → NO se inventa una clave (las credenciales
 *     guardadas solo se descifran con la original): se indica cómo restaurarla.
 */
class TenantDoctorService
{
    public const OUTCOME_MASTER      = 'master';       // dominio en MASTER_DOMAINS
    public const OUTCOME_MASTER_OFF  = 'master_off';   // gestor de sitios apagado: todo es master
    public const OUTCOME_BLOCKED_KEY = 'blocked_key';  // 503: gestor activo sin clave
    public const OUTCOME_SITE        = 'site';         // sitio cliente registrado y activo
    public const OUTCOME_UNKNOWN     = 'unknown';      // 404: no es master ni sitio activo

    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? dirname(__DIR__, 2);
    }

    public function envPath(): string
    {
        return $this->root . '/.env';
    }

    /** Estado actual de la instalación (DB master + entorno). */
    public function state(): array
    {
        $module = false;
        $siteDomains = [];
        $sites = 0;
        try {
            $module = Capsule::table('settings')->where('setting_key', 'module_site_manager_enabled')->value('setting_value') === '1';
            if (Capsule::schema()->hasTable('sites')) {
                $sites = Capsule::table('sites')->count();
                $siteDomains = Capsule::table('sites')->where('status', 'active')->pluck('domain')
                    ->map(fn ($d) => TenantResolverMiddleware::normalizeHost((string) $d))->all();
            }
        } catch (\Throwable $e) {
            // Sin DB el middleware trata todo como master (modo reparación)
        }

        $enc = new EncryptionService();
        return [
            'module'         => $module,
            'sites'          => $sites,
            'site_domains'   => $siteDomains,
            'has_key'        => $enc->hasKey('v1') || $enc->hasKey('v2'),
            'master_domains' => TenantResolverMiddleware::masterDomains(),
            'app_env'        => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'),
        ];
    }

    /** Mismo criterio que TenantResolverMiddleware::process(). */
    public static function resolve(string $host, array $state): string
    {
        $host = TenantResolverMiddleware::normalizeHost($host);
        if (in_array($host, $state['master_domains'], true)) {
            return self::OUTCOME_MASTER;
        }
        if (!$state['module']) {
            return self::OUTCOME_MASTER_OFF;
        }
        if (!$state['has_key']) {
            return self::OUTCOME_BLOCKED_KEY;
        }
        return in_array($host, $state['site_domains'], true) ? self::OUTCOME_SITE : self::OUTCOME_UNKNOWN;
    }

    public static function describe(string $outcome): string
    {
        return [
            self::OUTCOME_MASTER      => 'OK: sitio principal (está en MASTER_DOMAINS).',
            self::OUTCOME_MASTER_OFF  => 'OK: sitio principal (gestor de sitios apagado).',
            self::OUTCOME_BLOCKED_KEY => 'ERROR 503 "encryption key not configured": el gestor de sitios está activo y no hay TENANT_ENCRYPTION_KEY.',
            self::OUTCOME_SITE        => 'OK: sitio cliente registrado.',
            self::OUTCOME_UNKNOWN     => 'ERROR 404 "No active site configured": no está en MASTER_DOMAINS ni es un sitio activo.',
        ][$outcome] ?? $outcome;
    }

    /**
     * Acciones para que $domain (y la instalación) funcionen.
     * @return list<array{type:string, detail:string, value?:string}>
     *   type: add_master | disable_module | manual
     */
    public static function plan(array $state, ?string $domain): array
    {
        $actions = [];
        $domain = $domain !== null && $domain !== '' ? TenantResolverMiddleware::normalizeHost($domain) : null;

        if ($domain !== null) {
            $outcome = self::resolve($domain, $state);
            $isSite = in_array($domain, $state['site_domains'], true);
            if (in_array($outcome, [self::OUTCOME_BLOCKED_KEY, self::OUTCOME_UNKNOWN], true) && !$isSite) {
                $value = implode(',', array_values(array_unique(array_merge(self::configuredMasterDomains($state), [$domain]))));
                $actions[] = ['type' => 'add_master', 'value' => $value, 'detail' => "Agregar {$domain} a MASTER_DOMAINS (.env) → MASTER_DOMAINS={$value}"];
            }
        }

        if ($state['module'] && !$state['has_key']) {
            if ($state['sites'] === 0) {
                $actions[] = ['type' => 'disable_module', 'detail' => 'Apagar el gestor de sitios (está activo, sin clave y sin sitios registrados).'];
            } else {
                $actions[] = ['type' => 'manual', 'detail' => "Hay {$state['sites']} sitio(s) registrados: restaurá en el .env la TENANT_ENCRYPTION_KEY ORIGINAL "
                    . '(una clave nueva no descifra sus credenciales). Compará con: php bin/console key:fingerprint'];
            }
        }

        return $actions;
    }

    /** MASTER_DOMAINS tal cual está configurado (sin los localhost que agrega el entorno de desarrollo). */
    private static function configuredMasterDomains(array $state): array
    {
        $raw = getenv('MASTER_DOMAINS');
        if ($raw === false) {
            $raw = $_ENV['MASTER_DOMAINS'] ?? null;
        }
        if ($raw === null) {
            return array_values(array_diff($state['master_domains'], ['127.0.0.1']));
        }
        return array_values(array_filter(array_map(
            fn ($d) => TenantResolverMiddleware::normalizeHost(trim($d)),
            explode(',', (string) $raw)
        )));
    }

    /** Aplica una acción. Devuelve líneas de log. */
    public function apply(array $action): array
    {
        switch ($action['type']) {
            case 'add_master':
                $log = [];
                $before = self::readEnvValue($this->envPath(), 'MASTER_DOMAINS');
                $runtime = getenv('MASTER_DOMAINS');
                $backup = self::writeEnvValue($this->envPath(), 'MASTER_DOMAINS', $action['value']);
                $log[] = "MASTER_DOMAINS={$action['value']} guardado en " . $this->envPath() . ($backup ? " (copia: {$backup})" : '');
                // Dotenv no pisa variables que ya vienen del servidor (Docker, panel del hosting)
                if ($runtime !== false && $runtime !== $before) {
                    $log[] = 'ATENCIÓN: MASTER_DOMAINS también está definido en las variables del servidor (Docker / panel del hosting) '
                        . 'y tiene prioridad sobre el .env. Cambialo también ahí.';
                }
                return $log;

            case 'disable_module':
                Capsule::table('settings')->updateOrInsert(['setting_key' => 'module_site_manager_enabled'], ['setting_value' => '0']);
                return ['module_site_manager_enabled = 0 (gestor de sitios apagado).'];
        }
        return ['Acción manual: ' . $action['detail']];
    }

    /** Valor de una clave en un archivo .env (null si no está). */
    public static function readEnvValue(string $path, string $key): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^\s*(?:export\s+)?' . preg_quote($key, '/') . '\s*=\s*(.*)$/', $line, $m)) {
                return trim($m[1], " \t\"'");
            }
        }
        return null;
    }

    /**
     * Escribe KEY=valor en el .env conservando el resto de las líneas.
     * Hace una copia previa y reemplaza el archivo de forma atómica. Devuelve la ruta de la copia.
     */
    public static function writeEnvValue(string $path, string $key, string $value): ?string
    {
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || preg_match('/[\r\n"]/', $value)) {
            throw new \InvalidArgumentException('Clave o valor inválido para el .env');
        }
        $lines = is_file($path) ? (file($path, FILE_IGNORE_NEW_LINES) ?: []) : [];
        $backup = null;
        // El .env tiene secretos: la copia y el archivo nuevo conservan sus permisos (ej. 600)
        $mode = is_file($path) ? (fileperms($path) & 0777) : 0600;
        if (is_file($path)) {
            $backup = $path . '.bak-' . date('YmdHis');
            if (!copy($path, $backup)) {
                throw new \RuntimeException("No se pudo crear la copia {$backup}");
            }
            @chmod($backup, $mode);
        }

        $found = false;
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*(?:export\s+)?' . preg_quote($key, '/') . '\s*=/', $line)) {
                $lines[$i] = "{$key}={$value}";
                $found = true;
            }
        }
        if (!$found) {
            $lines[] = "{$key}={$value}";
        }

        $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
        if (file_put_contents($tmp, implode("\n", $lines) . "\n") === false || !@chmod($tmp, $mode) || !rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException("No se pudo escribir {$path}");
        }
        return $backup;
    }
}
