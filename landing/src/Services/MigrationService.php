<?php

namespace App\Services;

use App\Support\SqlSplitter;
use Illuminate\Database\Connection;

/**
 * Aplica el esquema de forma idempotente sobre cualquier conexión
 * (DB master o DB de un sitio). Reemplaza el "mysql -u ... < archivo.sql" manual.
 *
 * Dos "tracks" (la master usa los dos, un sitio solo "site"):
 *   - db/schema/<track>.sql      esquema COMPLETO en su estado final.
 *   - db/upgrades/<track>/*.sql  cambios incrementales para bases existentes.
 *
 * Base nueva (no existe la tabla centinela): se ejecuta el schema y todos los
 * upgrades quedan registrados como incluidos, sin ejecutarlos.
 * Base existente: se ejecutan solo los upgrades no registrados en
 * schema_migrations (cada uno una única vez).
 *
 * Errores "ya existe" (tabla/columna/índice) se toman como aplicados: MySQL 8
 * no soporta ADD COLUMN IF NOT EXISTS y las bases viejas pueden tener parte
 * de un upgrade aplicado a mano.
 */
class MigrationService
{
    public const TRACKS = [
        'site'   => ['schema' => 'schema/site.sql',   'upgrades' => 'upgrades/site',   'sentinel' => 'users'],
        'master' => ['schema' => 'schema/master.sql', 'upgrades' => 'upgrades/master', 'sentinel' => 'sites'],
    ];

    /** Settings por defecto. Nunca pisa valores existentes ni crea usuarios. */
    public const SEED_FILE = 'seed/settings.sql';

    /** Tablas que deben existir según el scope. */
    public const SITE_TABLES = ['users', 'roles', 'role_permissions', 'links', 'settings', 'locations', 'menu_sections', 'menu_items', 'faq_items'];
    public const MASTER_TABLES = ['sites', 'servers'];

    /** 1050 tabla existe, 1060 columna duplicada, 1061 índice duplicado, 1091 no se puede DROP (no existe) */
    private const ALREADY_APPLIED = [1050, 1060, 1061, 1091];

    private string $dbDir;

    public function __construct(?string $dbDir = null)
    {
        $this->dbDir = $dbDir ?? dirname(__DIR__, 2) . '/db';
    }

    /** @return string[] tracks que aplica el scope ("site" o "master") */
    public function tracksFor(string $scope): array
    {
        return $scope === 'master' ? ['site', 'master'] : ['site'];
    }

    /** @return string[] upgrades del track, en orden (rutas relativas a db/) */
    public function upgradeFiles(string $track): array
    {
        $dir = self::TRACKS[$track]['upgrades'];
        $files = glob($this->dbDir . '/' . $dir . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return array_map(fn ($f) => $dir . '/' . basename($f), $files);
    }

    /** @return string[] todos los archivos de esquema del scope (schema + upgrades) */
    public function filesFor(string $scope): array
    {
        $files = [];
        foreach ($this->tracksFor($scope) as $track) {
            $files[] = self::TRACKS[$track]['schema'];
            array_push($files, ...$this->upgradeFiles($track));
        }
        return $files;
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

    /** @return array{file:string,status:string,checksum:string}[] estado por archivo */
    public function status(Connection $conn, string $scope): array
    {
        $applied = $this->appliedChecksums($conn);
        $tables = $this->existingTables($conn);

        $out = [];
        foreach ($this->tracksFor($scope) as $track) {
            $cfg = self::TRACKS[$track];
            $baseReady = in_array($cfg['sentinel'], $tables, true);

            $out[] = $this->fileStatus($cfg['schema'], $baseReady ? 'applied' : 'pending');
            foreach ($this->upgradeFiles($track) as $file) {
                $entry = $this->fileStatus($file, 'pending');
                if (isset($applied[$file])) {
                    $entry['status'] = $applied[$file] === $entry['checksum'] ? 'applied' : 'changed';
                }
                $out[] = $entry;
            }
        }
        return $out;
    }

    /**
     * Crea o actualiza el esquema del scope (re-ejecutarlo es seguro).
     * @return string[] log legible
     */
    public function migrate(Connection $conn, string $scope, bool $withSeed = false): array
    {
        $this->ensureMigrationsTable($conn);
        $log = [];

        foreach ($this->tracksFor($scope) as $track) {
            $cfg = self::TRACKS[$track];
            $upgrades = $this->upgradeFiles($track);

            if (!in_array($cfg['sentinel'], $this->existingTables($conn), true)) {
                [$run] = $this->runFile($conn, $cfg['schema']);
                $this->record($conn, $cfg['schema'], $scope);
                foreach ($upgrades as $file) {
                    $this->record($conn, $file, $scope);
                }
                $log[] = "db/{$cfg['schema']}: base nueva creada ({$run} sentencias); "
                    . count($upgrades) . ' upgrades ya incluidos';
                continue;
            }

            $applied = $this->appliedChecksums($conn);
            $pending = array_values(array_filter($upgrades, fn ($f) => !isset($applied[$f])));
            foreach ($pending as $file) {
                [$run, $skipped] = $this->runFile($conn, $file);
                $this->record($conn, $file, $scope);
                $log[] = "db/{$file}: {$run} sentencias ejecutadas" . ($skipped ? ", {$skipped} ya estaban aplicadas" : '');
            }
            if (!$pending) {
                $log[] = "Esquema {$track}: al día";
            }
        }

        if ($scope === 'master' && ($promoted = $this->ensureRoot($conn)) > 0) {
            $log[] = "{$promoted} administrador(es) de la master pasaron a root";
        }

        if ($withSeed) {
            [$run] = $this->runFile($conn, self::SEED_FILE);
            $this->record($conn, self::SEED_FILE, $scope);
            $log[] = 'db/' . self::SEED_FILE . ": {$run} sentencias (solo agrega claves faltantes)";
        }

        return $log;
    }

    /**
     * La master necesita al menos un root. Si no hay ninguno (bases anteriores
     * a los roles), los admin de la master pasan a root. @return int promovidos
     */
    private function ensureRoot(Connection $conn): int
    {
        if ($conn->table('users')->where('role', 'root')->exists()) {
            return 0;
        }
        return $conn->table('users')->where('role', 'admin')->update(['role' => 'root']);
    }

    /** @return array{0:int,1:int} [ejecutadas, ya aplicadas] */
    private function runFile(Connection $conn, string $file): array
    {
        $path = $this->path($file);
        if (!is_file($path)) {
            throw new \RuntimeException("Archivo de esquema no encontrado: db/{$file}. ¿Se borró db/ en el servidor?");
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

    /** @return array<string,string> filename => checksum */
    private function appliedChecksums(Connection $conn): array
    {
        try {
            return $conn->table('schema_migrations')->pluck('checksum', 'filename')->all();
        } catch (\Throwable $e) {
            return []; // tabla aún no creada: todo pendiente
        }
    }

    /** @return string[] */
    private function existingTables(Connection $conn): array
    {
        return array_map(
            fn ($row) => strtolower((string) array_values((array) $row)[0]),
            $conn->select('SHOW TABLES')
        );
    }

    private function fileStatus(string $file, string $status): array
    {
        $path = $this->path($file);
        $checksum = is_file($path) ? hash_file('sha256', $path) : '';
        return ['file' => $file, 'status' => $checksum === '' ? 'missing_file' : $status, 'checksum' => $checksum];
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
