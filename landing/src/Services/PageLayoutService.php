<?php

namespace App\Services;

use App\Support\HtmlSanitizer;
use App\Support\ImageUrl;

/**
 * UX Builder: define, valida y normaliza el layout de una página pública.
 *
 * Cada página (landing, shop) persiste su layout como JSON en settings:
 *   [{"id":"b_x","type":"links","visible":true,"data":{...}}, ...]
 *
 * - Bloques "core": envuelven secciones existentes de la página (logo, enlaces,
 *   productos…). Son únicos, no se borran, solo se ocultan o reordenan.
 *   Algunos tienen opciones propias (ej: columnas de la grilla de productos).
 * - Bloques "custom": componentes que el admin monta libremente (N instancias).
 */
class PageLayoutService
{
    /** Clave de la landing (compat: antes había una sola página). */
    public const SETTING_KEY = 'page_layout';

    public const MAX_BLOCKS = 40;
    public const MAX_SLIDES = 8;

    /** type => [label, icon, core, module setting que lo habilita] */
    public const BLOCK_TYPES = [
        // Core de la landing
        'profile'     => ['label' => 'Logo / Avatar',           'icon' => 'fa-image',             'core' => true,  'module' => 'module_avatar_enabled'],
        'title'       => ['label' => 'Título y Subtítulo',      'icon' => 'fa-heading',           'core' => true,  'module' => 'module_title_enabled'],
        'links'       => ['label' => 'Enlaces',                 'icon' => 'fa-link',              'core' => true,  'module' => 'module_links_enabled'],
        'bio'         => ['label' => 'Biografía',               'icon' => 'fa-align-left',        'core' => true,  'module' => 'module_bio_enabled'],
        'locations'   => ['label' => 'Ubicaciones',             'icon' => 'fa-map-location-dot',  'core' => true,  'module' => 'module_locations_enabled'],
        'faq'         => ['label' => 'Preguntas frecuentes',    'icon' => 'fa-circle-question',   'core' => true,  'module' => 'module_seo_enabled'],
        // Core de la tienda
        'shop_header' => ['label' => 'Encabezado de la tienda', 'icon' => 'fa-store',             'core' => true,  'module' => null],
        'products'    => ['label' => 'Productos',               'icon' => 'fa-table-cells-large', 'core' => true,  'module' => null],
        'payments'    => ['label' => 'Medios de pago y pie',    'icon' => 'fa-credit-card',       'core' => true,  'module' => null],
        // Componentes
        'banner'      => ['label' => 'Banner',                  'icon' => 'fa-rectangle-ad',      'core' => false, 'module' => null],
        'carousel'    => ['label' => 'Carrusel',                'icon' => 'fa-images',            'core' => false, 'module' => null],
        'heading'     => ['label' => 'Encabezado',              'icon' => 'fa-font',              'core' => false, 'module' => null],
        'text'        => ['label' => 'Texto',                   'icon' => 'fa-paragraph',         'core' => false, 'module' => null],
        'button'      => ['label' => 'Botón',                   'icon' => 'fa-hand-pointer',      'core' => false, 'module' => null],
        'map'         => ['label' => 'Mapa',                    'icon' => 'fa-map',               'core' => false, 'module' => null],
        'divider'     => ['label' => 'Separador',               'icon' => 'fa-minus',             'core' => false, 'module' => null],
    ];

    /**
     * Páginas editables: clave de settings, bloques core (en su orden por defecto)
     * y componentes permitidos.
     */
    public const PAGES = [
        'landing' => [
            'label'  => 'Landing',
            'key'    => 'page_layout',
            'path'   => '/',
            'core'   => ['profile', 'title', 'links', 'bio', 'locations', 'faq'],
            'custom' => ['banner', 'carousel', 'heading', 'text', 'button', 'map', 'divider'],
        ],
        'shop' => [
            'label'  => 'Tienda',
            'key'    => 'shop_layout',
            'path'   => '/shop',
            'core'   => ['shop_header', 'products', 'payments'],
            'custom' => ['banner', 'carousel', 'heading', 'text', 'button', 'divider'],
        ],
    ];

    private const ALIGNS = ['left', 'center', 'right'];
    private const SIZES  = ['sm', 'md', 'lg'];

