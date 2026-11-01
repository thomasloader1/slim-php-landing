<?php

namespace App\Services;

use App\Middleware\TenantResolverMiddleware;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Diagnóstico de la instalación (DB master). Cada check devuelve:
 *   ['group','label','status'=>'ok'|'warn'|'error','detail','fix'?]
 * Nunca expone secretos: la clave de cifrado se muestra como huella sha256 corta.
 */
class SystemCheckService
{
    private MigrationService $migrations;
    private string $root;

    public function __construct(?MigrationService $migrations = null, ?string $root = null)
    {
        $this->migrations = $migrations ?? new MigrationService();
        $this->root = $root ?? dirname(__DIR__, 2);
    }

    /** Huella pública de una clave (para comparar local vs producción sin exponerla). */
    public static function keyFingerprint(string $key): string
    {
        return $key === '' ? '' : substr(hash('sha256', $key), 0, 16);
    }

    public function run(): array
    {
        $checks = [];
        $dbOk = $this->checkDatabase($checks);
        $this->checkEncryption($checks, $dbOk);
        $this->checkDomains($checks, $dbOk);
        $this->checkFilesystem($checks);
        $this->checkPhp($checks);
        return $checks;
    }

    public function hasErrors(array $checks): bool
    {
        foreach ($checks as $c) {
            if ($c['status'] === 'error') {
                return true;
            }
        }
        return false;
    }

    private function checkDatabase(array &$checks): bool
    {
        try {
            $conn = Capsule::connection('default');
            $conn->getPdo();
            $checks[] = $this->ok('Base de datos', 'Conexión DB master', $conn->getDatabaseName() . ' @ ' . $conn->getConfig('host'));
        } catch (\Throwable $e) {
            $checks[] = $this->error('Base de datos', 'Conexión DB master', $e->getMessage(), 'Revisá DB_HOST / DB_NAME / DB_USER / DB_PASS en .env');
            return false;
        }

        $missing = $this->migrations->missingTables($conn, 'master');
        $checks[] = $missing
            ? $this->error('Base de datos', 'Tablas', 'Faltan: ' . implode(', ', $missing), 'Usá "Aplicar migraciones".')
            : $this->ok('Base de datos', 'Tablas', 'Todas las tablas requeridas existen.');

        $pending = array_filter($this->migrations->status($conn, 'master'), fn ($m) => $m['status'] !== 'applied');
        if ($pending) {
            $detail = implode(', ', array_map(fn ($m) => "{$m['file']} ({$m['status']})", $pending));
            $missingFile = array_filter($pending, fn ($m) => $m['status'] === 'missing_file');
            $checks[] = $missingFile
                ? $this->error('Base de datos', 'Migraciones', $detail, 'Falta la carpeta db/ en el servidor: subila de nuevo.')
                : $this->warn('Base de datos', 'Migraciones', $detail, 'Usá "Aplicar migraciones" (es seguro re-ejecutarlas).');
        } else {
            $checks[] = $this->ok('Base de datos', 'Migraciones', 'Al día.');
        }

        try {
            $defaults = Capsule::table('users')->where('active', 1)->get(['email', 'password_hash'])
                ->filter(fn ($u) => password_verify('password', $u->password_hash))
                ->pluck('email')->all();
            $checks[] = $defaults
                ? $this->warn('Seguridad', 'Contraseñas por defecto', 'Usuarios con clave "password": ' . implode(', ', $defaults), 'Cambiala en Usuarios.')
                : $this->ok('Seguridad', 'Contraseñas por defecto', 'Ningún usuario usa la clave por defecto.');
        } catch (\Throwable $e) {
            // tabla users ausente: ya reportado arriba
        }

        return true;
    }

    private function checkEncryption(array &$checks, bool $dbOk): void
    {
        $key = getenv('TENANT_ENCRYPTION_KEY') ?: ($_ENV['TENANT_ENCRYPTION_KEY'] ?? '');
        $active = getenv('TENANT_ENCRYPTION_KEY_ACTIVE') ?: ($_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] ?? 'v1');

        try {
            $enc = new EncryptionService();
        } catch (\Throwable $e) {
            $checks[] = $this->error('Cifrado', 'TENANT_ENCRYPTION_KEY', $e->getMessage(), 'Generá una con: openssl rand -base64 32');
            return;
        }

