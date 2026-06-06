#!/usr/bin/env php
<?php

/**
 * cleanup.php — Elimina archivos innecesarios en producción.
 *
 * Uso: php cleanup.php
 *
 * Este script debe ejecutarse en el servidor de producción como parte del
 * deploy, después de `composer install --no-dev`.
 *
 * Para agregar más rutas en el futuro, solo añadir entradas al array $paths.
 * Si la ruta requiere validar la base de datos primero, agregarla en
 * 'db_dirs' o 'db_files' — se verificará conexión MySQL + tablas antes.
 *
 * Exit codes:
 *   0 = éxito (se limpió algo o no había nada que limpiar)
 *   1 = entorno inválido (APP_ENV no es 'production')
 *   2 = validación de BD falló (conexión o tablas faltantes)
 */

$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'production';

if ($env !== 'production') {
    echo "cleanup.php: APP_ENV es '$env' — abortando (solo producción).\n";
    exit(1);
}

$baseDir  = __DIR__;              // landing/
$rootDir  = dirname(__DIR__);     // raíz del repo (para Docker, etc.)
$removed  = [];
$errors   = [];

// ─── Helpers ─────────────────────────────────────────────────────────

function removeDirectory(string $dir, array &$removed, array &$errors, string $label): void
{
    if (!is_dir($dir)) {
        return;
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
        $removed[] = $label;
    } catch (Exception $e) {
        $errors[] = "Error al eliminar $label: " . $e->getMessage();
    }
}

function removeFile(string $path, array &$removed, array &$errors, string $label): void
{
    if (!is_file($path)) {
        return;
    }

    if (!@unlink($path)) {
        $errors[] = "No se pudo eliminar: $label";
    } else {
        $removed[] = $label;
    }
}

// ─── Validación de base de datos ──────────────────────────────────────

/**
 * Carga variables de entorno desde .env si existe (parseo simple).
 */
function loadEnv(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $vars = [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }

    return $vars;
}

/**
 * Valida conexión MySQL y existencia de tablas requeridas.
 *
 * @param array $requiredTables Lista de tablas que deben existir
 * @return string|null Null si OK, mensaje de error si falla
 */
function validateDatabase(array $requiredTables): ?string
{
    // Cargar credenciales: priorizar variables de entorno, luego .env
    $envVars = loadEnv(__DIR__ . '/.env');

    $host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? $envVars['DB_HOST'] ?? null;
    $port = $_SERVER['DB_PORT'] ?? $_ENV['DB_PORT'] ?? $envVars['DB_PORT'] ?? '3306';
    $name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? $envVars['DB_NAME'] ?? null;
    $user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? $envVars['DB_USER'] ?? null;
    $pass = $_SERVER['DB_PASS'] ?? $_ENV['DB_PASS'] ?? $envVars['DB_PASS'] ?? null;

    if (!$host || !$name || !$user) {
        return "No se encontraron credenciales de base de datos en entorno ni en .env";
    }

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
    } catch (PDOException $e) {
        return "No se pudo conectar a MySQL: " . $e->getMessage();
    }

    // Verificar tablas requeridas
    $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
    $stmt = $pdo->prepare(
        "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ($placeholders)"
    );
    $stmt->execute(array_merge([$name], $requiredTables));
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $missing = array_diff($requiredTables, $existing);

    if (!empty($missing)) {
        return "Faltan tablas requeridas: " . implode(', ', $missing);
    }

    return null; // OK
}

// ─── Config: rutas a limpiar ─────────────────────────────────────────
// Agregar más entradas acá en el futuro según sea necesario.
//
// Secciones disponibles:
//   'dirs'       → directorios que se borran siempre
//   'files'      → archivos que se borran siempre
//   'root_dirs'  → directorios en la raíz del repo
//   'root_files' → archivos en la raíz del repo
//   'db_dirs'    → directorios que requieren validación de BD primero
//   'db_files'   → archivos que requieren validación de BD primero

$paths = [
    // Tests y config de PHPUnit (dentro de landing/)
    'dirs' => [
        [$baseDir . '/tests',            'tests/ (directorio completo)'],
        [$baseDir . '/.phpunit.cache',   '.phpunit.cache/ (directorio completo)'],
    ],
    'files' => [
        [$baseDir . '/phpunit.xml',      'phpunit.xml'],
        [$baseDir . '/phpunit.xml.dist', 'phpunit.xml.dist'],
    ],

    // Docker (en la raíz del repo)
    'root_dirs' => [
        [$rootDir . '/docker',           'docker/ (directorio completo)'],
    ],
    'root_files' => [
        [$rootDir . '/docker-compose.yml', 'docker-compose.yml'],
        [$rootDir . '/.dockerignore',      '.dockerignore'],
    ],

    // Archivos de esquema BD (se borran solo si la BD está funcionando)
    'db_dirs' => [
        [$baseDir . '/db',               'db/ (directorio completo)'],
    ],
];

// Tablas que deben existir en la BD para poder borrar db/
$requiredTables = [
    'users', 'links', 'settings', 'locations',
    'menu_sections', 'menu_items', 'faq_items',
    'sites',
];

// ─── Ejecutar limpieza (sin validación de BD) ────────────────────────

foreach ($paths['dirs'] as [$path, $label]) {
    removeDirectory($path, $removed, $errors, $label);
}

foreach ($paths['files'] as [$path, $label]) {
    removeFile($path, $removed, $errors, $label);
}

foreach ($paths['root_dirs'] as [$path, $label]) {
    removeDirectory($path, $removed, $errors, $label);
}

foreach ($paths['root_files'] as [$path, $label]) {
    removeFile($path, $removed, $errors, $label);
}

// ─── Validar BD y limpiar rutas que dependen de ella ─────────────────

$dbError = validateDatabase($requiredTables);

if ($dbError !== null) {
    echo "cleanup.php: [$dbError] — se omite limpieza de db/.\n";
    echo "  → La BD debe estar funcionando y tener todas las tablas antes de borrar db/.\n";
}

foreach ($paths['db_dirs'] ?? [] as [$path, $label]) {
    if ($dbError !== null) {
        $errors[] = "Omitido (validación BD falló): $label";
        continue;
    }
    removeDirectory($path, $removed, $errors, $label);
}

// ─── Reporte ─────────────────────────────────────────────────────────

if (empty($removed) && empty($errors)) {
    echo "cleanup.php: Nada que limpiar — no se encontraron archivos.\n";
    exit(0);
}

echo "cleanup.php: Limpieza completada.\n";

if (!empty($removed)) {
    echo "\nElementos eliminados:\n";
    foreach ($removed as $item) {
        echo "  ✓ $item\n";
    }
}

if (!empty($errors)) {
    echo "\nAdvertencias:\n";
    foreach ($errors as $error) {
        echo "  ⚠ $error\n";
    }
}

exit($dbError !== null ? 2 : 0);
