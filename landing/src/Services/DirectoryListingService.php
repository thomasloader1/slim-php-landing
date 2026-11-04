<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Listado de solo lectura de las carpetas de una ruta local (ej. /home/cuenta/public_html/*).
 * Reemplaza la vieja sección "Servidores": no hay SSH ni acciones de escritura.
 *
 * La ruta se guarda en settings.directories_root (base master). Si no está, se usa
 * DIRECTORIES_ROOT del .env o, si existe, la carpeta public_html junto a la app.
 */
final class DirectoryListingService
{
    public const SETTING = 'directories_root';

    /** Ruta configurada (puede estar vacía si no hay nada configurado ni por defecto). */
    public static function configuredRoot(): string
    {
        try {
            $value = Capsule::table('settings')->where('setting_key', self::SETTING)->value('setting_value');
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        } catch (\Throwable $e) {
            log_error('[DirectoryListing] ' . $e->getMessage(), null, [], $e);
        }
        return self::defaultRoot();
    }

    public static function defaultRoot(): string
    {
        $env = getenv('DIRECTORIES_ROOT');
        $env = $env !== false ? $env : ($_ENV['DIRECTORIES_ROOT'] ?? '');
        if (trim((string) $env) !== '') {
            return trim((string) $env);
        }
        $candidate = dirname(__DIR__, 3) . '/public_html'; // app en ~/landing → ~/public_html
        return is_dir($candidate) ? $candidate : '';
    }

    /**
     * Valida la ruta y devuelve su realpath. Lanza InvalidArgumentException con un mensaje para el usuario.
     */
    public static function resolve(string $root): string
    {
        $root = trim($root);
        if ($root === '') {
            throw new \InvalidArgumentException('Indicá una ruta (ej: /home/cuenta/public_html).');
        }
        if (!str_starts_with($root, '/')) {
            throw new \InvalidArgumentException('La ruta tiene que ser absoluta (empezar con /).');
        }
        if (str_contains($root, "\0") || preg_match('#(^|/)\.\.(/|$)#', $root)) {
            throw new \InvalidArgumentException('La ruta no puede contener "..".');
        }
        $real = realpath($root);
        if ($real === false || !is_dir($real)) {
            throw new \InvalidArgumentException("La ruta {$root} no existe o no es un directorio.");
        }
        if ($real === '/') {
            throw new \InvalidArgumentException('No se puede listar la raíz del sistema (/).');
        }
        if (!is_readable($real)) {
            throw new \InvalidArgumentException("Sin permisos de lectura sobre {$real}.");
        }
        return $real;
    }

    /**
     * Subcarpetas de primer nivel de $root, ordenadas por nombre.
     *
     * @param array<string,string> $domains nombre de carpeta en minúsculas => dominio registrado
     * @return array<int,array{name:string,path:string,modified:int,symlink:bool,app:bool,domain:?string}>
     */
    public static function list(string $root, array $domains = []): array
    {
        $real = self::resolve($root);
        $entries = @scandir($real);
        if ($entries === false) {
            throw new \InvalidArgumentException("No se pudo leer {$real}.");
        }

        $dirs = [];
        foreach ($entries as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $real . '/' . $name;
            if (!is_dir($path)) {
                continue; // solo carpetas
            }
            $dirs[] = [
                'name'     => $name,
                'path'     => $path,
                'modified' => (int) @filemtime($path),
                'symlink'  => is_link($path),
                'app'      => self::looksLikeApp($path),
                'domain'   => $domains[strtolower($name)] ?? null,
            ];
        }
        usort($dirs, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
        return $dirs;
    }

    /** ¿Tiene una instalación de esta app? (carpeta landing/, o .env + index.php). */
    public static function looksLikeApp(string $path): bool
    {
        return is_dir($path . '/landing')
            || (is_file($path . '/.env') && (is_file($path . '/index.php') || is_file($path . '/public/index.php')));
    }

    /**
     * Nombre de carpeta (minúsculas) => dominio registrado, para asociar las carpetas
     * de public_html/* con su sitio: usa `folder` cuando está cargado y si no el
     * dominio (y su variante sin www), como hasta ahora.
     */
    public static function siteDomains(): array
    {
        try {
            $map = [];
            foreach (Capsule::table('sites')->get(['domain', 'folder']) as $row) {
                $d = strtolower((string) $row->domain);
                $map[$d] = $row->domain;
                $map[preg_replace('/^www\./', '', $d)] = $row->domain;
                // folder explícito gana sobre la coincidencia por dominio
                $folder = trim((string) ($row->folder ?? ''));
                if ($folder !== '') {
                    $map[strtolower(basename($folder))] = $row->domain;
                }
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
