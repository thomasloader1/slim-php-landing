<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\MenuSection;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Plantilla, exportación e importación masiva de productos (menu_items) en CSV.
 *
 * El CSV sale en UTF-8 con BOM y separador ";" para que Excel en español lo
 * abra directo en columnas y con acentos. Al importar se acepta ";", "," o TAB,
 * UTF-8 o Windows-1252 (lo que guarda Excel con "CSV (delimitado por comas)").
 *
 * Reglas de importación:
 *  - "codigo" coincide con un producto existente → se actualiza; si no → se crea.
 *  - Sólo se actualizan las columnas presentes en el archivo.
 *  - "foto_url" vacía no borra la foto actual del producto.
 *  - "stock" vacío = sin control de stock.
 *  - Categorías que no existen se crean.
 */
class ProductCsvService
{
    public const COLUMNS = ['codigo', 'categoria', 'nombre', 'precio', 'descripcion', 'foto_url', 'stock', 'activo'];
    public const REQUIRED = ['nombre', 'precio'];

    public const MAX_ROWS  = 2000;
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** DECIMAL(8,2) */
    public const MAX_PRICE = 999999.99;

    private const DELIMITER = ';';

    /** Encabezados alternativos (ya normalizados) → columna canónica */
    private const ALIASES = [
        'sku' => 'codigo', 'cod' => 'codigo', 'code' => 'codigo', 'codigo_sku' => 'codigo',
        'category' => 'categoria', 'seccion' => 'categoria', 'rubro' => 'categoria',
        'titulo' => 'nombre', 'name' => 'nombre', 'producto' => 'nombre', 'title' => 'nombre',
        'price' => 'precio',
        'description' => 'descripcion', 'detalle' => 'descripcion',
        'foto' => 'foto_url', 'imagen' => 'foto_url', 'image_url' => 'foto_url', 'url_foto' => 'foto_url',
        'foto_link' => 'foto_url', 'imagen_url' => 'foto_url', 'url_imagen' => 'foto_url',
        'estado' => 'activo', 'active' => 'activo', 'visible' => 'activo',
    ];

    /** Columna canónica → atributo del modelo */
    private const ATTRIBUTES = [
        'codigo' => 'sku', 'nombre' => 'title', 'precio' => 'price', 'descripcion' => 'description',
        'foto_url' => 'image_url', 'stock' => 'stock', 'activo' => 'active',
    ];

    // ── Generación ───────────────────────────────────────────────

    public function template(): string
    {
        return $this->toCsv([
            ['A-001', 'Bebidas', 'Agua mineral 500ml', '1200', 'Sin gas', 'https://ejemplo.com/agua.jpg', '24', 'si'],
            ['A-002', 'Bebidas', 'Gaseosa 1,5L', '2500,50', '', '', '', 'si'],
        ]);
    }

