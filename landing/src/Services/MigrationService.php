<?php

namespace App\Services;

use App\Support\SqlSplitter;
use Illuminate\Database\Connection;

/**
 * Aplica db/install.sql: el ÚNICO script de base de datos. Crea o actualiza
 * todo (tablas, columnas nuevas, roles, settings y el usuario inicial) y se
 * puede correr las veces que haga falta: cada cambio pregunta antes si ya
 * existe. Es el mismo archivo que se puede importar a mano en phpMyAdmin.
 *
 * Errores "ya existe" (tabla/columna/índice) igual se toleran por si una base
 * tiene cambios aplicados a mano con otro nombre de índice.
 */
class MigrationService
{
    public const INSTALL_FILE = 'install.sql';

    /** Usuario que crea install.sql en una base sin usuarios. */
    public const DEFAULT_USER_EMAIL = 'admin@gotogo.com.ar';

    /** Tablas que deben existir según el scope. */
    public const SITE_TABLES = ['users', 'roles', 'role_permissions', 'links', 'settings', 'locations', 'menu_sections', 'menu_items', 'faq_items', 'orders', 'order_items', 'rate_limits', 'error_logs'];
    public const MASTER_TABLES = ['sites', 'sso_nonces'];

    /** 1050 tabla existe, 1060 columna duplicada, 1061 índice duplicado, 1091 no se puede DROP (no existe) */
    private const ALREADY_APPLIED = [1050, 1060, 1061, 1091];

    private string $dbDir;

    public function __construct(?string $dbDir = null)
    {
        $this->dbDir = $dbDir ?? dirname(__DIR__, 2) . '/db';
    }

    /** @return string[] archivos SQL que aplica (siempre install.sql) */
    public function filesFor(string $scope): array
    {
        return [self::INSTALL_FILE];
    }

    public function requiredTables(string $scope): array
    {
        return $scope === 'master'
            ? array_merge(self::SITE_TABLES, self::MASTER_TABLES)
            : self::SITE_TABLES;
    }

    /** @return string[] tablas faltantes en la conexión */
    public function missingTables(Connection $conn, string $scope): array
    {
        return array_values(array_diff($this->requiredTables($scope), $this->existingTables($conn)));
    }

    /**
     * "applied" si la versión actual de install.sql ya corrió en esta base;
     * "pending" si nunca corrió o el archivo cambió desde entonces.
     * @return array{file:string,status:string,checksum:string}[]
     */
    public function status(Connection $conn, string $scope): array
    {
        $path = $this->path(self::INSTALL_FILE);
        $checksum = is_file($path) ? hash_file('sha256', $path) : '';
        if ($checksum === '') {
            return [['file' => self::INSTALL_FILE, 'status' => 'missing_file', 'checksum' => '']];
        }

        $recorded = null;
        try {
            $recorded = $conn->table('schema_migrations')->where('filename', self::INSTALL_FILE)->value('checksum');
        } catch (\Throwable $e) {
            // tabla aún no creada
        }
        $applied = $recorded === $checksum && !$this->missingTables($conn, $scope);

        return [['file' => self::INSTALL_FILE, 'status' => $applied ? 'applied' : 'pending', 'checksum' => $checksum]];
    }

    /**
     * Corre install.sql completo (re-ejecutarlo es seguro).
     *
     * @param bool $withSeed        compatibilidad: los datos iniciales ya van siempre en install.sql
     * @param bool $withDefaultUser false = no dejar el usuario inicial genérico (sitios creados
     *                              desde el gestor, que cargan su propio admin)
     * @return string[] log legible
     */
    public function migrate(Connection $conn, string $scope, bool $withSeed = false, bool $withDefaultUser = true): array
    {
        $this->ensureMigrationsTable($conn);
        $hadUsers = in_array('users', $this->existingTables($conn), true) && $conn->table('users')->exists();

        [$run, $skipped] = $this->runFile($conn, self::INSTALL_FILE);
        $this->record($conn, self::INSTALL_FILE, $scope);
        $log = ['db/' . self::INSTALL_FILE . ": {$run} sentencias ejecutadas" . ($skipped ? ", {$skipped} ya estaban aplicadas" : '')];

        if (!$hadUsers && $conn->table('users')->where('email', self::DEFAULT_USER_EMAIL)->exists()) {
            if ($withDefaultUser) {
                $log[] = 'Usuario inicial creado: ' . self::DEFAULT_USER_EMAIL . ' / password  ← cambiá la contraseña al entrar';
            } else {
                $conn->table('users')->where('email', self::DEFAULT_USER_EMAIL)->delete();
            }
        }

        if ($scope === 'master' && ($promoted = $this->ensureRoot($conn)) > 0) {
            $log[] = "{$promoted} administrador(es) de la master pasaron a root";
        }

        return $log;
    }

    /**
     * La master necesita al menos un root. Si no hay ninguno (bases anteriores
     * a los roles), los admin de la master pasan a root. @return int promovidos
     */
    private function ensureRoot(Connection $conn): int
    {
        if ($conn->table('users')->where('role', AccessService::ROOT)->exists()) {
            return 0;
        }
        return $conn->table('users')->where('role', 'admin')->update(['role' => AccessService::ROOT]);
    }

    /** @return array{0:int,1:int} [ejecutadas, ya aplicadas] */
    private function runFile(Connection $conn, string $file): array
    {
        $path = $this->path($file);
        if (!is_file($path)) {
            throw new \RuntimeException("Archivo no encontrado: db/{$file}. ¿Se subió la carpeta db/ al servidor?");
        }

        $run = 0;
        $skipped = 0;
        foreach (SqlSplitter::split((string) file_get_contents($path)) as $stmt) {
            try {
                $conn->unprepared($stmt);
                $run++;
            } catch (\Illuminate\Database\QueryException $e) {
                $code = (int) ($e->errorInfo[1] ?? 0);
                if (!in_array($code, self::ALREADY_APPLIED, true)) {
                    throw $e;
                }
                $skipped++;
            }
        }
        return [$run, $skipped];
    }

    private function record(Connection $conn, string $file, string $scope): void
    {
        $conn->table('schema_migrations')->updateOrInsert(
            ['filename' => $file],
            ['checksum' => hash_file('sha256', $this->path($file)), 'scope' => $scope, 'applied_at' => date('Y-m-d H:i:s')]
        );
    }

    /** @return string[] */
    private function existingTables(Connection $conn): array
    {
        return array_map(
            fn ($row) => strtolower((string) array_values((array) $row)[0]),
            $conn->select('SHOW TABLES')
        );
    }

    private function ensureMigrationsTable(Connection $conn): void
    {
        $conn->unprepared('CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `filename`   VARCHAR(190) NOT NULL PRIMARY KEY,
            `checksum`   CHAR(64) NOT NULL,
            `scope`      VARCHAR(20) NOT NULL,
            `applied_at` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function path(string $file): string
    {
        return $this->dbDir . '/' . $file;
    }
}
