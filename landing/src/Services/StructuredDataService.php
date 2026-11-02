<?php

namespace App\Services;

use App\Models\MenuItem;

/**
 * Arma el JSON-LD (schema.org) de las páginas públicas indexables: landing (/) y tienda (/shop).
 *
 * Todo sale con URLs absolutas y @id estables para que Google y los buscadores IA
 * (ChatGPT search, Perplexity, Claude, AI Overviews) puedan unir negocio, sitio y productos.
 * Solo se describe contenido que la página realmente muestra (guías de Google).
 */
class StructuredDataService
{
    private const SCHEMA_TYPES = ['Person', 'Organization', 'LocalBusiness'];

    /** Tope de productos en el ItemList para no inflar el HTML. */
    public const MAX_PRODUCTS = 200;

    public function __construct(private array $settings)
    {
    }

    public function base(): string
    {
        return site_base_url($this->settings);
    }

    /** Tipo schema.org efectivo de la entidad principal (LocalBusiness puede ser subtipo: Optician, Restaurant…). */
    public function entityType(): string
    {
        $type = $this->settings['seo_schema_type'] ?? 'Person';
        if (!in_array($type, self::SCHEMA_TYPES, true)) {
            $type = 'Person';
        }
        if ($type === 'LocalBusiness') {
            $sub = trim((string) ($this->settings['seo_business_type'] ?? ''));
            if ($sub !== '' && preg_match('/^[A-Z][A-Za-z]{2,60}$/', $sub)) {
                return $sub;
            }
        }
        return $type;
    }

