<?php

namespace App\Services;

use App\Support\SqlSplitter;
use Illuminate\Database\Connection;

/**
 * Aplica el esquema de la landing de forma idempotente sobre cualquier conexión
 * (DB master o DB de un sitio). Reemplaza el "mysql -u ... < archivo.sql" manual.
 *
 * - scope "site":   esquema de una landing (lo que necesita cada DB de cliente).
 * - scope "master": esquema de landing + tablas del gestor (sites, servers).
 *
 * Errores "ya existe" (tabla/columna/índice) se toman como aplicados, porque
 * MySQL 8 no soporta ADD COLUMN IF NOT EXISTS y 03_menu_enhance.sql usa ALTERs.
 */
class MigrationService
{
    /** Archivos de esquema (sin datos de demo). Orden importa. */
    public const SITE_FILES = [
        'init/01_schema.sql',
        'init/03_menu_enhance.sql',
        'init/05_locations_whatsapp.sql',
    ];

    public const MASTER_FILES = [
        'master/01_sites.sql',
        'master/02_servers.sql',
    ];

    /** Datos base (settings por defecto). El INSERT de usuarios se filtra. */
    public const SEED_FILE = 'init/02_seed.sql';

    /** Tablas que deben existir según el scope. */
    public const SITE_TABLES = ['users', 'links', 'settings', 'locations', 'menu_sections', 'menu_items', 'faq_items'];
    public const MASTER_TABLES = ['sites', 'servers'];

    /** 1050 tabla existe, 1060 columna duplicada, 1061 índice duplicado, 1091 no se puede DROP (no existe) */
    private const ALREADY_APPLIED = [1050, 1060, 1061, 1091];

    private string $dbDir;

    public function __construct(?string $dbDir = null)
    {
        $this->dbDir = $dbDir ?? dirname(__DIR__, 2) . '/db';
    }

    public function filesFor(string $scope): array
    {
        return $scope === 'master'
            ? array_merge(self::SITE_FILES, self::MASTER_FILES)
            : self::SITE_FILES;
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
        $existing = array_map(
            fn ($row) => strtolower((string) array_values((array) $row)[0]),
            $conn->select('SHOW TABLES')
        );
        return array_values(array_diff($this->requiredTables($scope), $existing));
    }

    /** @return array{file:string,status:string,checksum:string}[] estado por archivo */
    public function status(Connection $conn, string $scope): array
    {
        $applied = [];
        try {
            foreach ($conn->table('schema_migrations')->get() as $row) {
                $applied[$row->filename] = $row->checksum;
            }
        } catch (\Throwable $e) {
            // tabla aún no creada: todo pendiente
        }

        $out = [];
        foreach ($this->filesFor($scope) as $file) {
            $path = $this->path($file);
            $checksum = is_file($path) ? hash_file('sha256', $path) : '';
            $status = match (true) {
                $checksum === '' => 'missing_file',
                !isset($applied[$file]) => 'pending',
                $applied[$file] !== $checksum => 'changed',
                default => 'applied',
            };
            $out[] = ['file' => $file, 'status' => $status, 'checksum' => $checksum];
        }
        return $out;
    }

    /**
     * Aplica todos los archivos del scope (re-ejecutarlos es seguro).
     * @return string[] log legible
     */
    public function migrate(Connection $conn, string $scope, bool $withSeed = false): array
    {
        $log = [];
        $this->ensureMigrationsTable($conn);

        $files = $this->filesFor($scope);
        if ($withSeed) {
            $files[] = self::SEED_FILE;
        }

        foreach ($files as $file) {
            $path = $this->path($file);
            if (!is_file($path)) {
                throw new \RuntimeException("Archivo de esquema no encontrado: db/{$file}. ¿Se borró db/ en el servidor?");
            }
            [$run, $skipped] = $this->runFile($conn, $path, $file === self::SEED_FILE);

            $conn->table('schema_migrations')->updateOrInsert(
                ['filename' => $file],
                ['checksum' => hash_file('sha256', $path), 'scope' => $scope, 'applied_at' => date('Y-m-d H:i:s')]
            );
            $log[] = "db/{$file}: {$run} sentencias ejecutadas" . ($skipped ? ", {$skipped} ya estaban aplicadas" : '');
        }

        return $log;
    }

    /** @return array{0:int,1:int} [ejecutadas, ya aplicadas] */
    private function runFile(Connection $conn, string $path, bool $skipUserInserts): array
    {
        $run = 0;
        $skipped = 0;
        foreach (SqlSplitter::split((string) file_get_contents($path)) as $stmt) {
            // El seed trae un admin con clave por defecto: nunca lo copiamos a un sitio nuevo
            if ($skipUserInserts && preg_match('/^INSERT\s+INTO\s+`?users`?/i', $stmt)) {
                continue;
            }
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
