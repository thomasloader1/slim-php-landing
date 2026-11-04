<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Throwable;

/**
 * Registro central de errores y eventos en la tabla `error_logs`.
 *
 * - Escribe en la conexión activa: la DB del sitio si el request resolvió
 *   un tenant, si no la master.
 * - Nunca rompe la app: si la tabla no existe o la DB no responde, cae a
 *   error_log() de PHP como antes.
 * - Purga oportunista: ~1 de cada PURGE_ODDS escrituras borra lo que tenga
 *   más de RETENTION_DAYS días.
 */
final class ErrorLogService
{
    public const LEVELS = ['critical', 'error', 'warning', 'security', 'audit'];
    public const RETENTION_DAYS = 30;
    private const PURGE_ODDS = 200;
    private const MAX_MESSAGE = 10000;
    private const MAX_TRACE = 20000;
    private const REDACT_KEYS = '/pass|contraseña|secret|token|key|authorization|cookie|csrf|nonce/i';

    /** Evita recursión si escribir el log dispara otro log. */
    private static bool $writing = false;

    /** Probabilidad de purga (tests la fijan a 1 para forzarla, 0 la apaga). */
    public static int $purgeOdds = self::PURGE_ODDS;

    /**
     * @param array $context datos extra (se sanean y guardan como JSON)
     * @param array $request uri/method/ip/user_agent explícitos; por defecto salen de $_SERVER
     * @return bool true si quedó en la tabla
     */
    public static function log(
        string $level,
        string $message,
        array $context = [],
        ?Throwable $e = null,
        array $request = [],
        ?Connection $connection = null
    ): bool {
        if (self::$writing) {
            return false;
        }
        self::$writing = true;
        try {
            $row = self::buildRow($level, $message, $context, $e, $request);
            $conn = $connection ?? Capsule::connection();
            $conn->table('error_logs')->insert($row);
            self::maybePurge($conn);
            return true;
        } catch (Throwable $fail) {
            error_log('[' . strtoupper($level) . '] ' . $message . ($e ? ' (' . get_class($e) . ' en ' . $e->getFile() . ':' . $e->getLine() . ')' : ''));
            return false;
        } finally {
            self::$writing = false;
        }
    }

    /** Borra registros más viejos que RETENTION_DAYS. @return int filas borradas */
    public static function purge(Connection $conn): int
    {
        return $conn->table('error_logs')
            ->where('created_at', '<', date('Y-m-d H:i:s', time() - self::RETENTION_DAYS * 86400))
            ->delete();
    }

    /** Nivel según el prefijo histórico de los mensajes ([SECURITY], [AUDIT]). */
    public static function levelFromMessage(string $message, string $default = 'error'): string
    {
        if (stripos($message, '[SECURITY]') !== false) {
            return 'security';
        }
        if (stripos($message, '[AUDIT]') !== false) {
            return 'audit';
        }
        return $default;
    }

    /** Redacta recursivamente claves sensibles. */
    public static function sanitize(array $data, int $depth = 0): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($k) && preg_match(self::REDACT_KEYS, $k)) {
                $out[$k] = '[REDACTED]';
            } elseif (is_array($v)) {
                $out[$k] = $depth < 5 ? self::sanitize($v, $depth + 1) : '[...]';
            } elseif (is_object($v)) {
                $out[$k] = '[' . get_class($v) . ']';
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    // ─── Private ──────────────────────────────────────────────

    private static function buildRow(string $level, string $message, array $context, ?Throwable $e, array $request): array
    {
        $level = in_array($level, self::LEVELS, true) ? $level : 'error';
        $server = $_SERVER;

        if (!empty($_SESSION['user_id'])) {
            $context['user'] = array_filter([
                'id'              => $_SESSION['user_id'] ?? null,
                'name'            => $_SESSION['user_name'] ?? null,
                'email'           => $_SESSION['user_email'] ?? null,
                'role'            => $_SESSION['user_role'] ?? null,
                'auth_source'     => $_SESSION['auth_source'] ?? null,
                'impersonated_by' => $_SESSION['impersonated_by'] ?? null,
            ], fn ($v) => $v !== null);
        }

        if ($e) {
            $context['trace'] = mb_substr($e->getTraceAsString(), 0, self::MAX_TRACE);
            $prev = [];
            for ($p = $e->getPrevious(); $p !== null && count($prev) < 5; $p = $p->getPrevious()) {
                $prev[] = get_class($p) . ': ' . $p->getMessage() . ' (' . self::relativePath($p->getFile()) . ':' . $p->getLine() . ')';
            }
            if ($prev) {
                $context['previous'] = $prev;
            }
        }

        $json = $context ? json_encode(self::sanitize($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR) : null;

        return [
            'level'           => $level,
            'message'         => mb_substr($message, 0, self::MAX_MESSAGE),
            'exception_class' => $e ? mb_substr(get_class($e), 0, 190) : null,
            'code'            => $e && is_numeric($e->getCode()) ? (int) $e->getCode() : null,
            'file'            => $e ? mb_substr(self::relativePath($e->getFile()), 0, 255) : null,
            'line'            => $e ? $e->getLine() : null,
            'uri'             => self::cut($request['uri'] ?? $server['REQUEST_URI'] ?? null, 500),
            'method'          => self::cut($request['method'] ?? $server['REQUEST_METHOD'] ?? (PHP_SAPI === 'cli' ? 'CLI' : null), 10),
            'ip'              => self::cut($request['ip'] ?? $server['REMOTE_ADDR'] ?? null, 45),
            'user_agent'      => self::cut($request['user_agent'] ?? $server['HTTP_USER_AGENT'] ?? null, 500),
            'context'         => $json === false ? null : $json,
            'created_at'      => date('Y-m-d H:i:s'),
        ];
    }

    private static function maybePurge(Connection $conn): void
    {
        if (self::$purgeOdds > 0 && mt_rand(1, self::$purgeOdds) === 1) {
            try {
                self::purge($conn);
            } catch (Throwable $e) {
                // la purga nunca debe afectar al request
            }
        }
    }

    private static function relativePath(string $file): string
    {
        $root = dirname(__DIR__, 2) . '/';
        return str_starts_with($file, $root) ? substr($file, strlen($root)) : $file;
    }

    private static function cut(?string $value, int $max): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, $max);
    }
}