    /** Teléfono, email y redes sociales a partir de los links de la landing. */
    public function contactFromLinks(iterable $links): array
    {
        $out = ['sameAs' => [], 'telephone' => null, 'email' => null];
        foreach ($links as $link) {
            $type = is_array($link) ? ($link['type'] ?? '') : ($link->type ?? '');
            $url  = trim((string) (is_array($link) ? ($link['url'] ?? '') : ($link->url ?? '')));
            if ($url === '') {
                continue;
            }
            if ($type === 'social' && preg_match('#^https?://#i', $url)) {
                $out['sameAs'][] = $url;
            } elseif (in_array($type, ['phone', 'whatsapp'], true) && $out['telephone'] === null) {
                $tel = preg_replace('#^(tel:|https?://(wa\.me|api\.whatsapp\.com/send\?phone=)/?)#i', '', $url);
                $tel = preg_replace('/[^\d+]/', '', explode('?', $tel)[0]);
                if (strlen(ltrim($tel, '+')) >= 6) {
                    $out['telephone'] = str_starts_with($tel, '+') ? $tel : '+' . $tel;
                }
            } elseif ($type === 'email' && $out['email'] === null) {
                $mail = preg_replace('/^mailto:/i', '', explode('?', $url)[0]);
                if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                    $out['email'] = $mail;
                }
            }
        }
        $out['sameAs'] = array_values(array_unique($out['sameAs']));
        return $out;
    }

    /** Descripción de la entidad: la de SEO, o subtítulo / bio de la landing. */
    public function description(): string
    {
        return seo_text(
            ($this->settings['seo_description'] ?? '')
                ?: ($this->settings['landing_subtitle'] ?? '')
                ?: ($this->settings['landing_bio'] ?? ''),
            300
        );
    }

    public function image(): string
    {
        return absolute_url(
            ($this->settings['seo_og_image'] ?? '') ?: ($this->settings['landing_avatar_url'] ?? ''),
            $this->settings
        );
    }

    /**
     * Grafo de la landing: WebSite + WebPage + entidad (persona / organización / negocio local).
     *
     * @param iterable $links      LinkEntity|array activos
     * @param iterable $locations  Location activos (name, address, maps_link)
     */
    /**
     * Nodo de la entidad principal (persona / organización / negocio local).
     * Se repite en cada página para que un crawler que lee solo /shop igual sepa quién vende.
     */
    public function entityNode(iterable $links, iterable $locations): array
    {
        $home     = $this->base() . '/';
        $name     = $this->siteName();
        $type     = $this->entityType();
        $isPerson = $type === 'Person';
        $desc     = $this->description();
        $image    = $this->image();
        $contact  = $this->contactFromLinks($links);

        $entity = array_filter([
            '@type'       => $type,
            '@id'         => $home . '#entity',
            'name'        => $isPerson ? (($this->settings['landing_title'] ?? '') ?: $name) : $name,
            'description' => $desc ?: null,
            'url'         => $home,
            'image'       => $image ?: null,
            'logo'        => (!$isPerson && $image) ? $image : null,
            'telephone'   => $contact['telephone'],
            'email'       => $contact['email'],
            'sameAs'      => $contact['sameAs'] ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($isPerson) {
            return $entity;
        }

        $places = [];
        foreach ($locations as $loc) {
            $addr = trim((string) ($loc->address ?? ''));
            $place = array_filter([
                '@type'   => 'Place',
                'name'    => trim((string) ($loc->name ?? '')) ?: null,
                'address' => $addr !== '' ? $this->postalAddress($addr) : null,
                'hasMap'  => $loc->maps_link ?? null,
            ]);
            if (count($place) > 1) {
                $places[] = $place;
            }
        }

        $mainAddress = trim((string) ($this->settings['seo_address'] ?? ''));
        if ($mainAddress !== '') {
            $entity['address'] = $this->postalAddress($mainAddress);
        } elseif (isset($places[0]['address'])) {
            $entity['address'] = $places[0]['address'];
        }
        if (isset($places[0]['hasMap'])) {
            $entity['hasMap'] = $places[0]['hasMap'];
        }
        if (count($places) > 1) {
            $entity['location'] = $places;
        }

        return $entity;
    }

    public function siteName(): string
    {
        return trim((string) ($this->settings['site_name'] ?? '')) ?: trim((string) ($this->settings['landing_title'] ?? ''));
    }

    private function websiteNode(): array
    {
        $home = $this->base() . '/';
        return [
            '@type'      => 'WebSite',
            '@id'        => $home . '#website',
            'url'        => $home,
            'name'       => $this->siteName(),
            'inLanguage' => seo_lang($this->settings),
            'publisher'  => ['@id' => $home . '#entity'],
        ];
    }

    /**
     * Grafo de la landing: WebSite + WebPage + entidad.
     *
     * @param iterable $links      LinkEntity|array activos
     * @param iterable $locations  Location activos (name, address, maps_link)
     */
    public function landingGraph(iterable $links, iterable $locations): array
    {
        $home  = $this->base() . '/';
        $desc  = $this->description();
        $image = $this->image();

        $webpage = array_filter([
            '@type'       => 'WebPage',
            '@id'         => $home . '#webpage',
            'url'         => $home,
            'name'        => seo_title($this->settings['landing_title'] ?? '', $this->settings),
            'description' => $desc ?: null,
            'inLanguage'  => seo_lang($this->settings),
            'isPartOf'    => ['@id' => $home . '#website'],
            'about'       => ['@id' => $home . '#entity'],
            'primaryImageOfPage' => $image ? ['@type' => 'ImageObject', 'url' => $image] : null,
        ]);

        return ['@context' => 'https://schema.org', '@graph' => [$this->websiteNode(), $webpage, $this->entityNode($links, $locations)]];
    }

    /** FAQPage: solo se emite si el bloque FAQ visible está en la página. */
    public function faqPage(iterable $faqItems): ?array
    {
        $questions = [];
        foreach ($faqItems as $faq) {
            $q = seo_text($faq->question ?? '', 300);
            $a = seo_text($faq->answer ?? '', 5000);
            if ($q !== '' && $a !== '') {
                $questions[] = [
                    '@type' => 'Question',
                    'name'  => $q,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
                ];
            }
        }
        if (!$questions) {
            return null;
        }
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            '@id'        => $this->base() . '/#faq',
            'isPartOf'   => ['@id' => $this->base() . '/#webpage'],
            'inLanguage' => seo_lang($this->settings),
            'mainEntity' => $questions,
        ];
    }

    public function shopName(): string
    {
        return trim((string) (($this->settings['menu_brand_name'] ?? '') ?: ($this->settings['menu_header_text'] ?? ''))) ?: 'Tienda';
    }

    /**
     * Título del shop distinto al de la landing (títulos duplicados compiten entre sí):
     * si la marca del shop es igual al nombre del sitio → "Tienda online | Marca".
     */
    public function shopTitle(): string
    {
        $name = $this->shopName();
        $site = trim((string) ($this->settings['site_name'] ?? ''));
        if ($site !== '' && mb_strtolower($name) === mb_strtolower($site)) {
            $name = 'Tienda online';
        }
        return seo_title($name, $this->settings);
    }

    /** Descripción del shop: la propia, o una armada con las categorías. */
    public function shopDescription(iterable $sections, iterable $unsectioned = []): string
    {
        $own = seo_text($this->settings['seo_shop_description'] ?? '', 300);
        if ($own !== '') {
            return $own;
        }
        $names = [];
        foreach ($sections as $s) {
            if (!empty($s->name)) {
                $names[] = trim($s->name);
            }
        }
        if (!$names) {
            // Sin categorías: usar los nombres de los productos
            foreach ($unsectioned as $item) {
                if (!empty($item->title) && count($names) < 8) {
                    $names[] = trim($item->title);
                }
            }
        }
        $site = trim((string) ($this->settings['site_name'] ?? ''));
        if ($names) {
            return seo_text('Catálogo' . ($site ? ' de ' . $site : '') . ': ' . implode(', ', array_slice($names, 0, 8)) . '. Precios y disponibilidad actualizados.', 160);
        }
        return seo_text(($this->settings['menu_header_text'] ?? '') ?: ('Catálogo de productos' . ($site ? ' de ' . $site : '')), 160);
    }

    public function currency(): string
    {
        $c = strtoupper(trim((string) ($this->settings['seo_currency'] ?? 'ARS')));
        return preg_match('/^[A-Z]{3}$/', $c) ? $c : 'ARS';
    }

    /**
     * Grafo del shop: CollectionPage + BreadcrumbList + ItemList de Product.
     *
     * @param iterable $sections    MenuSection con relación activeItems
     * @param iterable $unsectioned MenuItem sin sección
     * @param iterable $links/$locations para repetir la entidad (vendedor) en esta página
     */
    public function shopGraph(iterable $sections, iterable $unsectioned, iterable $links = [], iterable $locations = []): array
    {
        $base    = $this->base();
        $shopUrl = $base . '/shop';

        $elements = [];
        $pos = 0;
        $add = function (MenuItem $item, ?string $category) use (&$elements, &$pos, $shopUrl) {
            if ($pos >= self::MAX_PRODUCTS) {
                return;
            }
            $product = $this->product($item, $category, $shopUrl);
            $elements[] = $product
                ? ['@type' => 'ListItem', 'position' => ++$pos, 'item' => $product]
                : ['@type' => 'ListItem', 'position' => ++$pos, 'name' => trim((string) $item->title), 'url' => $shopUrl . '#' . $item->anchor];
        };

        foreach ($sections as $section) {
            foreach ($section->activeItems ?? [] as $item) {
                $add($item, $section->name ?? null);
            }
        }
        foreach ($unsectioned as $item) {
            $add($item, null);
        }

        $graph = [
            array_filter([
                '@type'       => 'CollectionPage',
                '@id'         => $shopUrl . '#webpage',
                'url'         => $shopUrl,
                'name'        => $this->shopTitle(),
                'description' => $this->shopDescription($sections, $unsectioned) ?: null,
                'inLanguage'  => seo_lang($this->settings),
                'isPartOf'    => ['@id' => $base . '/#website'],
                'about'       => ['@id' => $base . '/#entity'],
                'breadcrumb'  => ['@id' => $shopUrl . '#breadcrumb'],
                'mainEntity'  => $elements ? ['@id' => $shopUrl . '#products'] : null,
            ]),
            [
                '@type' => 'BreadcrumbList',
                '@id'   => $shopUrl . '#breadcrumb',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $base . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $this->shopName(), 'item' => $shopUrl],
                ],
            ],
        ];

        $graph[] = $this->websiteNode();
        $graph[] = $this->entityNode($links, $locations);

        if ($elements) {
            $graph[] = [
                '@type'           => 'ItemList',
                '@id'             => $shopUrl . '#products',
                'numberOfItems'   => count($elements),
                'itemListElement' => $elements,
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    /** Product con una Offer por precio (con su etiqueta: "500g", "Grande"…); sin precio → null. */
    private function product(MenuItem $item, ?string $category, string $shopUrl): ?array
    {
        $url = $shopUrl . '#' . $item->anchor;
        $availability = $item->is_out_of_stock ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock';

        $offers = [];
        foreach ([[$item->price, $item->price_label_1], [$item->price_2, $item->price_label_2], [$item->price_3, $item->price_label_3]] as [$price, $label]) {
            if ($price === null || $price === '' || (float) $price <= 0) {
                continue;
            }
            $offers[] = array_filter([
                '@type'         => 'Offer',
                'name'          => trim((string) $label) ?: null,
                'price'         => $this->money((float) $price),
                'priceCurrency' => $this->currency(),
                'availability'  => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
                'url'           => $url,
                'seller'        => ['@id' => $this->base() . '/#entity'],
            ]);
        }
        if (!$offers) {
            return null; // Google exige offers/review/rating en Product
        }

        return array_filter([
            '@type'       => 'Product',
            '@id'         => $url,
            'name'        => trim((string) $item->title),
            'description' => seo_text($item->description ?? '', 500) ?: null,
            'image'       => $item->image_url ? absolute_url($item->image_url, $this->settings) : null,
            'sku'         => trim((string) $item->sku) ?: null,
            'brand'       => ($b = $this->siteName()) !== '' ? ['@type' => 'Brand', 'name' => $b] : null,
            'category'    => $category ?: null,
            'url'         => $url,
            'offers'      => count($offers) === 1 ? $offers[0] : $offers,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function money(float $v): string
    {
        return number_format($v, abs($v - round($v)) >= 0.005 ? 2 : 0, '.', '');
    }

    /**
     * "Av. Rivadavia 123, Ramos Mejía, Buenos Aires" (exactamente 3 partes) → calle/localidad/provincia.
     * Cualquier otro formato va completo en streetAddress (mejor que adivinar mal la localidad).
     */
    private function postalAddress(string $address): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        $out = ['@type' => 'PostalAddress', 'streetAddress' => $address];
        if (count($parts) === 3) {
            $out['streetAddress']   = $parts[0];
            $out['addressLocality'] = $parts[1];
            $out['addressRegion']   = $parts[2];
        }
        $country = strtoupper(substr((string) ($this->settings['seo_locale'] ?? 'es_AR'), 3, 2));
        if (preg_match('/^[A-Z]{2}$/', $country)) {
            $out['addressCountry'] = $country;
        }
        return $out;
    }

    /** JSON seguro para <script type="application/ld+json"> (no rompe con "</script>"). */
    public static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
            ?: '{}';
    }
}
