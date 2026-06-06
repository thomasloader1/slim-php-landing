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
 *
 * Exit codes:
 *   0 = éxito (se limpió algo o no había nada que limpiar)
 *   1 = entorno inválido (APP_ENV no es 'production')
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

// ─── Config: rutas a limpiar ─────────────────────────────────────────
// Agregar más entradas acá en el futuro según sea necesario.
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
];

// ─── Ejecutar limpieza ───────────────────────────────────────────────

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

// ─── Reporte ─────────────────────────────────────────────────────────

if (empty($removed) && empty($errors)) {
    echo "cleanup.php: Nada que limpiar — no se encontraron archivos.\n";
} else {
    echo "cleanup.php: Limpieza completada.\n";

    if (!empty($removed)) {
        echo "\nElementos eliminados:\n";
        foreach ($removed as $item) {
            echo "  ✓ $item\n";
        }
    }
}

if (!empty($errors)) {
    echo "\nErrores:\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
}

exit(0);
