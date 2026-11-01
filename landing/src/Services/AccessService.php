<?php

namespace App\Services;

use App\Support\Permissions;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Qué secciones del admin puede usar el usuario logueado.
 *
 *  - root: users.role = 'root' en la master, o el root de la master que
 *    entró a un sitio por SSO (sesión con impersonated_by). Ve todo.
 *  - resto: los permisos de su rol (role_permissions) en la DB actual.
 *  - un módulo apagado oculta y bloquea su sección para todos.
 *
 * Cachea por request (estático); AuthMiddleware llama reset() al cargar el usuario.
 */
final class AccessService
{
    public const ROOT = 'root';

    private static ?array $permissions = null;
    private static ?array $modules = null;

    public static function reset(): void
    {
        self::$permissions = null;
        self::$modules = null;
        self::$rootExists = null;
    }

    public static function isMasterContext(): bool
    {
        return ($_SESSION['context'] ?? 'master') === 'master';
    }

    public static function isRoot(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        if (!empty($_SESSION['impersonated_by'])) {
            return true; // root de la master dentro de un sitio (SSO)
        }
        $role = $_SESSION['user_role'] ?? '';
        if ($role === self::ROOT) {
            return true; // en un sitio solo controla ese sitio: lo de la master sigue bloqueado por scope
        }
        // Base master sin migrar a roles (no hay ningún root todavía): el admin actúa
        // como root para poder entrar a Sistema → Aplicar migraciones.
        return $role === 'admin' && self::isMasterContext() && !self::rootExists();
    }

    private static ?bool $rootExists = null;

    private static function rootExists(): bool
    {
        if (self::$rootExists === null) {
            try {
                self::$rootExists = Capsule::table('users')->where('role', self::ROOT)->exists();
            } catch (\Throwable $e) {
                self::$rootExists = false;
            }
        }
        return self::$rootExists;
    }

    /** @return string[] claves de permiso del rol del usuario en la DB actual */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        $role = (string) ($_SESSION['user_role'] ?? '');
        if ($role === '' || !isset($_SESSION['user_id'])) {
            return self::$permissions = [];
        }
        try {
            self::$permissions = Capsule::table('role_permissions')
                ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
                ->where('roles.slug', $role)
                ->pluck('role_permissions.permission')
                ->all();
        } catch (\Throwable $e) {
            // Base sin migrar todavía: sin permisos (solo el root entra)
            error_log('[AccessService] ' . $e->getMessage());
            self::$permissions = [];
        }
        return self::$permissions;
    }

    public static function moduleEnabled(string $flag): bool
    {
        if (self::$modules === null) {
            $flags = array_values(array_filter(array_column(Permissions::CATALOG, 'module')));
            self::$modules = Capsule::table('settings')->whereIn('setting_key', $flags)
                ->pluck('setting_value', 'setting_key')->all();
        }
        $default = in_array($flag, Permissions::MODULES_OFF_BY_DEFAULT, true) ? '0' : '1';
        return (self::$modules[$flag] ?? $default) === '1';
    }

    public static function can(string $key): bool
    {
        $perm = Permissions::CATALOG[$key] ?? null;
        if ($perm === null) {
            return false;
        }
        if ($perm['module'] !== null && !self::moduleEnabled($perm['module'])) {
            return false;
        }
        if ($perm['scope'] === 'master' && !self::isMasterContext()) {
            return false;
        }
        if (self::isRoot()) {
            return true;
        }
        return $perm['scope'] === 'site' && in_array($key, self::permissions(), true);
    }

    /** @return array<string,array> secciones visibles en el sidebar, en orden */
    public static function visibleSections(): array
    {
        return array_filter(Permissions::CATALOG, fn ($p, $key) => self::can($key), ARRAY_FILTER_USE_BOTH);
    }

    /** Roles que el usuario actual puede asignar a otros. */
    public static function assignableRoles(): array
    {
        $roles = Capsule::table('roles')->orderBy('is_system', 'desc')->orderBy('name')->pluck('name', 'slug')->all();
        if (!self::isRoot()) {
            unset($roles['admin']); // solo el root crea administradores
        }
        if (self::isRoot() && self::isMasterContext()) {
            $roles = [self::ROOT => 'Root (control total)'] + $roles;
        }
        return $roles;
    }
}
