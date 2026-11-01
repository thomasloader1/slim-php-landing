<?php

namespace App\Services;

use App\Middleware\TenantResolverMiddleware;
use App\Models\Site;

/**
 * Validación de datos de un sitio. Compartida por el panel (SiteAdminController)
 * y la consola (bin/console site:register).
 */
final class SiteValidator
{
    public const DOMAIN_REGEX = '/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D';
    public const PLANS = ['basico', 'profesional', 'premium'];

    /** @return string|null mensaje de error o null si es válido */
    public static function validate(array $data, ?int $ignoreId = null, bool $requirePassword = true): ?string
    {
        $domain = strtolower(trim((string) ($data['domain'] ?? '')));
        if (trim((string) ($data['name'] ?? '')) === '') {
            return 'El nombre es obligatorio.';
        }
        if (!preg_match(self::DOMAIN_REGEX, $domain)) {
            return 'Dominio inválido. Usá solo el host, sin http:// ni rutas (ej: cliente.com.ar).';
        }
        if (str_starts_with($domain, 'www.')) {
            return 'Registrá el dominio sin "www." (se acepta automáticamente).';
        }
        if (TenantResolverMiddleware::isMasterHost($domain)) {
            return 'Ese dominio es un dominio master (MASTER_DOMAINS): no puede ser un sitio cliente.';
        }
        $dup = Site::where('domain', $domain)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($dup) {
            return 'Ya existe un sitio con ese dominio.';
        }
        foreach (['db_name' => 'Base de datos', 'db_user' => 'Usuario'] as $field => $label) {
            if (!preg_match(TenantProvisioner::IDENTIFIER, (string) ($data[$field] ?? ''))) {
                return "{$label}: solo letras, números y _ (máx. 64).";
            }
            if (self::isReserved((string) $data[$field])) {
                return "{$label}: ese nombre está reservado (sistema MySQL o la base/usuario master).";
            }
        }
        if (!preg_match('/^[A-Za-z0-9.\-_]{1,255}$/', (string) ($data['db_host'] ?? ''))) {
            return 'Host de base de datos inválido.';
        }
        $port = (int) ($data['db_port'] ?? 3306);
        if ($port < 1 || $port > 65535) {
            return 'Puerto inválido.';
        }
        if ($requirePassword && (string) ($data['db_pass'] ?? '') === '') {
            return 'La contraseña de la base de datos es obligatoria.';
        }
        $start = (string) ($data['plan_start'] ?? '');
        $end = (string) ($data['plan_end'] ?? '');
        if (!self::isDate($start) || !self::isDate($end)) {
            return 'Fechas de plan inválidas.';
        }
        if ($end < $start) {
            return 'La fecha de fin del plan no puede ser anterior al inicio.';
        }
        return null;
    }

    /** Nombres que un sitio nunca puede usar: GRANT/ALTER USER sobre ellos sería destructivo. */
    public static function isReserved(string $name): bool
    {
        $reserved = ['mysql', 'sys', 'information_schema', 'performance_schema', 'root', 'mysql.sys', 'mysql.session'];
        foreach (['DB_NAME', 'DB_USER'] as $env) {
            $v = getenv($env) ?: ($_ENV[$env] ?? '');
            if ($v !== '') {
                $reserved[] = strtolower($v);
            }
        }
        return in_array(strtolower($name), $reserved, true);
    }

    public static function isDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }
}