        if ($key === '') {
            $checks[] = $this->warn('Cifrado', 'TENANT_ENCRYPTION_KEY', 'No configurada. El gestor de sitios no puede guardar credenciales.', 'Generá una con: openssl rand -base64 32 y agregala al .env');
            return;
        }
        $checks[] = $this->ok('Cifrado', 'TENANT_ENCRYPTION_KEY', 'Configurada. Huella: ' . self::keyFingerprint($key));

        if (!$enc->hasKey($active)) {
            $checks[] = $this->error('Cifrado', 'Clave activa', "TENANT_ENCRYPTION_KEY_ACTIVE={$active} pero esa clave no está configurada.");
        }

        if ($dbOk) {
            try {
                $pending = (new KeyRotationService())->pendingCount();
                $checks[] = $pending
                    ? $this->warn('Cifrado', 'Rotación', "{$pending} secreto(s) cifrados con una clave distinta a la activa ({$active}).", 'Usá "Re-cifrar con clave activa".')
                    : $this->ok('Cifrado', 'Rotación', "Todos los secretos usan la clave activa ({$active}).");
            } catch (\Throwable $e) {
                $checks[] = $this->warn('Cifrado', 'Rotación', $e->getMessage());
            }
        }
    }

    private function checkDomains(array &$checks, bool $dbOk): void
    {
        $checks[] = $this->ok('Dominios', 'MASTER_DOMAINS', implode(', ', TenantResolverMiddleware::masterDomains()));

        if (!$dbOk) {
            return;
        }
        try {
            $enabled = Capsule::table('settings')->where('setting_key', 'module_site_manager_enabled')->value('setting_value') === '1';
            $sites = Capsule::schema()->hasTable('sites') ? Capsule::table('sites')->count() : 0;
            $checks[] = $enabled
                ? $this->ok('Dominios', 'Gestor de sitios', "Activo. {$sites} sitio(s) registrados.")
                : $this->warn('Dominios', 'Gestor de sitios', 'Desactivado: todos los dominios sirven la landing master.', 'Activalo en Módulos para servir sitios clientes.');
        } catch (\Throwable $e) {
            $checks[] = $this->warn('Dominios', 'Gestor de sitios', $e->getMessage());
        }
    }

    private function checkFilesystem(array &$checks): void
    {
        foreach (['storage', 'storage/cache', 'storage/logs', 'public/uploads'] as $dir) {
            $path = $this->root . '/' . $dir;
            if (!is_dir($path)) {
                $checks[] = $this->error('Archivos', $dir . '/', 'No existe.', "mkdir -p {$dir} && chmod 755 {$dir}");
            } elseif (!is_writable($path)) {
                $checks[] = $this->error('Archivos', $dir . '/', 'Sin permiso de escritura.', "chmod 755 {$dir}");
            } else {
                $checks[] = $this->ok('Archivos', $dir . '/', 'Escribible.');
            }
        }

        $checks[] = is_dir($this->root . '/db/init')
            ? $this->ok('Archivos', 'db/', 'Presente (necesaria para migraciones e inicializar sitios).')
            : $this->error('Archivos', 'db/', 'No existe.', 'Subí la carpeta db/ al servidor.');
    }

    private function checkPhp(array &$checks): void
    {
        $checks[] = version_compare(PHP_VERSION, '8.1.0', '>=')
            ? $this->ok('PHP', 'Versión', PHP_VERSION)
            : $this->error('PHP', 'Versión', PHP_VERSION, 'Se requiere PHP 8.1+');

        foreach (['pdo_mysql', 'openssl', 'mbstring', 'json', 'fileinfo'] as $ext) {
            $checks[] = extension_loaded($ext)
                ? $this->ok('PHP', "ext-{$ext}", 'Cargada.')
                : $this->error('PHP', "ext-{$ext}", 'No disponible.', "Habilitá {$ext} en el PHP del hosting.");
        }
    }

    private function ok(string $g, string $l, string $d): array
    {
        return ['group' => $g, 'label' => $l, 'status' => 'ok', 'detail' => $d, 'fix' => null];
    }

    private function warn(string $g, string $l, string $d, ?string $fix = null): array
    {
        return ['group' => $g, 'label' => $l, 'status' => 'warn', 'detail' => $d, 'fix' => $fix];
    }

    private function error(string $g, string $l, string $d, ?string $fix = null): array
    {
        return ['group' => $g, 'label' => $l, 'status' => 'error', 'detail' => $d, 'fix' => $fix];
    }
}
