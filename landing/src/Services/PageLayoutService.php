<?php

namespace App\Services;

/**
 * UX Builder: define, valida y normaliza el layout de la landing.
 *
 * El layout se persiste como JSON en settings.page_layout:
 *   [{"id":"b_x","type":"links","visible":true,"data":{...}}, ...]
 *
 * - Bloques "core": envuelven secciones existentes (logo, título, enlaces, bio,
 *   ubicaciones). Son únicos, no se borran, solo se ocultan o reordenan.
 * - Bloques "custom": componentes que el admin monta libremente (N instancias).
 */
class PageLayoutService
{
    public const SETTING_KEY = 'page_layout';

    public const MAX_BLOCKS = 40;

    /** type => [label, icon, core, module setting que lo habilita] */
    public const BLOCK_TYPES = [
        'profile'   => ['label' => 'Logo / Avatar',      'icon' => 'fa-image',            'core' => true,  'module' => 'module_avatar_enabled'],
        'title'     => ['label' => 'Título y Subtítulo', 'icon' => 'fa-heading',          'core' => true,  'module' => 'module_title_enabled'],
        'links'     => ['label' => 'Enlaces',            'icon' => 'fa-link',             'core' => true,  'module' => 'module_links_enabled'],
        'bio'       => ['label' => 'Biografía',          'icon' => 'fa-align-left',       'core' => true,  'module' => 'module_bio_enabled'],
        'locations' => ['label' => 'Ubicaciones',        'icon' => 'fa-map-location-dot', 'core' => true,  'module' => 'module_locations_enabled'],
        'heading'   => ['label' => 'Encabezado',         'icon' => 'fa-font',             'core' => false, 'module' => null],
        'text'      => ['label' => 'Texto',              'icon' => 'fa-paragraph',        'core' => false, 'module' => null],
        'button'    => ['label' => 'Botón',              'icon' => 'fa-hand-pointer',     'core' => false, 'module' => null],
        'map'       => ['label' => 'Mapa',               'icon' => 'fa-map',              'core' => false, 'module' => null],
        'divider'   => ['label' => 'Separador',          'icon' => 'fa-minus',            'core' => false, 'module' => null],
    ];

    /** Campos permitidos por tipo custom: campo => longitud máxima */
    private const FIELDS = [
        'heading' => ['text' => 150, 'size' => 2, 'align' => 6],
        'text'    => ['text' => 8000, 'align' => 6],   // HTML del editor (se sanitiza)
        'button'  => ['label' => 100, 'url' => 500, 'icon' => 60, 'style' => 10],
        'map'     => ['title' => 150, 'query' => 500],
        'divider' => ['style' => 10],
    ];

    private const ALIGNS = ['left', 'center', 'right'];
    private const HEADING_SIZES = ['sm', 'md', 'lg'];
    private const BUTTON_STYLES = ['glass', 'accent', 'outline'];
    private const DIVIDER_STYLES = ['line', 'space', 'dots'];

    /** Orden histórico de la landing (antes del builder). */
    public function defaultLayout(): array
    {
        $layout = [];
        foreach (['profile', 'title', 'links', 'bio', 'locations'] as $type) {
            $layout[] = ['id' => 'core_' . $type, 'type' => $type, 'visible' => true, 'data' => []];
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
     * Garantiza: tipos válidos, core únicos y siempre presentes, ids únicos,
     * campos acotados y URLs con esquema seguro.
     */
    public function sanitize(array $raw): array
    {
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
            if (!isset(self::BLOCK_TYPES[$type])) {
                continue;
            }
            $isCore = self::BLOCK_TYPES[$type]['core'];
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
                'data'    => $isCore ? [] : $this->sanitizeData($type, is_array($block['data'] ?? null) ? $block['data'] : []),
            ];
        }

        // Los bloques core nunca se pierden: si faltan, se agregan ocultos al final
        foreach (self::BLOCK_TYPES as $type => $meta) {
            if ($meta['core'] && !isset($seenCore[$type])) {
                $out[] = ['id' => 'core_' . $type, 'type' => $type, 'visible' => false, 'data' => []];
            }
        }

        return $out;
    }

    /** ¿Se debe renderizar el bloque en la landing? */
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

    private function sanitizeData(string $type, array $data): array
    {
        $clean = [];
        foreach (self::FIELDS[$type] ?? [] as $field => $max) {
            $value = $data[$field] ?? '';
            $value = is_scalar($value) ? trim((string) $value) : '';
            $clean[$field] = mb_substr($value, 0, $max);
        }

        if ($type === 'text') {
            // Mismo editor y lista blanca que la bio: negrita, cursiva, listas y links seguros
            $clean['text'] = \App\Support\HtmlSanitizer::bio($clean['text']);
        }
        if (isset($clean['align']) && !in_array($clean['align'], self::ALIGNS, true)) {
            $clean['align'] = 'center';
        }
        if ($type === 'heading' && !in_array($clean['size'], self::HEADING_SIZES, true)) {
            $clean['size'] = 'md';
        }
        if ($type === 'button') {
            if (!in_array($clean['style'], self::BUTTON_STYLES, true)) {
                $clean['style'] = 'glass';
            }
            $clean['url'] = $this->safeUrl($clean['url']);
            // Solo clases de Font Awesome (evita inyectar atributos)
            if (!preg_match('/^(fa-[a-z0-9-]+\s?)+$/', $clean['icon'])) {
                $clean['icon'] = '';
            }
        }
        if ($type === 'divider' && !in_array($clean['style'], self::DIVIDER_STYLES, true)) {
            $clean['style'] = 'line';
        }

        return $clean;
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
