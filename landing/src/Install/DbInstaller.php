<?php

namespace App\Install;

use PDO;
use PDOException;

class DbInstaller
{
    /**
     * Prueba la conexión PDO con los datos de configuración dados.
     * Retorna true si conecta, false si falla.
     */
    public function testConnection(array $config): bool
    {
        try {
            $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4";
            new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * Abre conexión PDO. Lanza PDOException si falla.
     */
    public function connect(array $config): PDO
    {
        $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4";
        return new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * Escribe (sobreescribe) el archivo .env en la raíz de la app landing.
     */
    public function writeEnv(array $config): void
    {
        $envPath = __DIR__ . '/../../.env';

        $lines = [
            'DB_HOST='     . $this->escapeEnvValue($config['DB_HOST']),
            'DB_PORT='     . $this->escapeEnvValue($config['DB_PORT']),
            'DB_NAME='     . $this->escapeEnvValue($config['DB_NAME']),
            'DB_USER='     . $this->escapeEnvValue($config['DB_USER']),
            'DB_PASS='     . $this->escapeEnvValue($config['DB_PASS']),
            'APP_ENV='     . $this->escapeEnvValue($config['APP_ENV'] ?? 'production'),
            '',
            '# Ruta base si la app NO está en la raíz del dominio.',
            '# Ej: /landing  → mysite.com/landing/',
            '# Dejar vacío si está en la raíz',
            'APP_BASE_PATH=' . $this->escapeEnvValue($config['APP_BASE_PATH'] ?? ''),
        ];

        file_put_contents($envPath, implode("\n", $lines) . "\n");
    }

    /**
     * Ejecuta un archivo SQL statement por statement sobre la conexión dada.
     * Retorna array de mensajes de log.
     */
    public function runSqlFile(PDO $pdo, string $filePath): array
    {
        $log = [];

        if (!file_exists($filePath)) {
            $log[] = "⚠️  Archivo no encontrado: " . basename($filePath);
            return $log;
        }

        $sql = file_get_contents($filePath);

        // Separar por ; ignorando comentarios
        $statements = array_filter(
            array_map('trim', $this->splitSql($sql)),
            fn($s) => $s !== ''
        );

        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                $log[] = "✓ " . $this->summarizeStatement($statement);
            } catch (PDOException $e) {
                $log[] = "✗ " . $this->summarizeStatement($statement) . " — " . $e->getMessage();
            }
        }

        return $log;
    }

    // ─── Helpers privados ────────────────────────────────────────

    private function escapeEnvValue(string $value): string
    {
        // Envolver en comillas si contiene espacios o caracteres especiales
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"' . addcslashes($value, '"\\') . '"';
        }
        return $value;
    }

    /**
     * Divide SQL en statements individuales respetando strings y comentarios.
     */
    private function splitSql(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inString   = false;
        $strChar    = '';
        $i          = 0;
        $len        = strlen($sql);

        while ($i < $len) {
            $char = $sql[$i];

            // Comentarios de línea -- o #
            if (!$inString && ($char === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-') ||
                (!$inString && $char === '#')) {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            // Comentarios bloque /* */
            if (!$inString && $char === '/' && isset($sql[$i + 1]) && $sql[$i + 1] === '*') {
                $i += 2;
                while ($i < $len && !($sql[$i] === '*' && isset($sql[$i + 1]) && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i += 2;
                continue;
            }

            // Inicio/fin de string
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $strChar  = $char;
            } elseif ($inString && $char === $strChar && (!isset($sql[$i - 1]) || $sql[$i - 1] !== '\\')) {
                $inString = false;
            }

            if (!$inString && $char === ';') {
                $statements[] = trim($current);
                $current      = '';
            } else {
                $current .= $char;
            }

            $i++;
        }

        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        return $statements;
    }

    private function summarizeStatement(string $sql): string
    {
        $first = strtok(trim($sql), " \t\n");
        $preview = substr(trim($sql), 0, 60);
        return strtoupper($first) . ': ' . preg_replace('/\s+/', ' ', $preview) . (strlen($sql) > 60 ? '…' : '');
    }
}
