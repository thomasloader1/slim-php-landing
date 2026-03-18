<?php

namespace App\Install;

use PDO;
use PDOException;

/**
 * Maneja las migraciones de base de datos asociadas a cada commit/versión.
 *
 * Convención de archivos en db/updates/:
 *   YYYYMMDD_HHMM_{version}_{descripcion}.sql
 *   Ej: 20260316_1100_1_1_0_seo_settings.sql
 *
 * Las migraciones aplicadas se almacenan en la tabla settings como
 * JSON bajo la clave app_applied_migrations.
 * La versión instalada se guarda en app_installed_version.
 */
class UpdateManager
{
    private PDO    $pdo;
    private string $updatesDir;

    public function __construct(PDO $pdo)
    {
        $this->pdo        = $pdo;
        $this->updatesDir = realpath(__DIR__ . '/../../db/updates') ?: (__DIR__ . '/../../db/updates');
    }

    // ─── Versión del código ───────────────────────────────────────

    /**
     * Detecta la versión actual del código:
     *   1. git describe --tags --always (tag o hash corto)
     *   2. git rev-parse --short HEAD
     *   3. Archivo landing/VERSION
     *   4. 'unknown'
     */
    public function getCodeVersion(): string
    {
        // Intentar con git describe (funciona en Linux/Mac/Windows con git en PATH)
        $gitDescribe = $this->runGit('git describe --tags --always 2>/dev/null');
        if ($gitDescribe !== '') {
            return $gitDescribe;
        }

        // Intentar con rev-parse
        $gitHash = $this->runGit('git rev-parse --short HEAD 2>/dev/null');
        if ($gitHash !== '') {
            return $gitHash;
        }

        // Archivo VERSION
        $versionFile = __DIR__ . '/../../VERSION';
        if (file_exists($versionFile)) {
            $v = trim(file_get_contents($versionFile));
            if ($v !== '') {
                return $v;
            }
        }

        return 'unknown';
    }

    /**
     * Ejecuta un comando git y retorna el resultado limpio.
     * Silencia errores para no romper si git no está disponible.
     */
    private function runGit(string $cmd): string
    {
        if (!function_exists('shell_exec')) {
            return '';
        }

        // En Windows adaptar redirección de stderr
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = str_replace('2>/dev/null', '2>NUL', $cmd);
        }

        $result = @shell_exec($cmd);
        return trim((string) ($result ?? ''));
    }

    // ─── Versión instalada en DB ──────────────────────────────────

    public function getInstalledVersion(): string
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT setting_value FROM settings WHERE setting_key = 'app_installed_version' LIMIT 1"
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($row && $row['setting_value'] !== '') ? $row['setting_value'] : 'ninguna';
        } catch (PDOException) {
            return 'ninguna';
        }
    }

    public function setInstalledVersion(string $version): void
    {
        $this->upsertSetting('app_installed_version', $version);
    }

    // ─── Migraciones aplicadas ────────────────────────────────────

    /** @return string[] Lista de nombres de archivo (sin path) ya aplicados */
    public function getAppliedMigrations(): array
    {
        try {
            $stmt = $this->pdo->query(
                "SELECT setting_value FROM settings WHERE setting_key = 'app_applied_migrations' LIMIT 1"
            );
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || empty($row['setting_value'])) {
                return [];
            }
            return json_decode($row['setting_value'], true) ?? [];
        } catch (PDOException) {
            return [];
        }
    }

    private function saveAppliedMigrations(array $applied): void
    {
        $this->upsertSetting('app_applied_migrations', json_encode(array_values($applied)));
    }

    // ─── Descubrimiento de archivos SQL ──────────────────────────

    /**
     * Retorna todos los archivos SQL de db/updates/ en orden alfabético.
     * @return string[] Rutas absolutas
     */
    public function discoverMigrations(): array
    {
        if (!is_dir($this->updatesDir)) {
            return [];
        }
        $files = glob($this->updatesDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files); // orden alfabético = cronológico con prefijo de fecha
        return $files;
    }

    /**
     * Retorna solo las migraciones aún no aplicadas.
     * @return string[] Rutas absolutas
     */
    public function getPendingMigrations(): array
    {
        $all     = $this->discoverMigrations();
        $applied = $this->getAppliedMigrations();

        return array_values(
            array_filter($all, fn($f) => !in_array(basename($f), $applied, true))
        );
    }

    // ─── Ejecución ───────────────────────────────────────────────

    /**
     * Aplica UNA migración y la marca como aplicada.
     * Las migraciones son idempotentes (IF NOT EXISTS / ON DUPLICATE KEY),
     * por lo que siempre se marcan como aplicadas para evitar re-ejecución.
     * Retorna array de líneas de log.
     */
    public function applyMigration(string $filePath, DbInstaller $installer): array
    {
        $log = $installer->runSqlFile($this->pdo, $filePath);

        // Siempre marcar como aplicada — los scripts son idempotentes
        $applied   = $this->getAppliedMigrations();
        $applied[] = basename($filePath);
        $this->saveAppliedMigrations($applied);

        return $log;
    }

    /**
     * Aplica todas las migraciones pendientes en orden.
     * @return array{file: string, log: string[]}[]
     */
    public function applyAll(DbInstaller $installer): array
    {
        $pending = $this->getPendingMigrations();
        $results = [];

        foreach ($pending as $file) {
            $log       = $this->applyMigration($file, $installer);
            $results[] = ['file' => $file, 'log' => $log];
        }

        // Actualizar versión instalada a la del código
        $this->setInstalledVersion($this->getCodeVersion());

        return $results;
    }

    // ─── Leer config .env ────────────────────────────────────────

    /**
     * Lee el archivo .env de la app y retorna un array de clave → valor.
     */
    public function readEnvConfig(): array
    {
        $envFile = __DIR__ . '/../../.env';
        $config  = [];

        if (!file_exists($envFile)) {
            return $config;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $config[trim($k)] = trim($v, '"\'');
        }

        return $config;
    }

    // ─── Helper privado ───────────────────────────────────────────

    private function upsertSetting(string $key, string $value): void
    {
        $this->pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        )->execute([':k' => $key, ':v' => $value]);
    }
}