    /** @param iterable<MenuItem> $items con la relación section cargada */
    public function export(iterable $items): string
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                (string) $item->sku,
                (string) ($item->section->name ?? ''),
                (string) $item->title,
                number_format((float) $item->price, 2, ',', ''),
                (string) $item->description,
                (string) $item->image_url,
                $item->stock === null ? '' : (string) $item->stock,
                $item->active ? 'si' : 'no',
            ];
        }
        return $this->toCsv($rows);
    }

    private function toCsv(array $rows): string
    {
        $h = fopen('php://temp', 'r+');
        fputcsv($h, self::COLUMNS, self::DELIMITER, '"', '', "\r\n");
        foreach ($rows as $row) {
            fputcsv($h, array_map([self::class, 'escapeFormula'], $row), self::DELIMITER, '"', '', "\r\n");
        }
        rewind($h);
        $csv = stream_get_contents($h);
        fclose($h);
        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Evita inyección de fórmulas al abrir el CSV en Excel (=, +, -, @ al inicio).
     * Los números (precio/stock) nunca empiezan con esos caracteres.
     */
    public static function escapeFormula(string $value): string
    {
        return ($value !== '' && strpbrk($value[0], "=+-@\t\r") !== false) ? "'" . $value : $value;
    }

    // ── Lectura y validación (sin DB) ────────────────────────────

    /**
     * @return array{columns: string[], rows: list<array{line:int, data:array, errors:string[]}>, errors: string[]}
     */
    public function parse(string $content): array
    {
        $result = ['columns' => [], 'rows' => [], 'errors' => []];

        $content = self::toUtf8($content);
        $lines = preg_split('/\r\n|\n|\r/', $content, 2);
        // Excel a veces agrega "sep=;" como primera línea
        if (preg_match('/^sep=(.)$/i', trim($lines[0] ?? ''), $m)) {
            $delimiter = $m[1];
            $content = $lines[1] ?? '';
        } else {
            $delimiter = self::detectDelimiter($lines[0] ?? '');
        }

        $h = fopen('php://temp', 'r+');
        fwrite($h, $content);
        rewind($h);

        $header = fgetcsv($h, 0, $delimiter, '"', '');
        if ($header === false || $header === [null]) {
            fclose($h);
            $result['errors'][] = 'El archivo está vacío.';
            return $result;
        }

        // Índice de columna → columna canónica (ignora columnas desconocidas)
        $map = [];
        foreach ($header as $i => $name) {
            $key = self::normalizeHeader((string) $name);
            $key = self::ALIASES[$key] ?? $key;
            if (in_array($key, self::COLUMNS, true) && !in_array($key, $map, true)) {
                $map[$i] = $key;
            }
        }
        $result['columns'] = array_values($map);

        $missing = array_diff(self::REQUIRED, $map);
        if ($missing) {
            fclose($h);
            $result['errors'][] = 'Faltan columnas obligatorias: ' . implode(', ', $missing)
                . '. Descargá la plantilla y respetá los encabezados de la primera fila.';
            return $result;
        }

        $line = 1;
        $seenCodes = [];
        while (($cells = fgetcsv($h, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($cells === [null] || implode('', array_map(fn ($c) => trim((string) $c), $cells)) === '') {
                continue; // fila vacía
            }
            if (count($result['rows']) >= self::MAX_ROWS) {
                $result['errors'][] = 'El archivo supera el máximo de ' . self::MAX_ROWS . ' productos. Dividilo en varios archivos.';
                break;
            }

            $raw = [];
            foreach ($map as $i => $col) {
                $raw[$col] = self::unescapeFormula(trim((string) ($cells[$i] ?? '')));
            }
            [$data, $errors] = $this->validateRow($raw);

            if (($data['codigo'] ?? null) !== null) {
                $codeKey = mb_strtolower($data['codigo']);
                if (isset($seenCodes[$codeKey])) {
                    $errors[] = "Código repetido (ya aparece en la fila {$seenCodes[$codeKey]}).";
                } else {
                    $seenCodes[$codeKey] = $line;
                }
            }

            $result['rows'][] = ['line' => $line, 'data' => $data, 'errors' => $errors];
        }
        fclose($h);

        if (!$result['rows'] && !$result['errors']) {
            $result['errors'][] = 'El archivo no tiene productos (sólo encabezados).';
        }
        return $result;
    }

    /** @return array{0: array, 1: string[]} [datos normalizados, errores] */
    public function validateRow(array $raw): array
    {
        $data = [];
        $errors = [];

        if (array_key_exists('codigo', $raw)) {
            $v = $raw['codigo'];
            if (mb_strlen($v) > 64) {
                $errors[] = 'El código no puede superar 64 caracteres.';
            }
            $data['codigo'] = $v === '' ? null : $v;
        }

        if (array_key_exists('categoria', $raw)) {
            $v = (string) preg_replace('/\s+/u', ' ', $raw['categoria']);
            if (mb_strlen($v) > 150) {
                $errors[] = 'La categoría no puede superar 150 caracteres.';
            }
            $data['categoria'] = $v === '' ? null : $v;
        }

        $name = $raw['nombre'] ?? '';
        if ($name === '') {
            $errors[] = 'Falta el nombre.';
        } elseif (mb_strlen($name) > 200) {
            $errors[] = 'El nombre no puede superar 200 caracteres.';
        }
        $data['nombre'] = $name;

        $rawPrice = $raw['precio'] ?? '';
        $price = self::parsePrice($rawPrice);
        if ($rawPrice === '') {
            $errors[] = 'Falta el precio.';
        } elseif ($price === null || $price > self::MAX_PRICE) {
            $errors[] = "Precio inválido: \"{$rawPrice}\". Usá sólo números, ej: 1500 o 1500,50.";
        }
        $data['precio'] = $price;

        if (array_key_exists('descripcion', $raw)) {
            $data['descripcion'] = $raw['descripcion'];
        }

        if (array_key_exists('foto_url', $raw)) {
            $v = $raw['foto_url'];
            if ($v !== '' && !self::isHttpUrl($v)) {
                $errors[] = 'La foto debe ser un link que empiece con http:// o https://.';
            } elseif (mb_strlen($v) > 500) {
                $errors[] = 'El link de la foto no puede superar 500 caracteres.';
            }
            $data['foto_url'] = $v === '' ? null : $v;
        }

        if (array_key_exists('stock', $raw)) {
            $v = str_replace([' ', '.'], '', $raw['stock']);
            if ($v === '') {
                $data['stock'] = null;
            } elseif (preg_match('/^\d{1,9}$/', $v)) {
                $data['stock'] = (int) $v;
            } else {
                $errors[] = "Stock inválido: \"{$raw['stock']}\". Usá un número entero (0 o más) o dejalo vacío.";
                $data['stock'] = null;
            }
        }

        if (array_key_exists('activo', $raw)) {
            $active = $raw['activo'] === '' ? true : self::parseBool($raw['activo']);
            if ($active === null) {
                $errors[] = "Valor de \"activo\" inválido: \"{$raw['activo']}\". Usá si o no.";
            }
            $data['activo'] = $active ?? true;
        }

        return [$data, $errors];
    }

    /**
     * Interpreta precios en formato argentino o internacional:
     * "1500", "1500,50", "1.500", "1.500,50", "$ 1.500", "1500.50", "1,500.50".
     * Devuelve null si no es un número no negativo.
     */
    public static function parsePrice(string $value): ?float
    {
        $v = str_replace(['$', ' ', "\u{00A0}", 'ARS'], '', trim($value));
        if ($v === '' || !preg_match('/^\d[\d.,]*$/', $v)) {
            return null;
        }

        $lastDot = strrpos($v, '.');
        $lastComma = strrpos($v, ',');

        if ($lastDot !== false && $lastComma !== false) {
            // El último separador es el decimal
            $decimal = $lastDot > $lastComma ? '.' : ',';
            $thousand = $decimal === '.' ? ',' : '.';
            $v = str_replace($thousand, '', $v);
            $v = str_replace($decimal, '.', $v);
        } elseif ($lastComma !== false) {
            $v = substr_count($v, ',') > 1
                ? str_replace(',', '', $v)   // 1,000,000
                : str_replace(',', '.', $v); // 1500,50
        } elseif ($lastDot !== false && preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v); // 10.000 = diez mil
        }

        return is_numeric($v) ? round((float) $v, 2) : null;
    }

    public static function parseBool(string $value): ?bool
    {
        $v = self::normalizeHeader($value);
        if (in_array($v, ['si', 's', '1', 'true', 'x', 'yes', 'y', 'activo', 'verdadero'], true)) {
            return true;
        }
        if (in_array($v, ['no', 'n', '0', 'false', 'inactivo', 'falso'], true)) {
            return false;
        }
        return null;
    }

    public static function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', $value)));
        $v = strtr($v, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $v), '_');
    }

    public static function isHttpUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private static function unescapeFormula(string $value): string
    {
        return (strlen($value) > 1 && $value[0] === "'" && strpbrk($value[1], '=+-@') !== false)
            ? substr($value, 1)
            : $value;
    }

    private static function detectDelimiter(string $headerLine): string
    {
        $best = self::DELIMITER;
        $max = 0;
        foreach ([';', ',', "\t"] as $d) {
            $n = substr_count($headerLine, $d);
            if ($n > $max) {
                $best = $d;
                $max = $n;
            }
        }
        return $best;
    }

    private static function toUtf8(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        return $content;
    }

    // ── Importación (DB) ─────────────────────────────────────────

    /**
     * Marca cada fila como "create", "update" o "error" según su código.
     * @param array $parsed resultado de parse()
     */
    public function plan(array $parsed): array
    {
        $codes = [];
        foreach ($parsed['rows'] as $row) {
            if (($row['data']['codigo'] ?? null) !== null) {
                $codes[] = $row['data']['codigo'];
            }
        }
        $existing = [];
        foreach (array_chunk($codes, 500) as $chunk) {
            foreach (MenuItem::whereIn('sku', $chunk)->pluck('sku') as $sku) {
                $existing[mb_strtolower($sku)] = true;
            }
        }

        foreach ($parsed['rows'] as &$row) {
            $code = $row['data']['codigo'] ?? null;
            $row['action'] = $row['errors'] ? 'error'
                : ($code !== null && isset($existing[mb_strtolower($code)]) ? 'update' : 'create');
        }
        unset($row);

        // Categorías que la importación va a crear (una sola vez cada una)
        $known = [];
        foreach (MenuSection::pluck('name') as $name) {
            $known[self::categoryKey($name)] = true;
        }
        $parsed['new_categories'] = [];
        foreach ($parsed['rows'] as $row) {
            $cat = $row['data']['categoria'] ?? null;
            if ($row['action'] === 'error' || $cat === null) {
                continue;
            }
            $key = self::categoryKey($cat);
            if (!isset($known[$key])) {
                $known[$key] = true;
                $parsed['new_categories'][] = $cat;
            }
        }

        return $parsed;
    }

    /**
     * Clave para comparar nombres de categoría como lo hace MySQL (utf8mb4_unicode_ci):
     * sin mayúsculas, sin tildes y sin espacios extra. "Panadería " = "panaderia".
     */
    public static function categoryKey(string $name): string
    {
        $v = mb_strtolower(trim($name));
        $v = strtr($v, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c']);
        return (string) preg_replace('/\s+/u', ' ', $v);
    }

    /**
     * Crea/actualiza los productos en una transacción.
     * @param string[] $columns columnas presentes en el archivo
     * @param array[]  $rows    datos normalizados de filas SIN errores
     * @return array{created:int, updated:int, categories:int}
     */
    public function apply(array $columns, array $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'categories' => 0];

        Capsule::connection()->transaction(function () use ($columns, $rows, &$stats) {
            $sections = [];
            foreach (MenuSection::all() as $s) {
                $sections[self::categoryKey($s->name)] = $s->id;
            }
            $sectionOrder = (int) (MenuSection::max('sort_order') ?? 0);
            $itemOrder = (int) (MenuItem::max('sort_order') ?? 0);

            foreach ($rows as $data) {
                $attrs = [];
                foreach (self::ATTRIBUTES as $col => $attr) {
                    if (in_array($col, $columns, true) && array_key_exists($col, $data)) {
                        $attrs[$attr] = $data[$col];
                    }
                }
                if (array_key_exists('active', $attrs)) {
                    $attrs['active'] = $attrs['active'] ? 1 : 0;
                }
                if (array_key_exists('description', $attrs)) {
                    $attrs['description'] = (string) $attrs['description'];
                }

                if (in_array('categoria', $columns, true)) {
                    $cat = $data['categoria'] ?? null;
                    if ($cat === null) {
                        $attrs['section_id'] = null;
                    } else {
                        $key = self::categoryKey($cat);
                        if (!isset($sections[$key])) {
                            // La DB decide si ya existe (collation *_ci): evita violar el UNIQUE
                            $found = MenuSection::where('name', $cat)->value('id');
                            if ($found) {
                                $sections[$key] = $found;
                            } else {
                                $sections[$key] = MenuSection::create([
                                    'name' => $cat, 'sort_order' => ++$sectionOrder, 'active' => 1,
                                ])->id;
                                $stats['categories']++;
                            }
                        }
                        $attrs['section_id'] = $sections[$key];
                    }
                }

                $item = !empty($attrs['sku']) ? MenuItem::where('sku', $attrs['sku'])->first() : null;
                if ($item) {
                    // Foto vacía en el archivo: conservar la actual
                    if (array_key_exists('image_url', $attrs) && $attrs['image_url'] === null) {
                        unset($attrs['image_url']);
                    }
                    $item->update($attrs);
                    $stats['updated']++;
                } else {
                    MenuItem::create($attrs + [
                        'description' => '',
                        'active'      => 1,
                        'badge_type'  => 'none',
                        'sort_order'  => ++$itemOrder,
                    ]);
                    $stats['created']++;
                }
            }
        });

        return $stats;
    }
}