    /**
     * Esquema de campos por tipo. Cada campo: [tipo, ...args]
     *   str  max            texto plano recortado
     *   html max            HTML del editor (lista blanca de la bio)
     *   url  max            link seguro (http/https/mailto/tel/relativo)
     *   img                 imagen: /uploads/… o https://
     *   icon max            clases Font Awesome
     *   enum values default
     *   bool default
     *   int  min max default
     *   slides              lista de diapositivas (carrusel)
     */
    private const FIELDS = [
        'heading' => [
            'text'  => ['str', 150],
            'size'  => ['enum', self::SIZES, 'md'],
            'align' => ['enum', self::ALIGNS, 'center'],
        ],
        'text' => [
            'text'  => ['html', 8000],
            'align' => ['enum', self::ALIGNS, 'center'],
        ],
        'button' => [
            'label' => ['str', 100],
            'url'   => ['url', 500],
            'icon'  => ['icon', 60],
            'style' => ['enum', ['glass', 'accent', 'outline'], 'glass'],
        ],
        'map' => [
            'title' => ['str', 150],
            'query' => ['str', 500],
        ],
        'divider' => [
            'style' => ['enum', ['line', 'space', 'dots'], 'line'],
        ],
        'banner' => [
            'image'        => ['img'],
            'image_mobile' => ['img'],
            'alt'          => ['str', 150],
            'title'        => ['str', 150],
            'subtitle'     => ['str', 300],
            'cta_label'    => ['str', 60],
            'cta_url'      => ['url', 500],
            'show_text'    => ['bool', true],
            'align'        => ['enum', self::ALIGNS, 'center'],
            'height'       => ['enum', self::SIZES, 'md'],
            'overlay'      => ['int', 0, 80, 35],
        ],
        'carousel' => [
            'slides'    => ['slides'],
            'show_text' => ['bool', true],
            'autoplay'  => ['bool', true],
            'interval'  => ['int', 3, 10, 5],
            'height'    => ['enum', self::SIZES, 'md'],
            'overlay'   => ['int', 0, 80, 35],
        ],
        'shop_header' => [
            'show_logo'     => ['bool', true],
            'show_title'    => ['bool', true],
            'show_subtitle' => ['bool', true],
            'show_back'     => ['bool', true],
            'align'         => ['enum', self::ALIGNS, 'center'],
        ],
        'products' => [
            'columns'              => ['enum', ['2', '3', '4'], '3'],
            'image_size'           => ['enum', ['lg', 'sm', 'none'], 'lg'],
            'card_ratio'           => ['enum', ['square', 'landscape', 'portrait'], 'square'],
            'show_search'          => ['bool', true],
            'show_filters'         => ['bool', true],
            'group_by_category'    => ['bool', true],
            'show_category_titles' => ['bool', true],
            'show_description'     => ['bool', true],
        ],
    ];

    private const SLIDE_FIELDS = [
        'image'     => ['img'],
        'alt'       => ['str', 150],
        'title'     => ['str', 150],
        'subtitle'  => ['str', 300],
        'cta_label' => ['str', 60],
        'cta_url'   => ['url', 500],
    ];

    private string $page;

    public function __construct(string $page = 'landing')
    {
        if (!isset(self::PAGES[$page])) {
            throw new \InvalidArgumentException("Página de layout desconocida: {$page}");
        }
        $this->page = $page;
    }

    public function page(): string
    {
        return $this->page;
    }

    /** Clave de settings donde se guarda el layout de esta página. */
    public function settingKey(): string
    {
        return self::PAGES[$this->page]['key'];
    }

    /** Tipos disponibles en esta página (core primero, luego componentes). */
    public function blockTypes(): array
    {
        $out = [];
        foreach (array_merge(self::PAGES[$this->page]['core'], self::PAGES[$this->page]['custom']) as $type) {
            $out[$type] = self::BLOCK_TYPES[$type];
        }
        return $out;
    }

    /** Orden por defecto: los bloques core de la página (para la landing, su orden histórico). */
    public function defaultLayout(): array
    {
        $layout = [];
        foreach (self::PAGES[$this->page]['core'] as $type) {
            $layout[] = ['id' => 'core_' . $type, 'type' => $type, 'visible' => true, 'data' => $this->sanitizeData($type, [])];
        }
        return $layout;
    }

    /** Decodifica el JSON guardado; ante cualquier problema vuelve al default. */
    public function decode(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return $this->defaultLayout();
        }
        $raw = json_decode($json, true);
        if (!is_array($raw)) {
            return $this->defaultLayout();
        }
        return $this->sanitize($raw);
    }

    public function encode(array $layout): string
    {
        return json_encode(array_values($layout), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Valida y normaliza un layout (venga del form o de la DB).
     * Garantiza: tipos válidos para la página, core únicos y siempre presentes,
     * ids únicos, campos acotados y URLs con esquema seguro.
     */
    public function sanitize(array $raw): array
    {
        $allowed = $this->blockTypes();
        $out = [];
        $seenCore = [];
        $seenIds = [];

        foreach (array_values($raw) as $block) {
            if (count($out) >= self::MAX_BLOCKS) {
                break;
            }
            if (!is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            if (!isset($allowed[$type])) {
                continue;
            }
            $isCore = $allowed[$type]['core'];
            if ($isCore) {
                if (isset($seenCore[$type])) {
                    continue;
                }
                $seenCore[$type] = true;
                $id = 'core_' . $type;
            } else {
                $id = (string) ($block['id'] ?? '');
                if (!preg_match('/^b_[a-z0-9]{4,24}$/', $id) || isset($seenIds[$id])) {
                    $id = $this->newId();
                }
            }
            $seenIds[$id] = true;

            $out[] = [
                'id'      => $id,
                'type'    => $type,
                'visible' => filter_var($block['visible'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'data'    => $this->sanitizeData($type, is_array($block['data'] ?? null) ? $block['data'] : []),
            ];
        }

        // Los bloques core nunca se pierden: si faltan, se agregan ocultos al final
        foreach (self::PAGES[$this->page]['core'] as $type) {
            if (!isset($seenCore[$type])) {
                $out[] = ['id' => 'core_' . $type, 'type' => $type, 'visible' => false, 'data' => $this->sanitizeData($type, [])];
            }
        }

        return $out;
    }

    /** ¿Se debe renderizar el bloque en la página pública? */
    public function isRenderable(array $block, array $settings): bool
    {
        if (empty($block['visible'])) {
            return false;
        }
        $module = self::BLOCK_TYPES[$block['type']]['module'] ?? null;
        if ($module === null) {
            return true;
        }
        // Mismos defaults que la landing original (todo encendido salvo que se apague)
        return ($settings[$module] ?? '1') === '1';
    }

    /** Bloques core sin esquema (los de la landing) no guardan datos: su contenido vive en settings. */
    private function sanitizeData(string $type, array $data): array
    {
        $clean = $this->sanitizeFields(self::FIELDS[$type] ?? [], $data);

        // Un carrusel solo muestra diapositivas con imagen
        if ($type === 'carousel') {
            $clean['slides'] = array_values(array_filter($clean['slides'], fn (array $s) => $s['image'] !== ''));
        }

        return $clean;
    }

    private function sanitizeFields(array $schema, array $data): array
    {
        $clean = [];
        foreach ($schema as $field => $spec) {
            $clean[$field] = $this->sanitizeField($spec, $data[$field] ?? null);
        }
        return $clean;
    }

    private function sanitizeField(array $spec, mixed $value): mixed
    {
        $kind = $spec[0];

        if ($kind === 'slides') {
            $slides = [];
            foreach (is_array($value) ? array_values($value) : [] as $slide) {
                if (count($slides) >= self::MAX_SLIDES) {
                    break;
                }
                if (is_array($slide)) {
                    $slides[] = $this->sanitizeFields(self::SLIDE_FIELDS, $slide);
                }
            }
            return $slides;
        }

        if ($kind === 'bool') {
            if ($value === null || $value === '') {
                return $spec[1];
            }
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $spec[1];
        }

        if ($kind === 'int') {
            [, $min, $max, $default] = $spec;
            if (!is_numeric($value)) {
                return $default;
            }
            return (int) max($min, min($max, (int) round((float) $value)));
        }

        $str = is_scalar($value) ? trim((string) $value) : '';

        switch ($kind) {
            case 'str':
                return mb_substr($str, 0, $spec[1]);
            case 'html':
                // Mismo editor y lista blanca que la bio: negrita, cursiva, listas y links seguros
                return HtmlSanitizer::bio(mb_substr($str, 0, $spec[1]));
            case 'url':
                return $this->safeUrl(mb_substr($str, 0, $spec[1]));
            case 'img':
                return ImageUrl::safe($str);
            case 'icon':
                // Solo clases de Font Awesome (evita inyectar atributos)
                $str = mb_substr($str, 0, $spec[1]);
                return preg_match('/^(fa-[a-z0-9-]+\s?)+$/', $str) ? $str : '';
            case 'enum':
                return in_array($str, $spec[1], true) ? $str : $spec[2];
        }
        return '';
    }

    /** Acepta http(s), mailto, tel y anclas/relativas; descarta javascript:, data:, etc. */
    private function safeUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }
        if (preg_match('#^(https?://|mailto:|tel:|/(?!/)|\#)#i', $url)) {
            return $url;
        }
        if (!preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) && !str_starts_with($url, '//')) {
            return 'https://' . $url; // "instagram.com/x" -> https://instagram.com/x
        }
        return '';
    }

    private function newId(): string
    {
        return 'b_' . bin2hex(random_bytes(5));
    }
}
