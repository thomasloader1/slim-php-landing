<?php

namespace App\Controllers\Front;

use App\Models\FaqItem;
use App\Models\Link;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Services\StructuredDataService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Archivos para crawlers, generados por tenant con su configuración:
 *   - robots.txt  → solo se rastrea la landing y el shop; bots de entrenamiento IA bloqueados.
 *   - sitemap.xml → landing + shop (si es público), con imágenes de productos.
 *   - llms.txt    → resumen en Markdown para buscadores/asistentes IA (llmstxt.org).
 */
class SitemapController
{
    /**
     * Bots que recolectan contenido para ENTRENAR modelos. Bloquearlos no afecta la aparición en
     * Google Search / AI Overviews (Googlebot), Bing/Copilot, ChatGPT search (OAI-SearchBot,
     * ChatGPT-User), Perplexity ni Claude search (Claude-SearchBot, Claude-User): esos caen en "*".
     */
    public const AI_TRAINING_BOTS = [
        'GPTBot',
        'ClaudeBot',
        'anthropic-ai',
        'Google-Extended',
        'Applebot-Extended',
        'CCBot',
        'Bytespider',
        'meta-externalagent',
        'cohere-training-data-crawler',
        'FacebookBot',
        'Diffbot',
        'Omgilibot',
        'ImagesiftBot',
        'Timpibot',
    ];

    /** Rutas públicas que no deben rastrearse (admin, API, salud, endpoints POST). */
    public const DISALLOWED_PATHS = ['/admin', '/api/', '/healthz', '/shop/orders'];

    /** Máximo de productos listados en llms.txt (evita archivos gigantes). */
    public const LLMS_MAX_PRODUCTS = 500;

    private function settings(): array
    {
        return Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
    }

    public function sitemap(Request $request, Response $response): Response
    {
        $settings = $this->settings();
        $base     = site_base_url($settings);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        if (seo_indexable($settings)) {
            $homeImages = array_filter([absolute_url(($settings['seo_og_image'] ?? '') ?: ($settings['landing_avatar_url'] ?? ''), $settings)]);
            $xml .= $this->urlEntry($base . '/', $this->lastmod(['settings', 'links', 'locations', 'faq_items']), $homeImages);

            if (shop_is_public($settings)) {
                $images = MenuItem::active()
                    ->whereNotNull('image_url')->where('image_url', '!=', '')
                    ->orderBy('sort_order')->limit(1000)
                    ->pluck('image_url')
                    ->map(fn ($u) => absolute_url($u, $settings))
                    ->unique()->values()->all();
                $xml .= $this->urlEntry($base . '/shop', $this->lastmod(['settings', 'menu_items', 'menu_sections']), $images);
            }
        }

        $xml .= '</urlset>' . "\n";
        $response->getBody()->write($xml);

        return $response
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    public function robots(Request $request, Response $response): Response
    {
        $settings = $this->settings();
        $base     = site_base_url($settings);
        $prefix   = rtrim(url(''), '/');

        $txt  = "# Solo se indexan la landing (/) y la tienda (/shop).\n";
        $txt .= "# Bots de entrenamiento de IA bloqueados; buscadores y asistentes IA (búsqueda/respuesta) permitidos.\n\n";

        foreach (self::AI_TRAINING_BOTS as $bot) {
            $txt .= "User-agent: {$bot}\n";
        }
        $txt .= "Disallow: /\n\n";

        $txt .= "User-agent: *\n";
        foreach (self::DISALLOWED_PATHS as $path) {
            $txt .= "Disallow: {$prefix}{$path}\n";
        }
        $txt .= "Allow: {$prefix}/\n";

        // Con noindex se permite el rastreo (para que lean el noindex) pero no se anuncia sitemap
        if (seo_indexable($settings)) {
            $txt .= "\nSitemap: {$base}/sitemap.xml\n";
        }

        $response->getBody()->write($txt);

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    /** /llms.txt — resumen Markdown del negocio, páginas, productos y FAQ para LLMs. */
    public function llms(Request $request, Response $response): Response
    {
        $settings = $this->settings();
        if (!seo_indexable($settings)) {
            $response->getBody()->write("Not found\n");
            return $response->withStatus(404)->withHeader('Content-Type', 'text/plain; charset=UTF-8');
        }

        $base = site_base_url($settings);
        $seo  = new StructuredDataService($settings);
        $name = trim((string) ($settings['site_name'] ?? '')) ?: (trim((string) ($settings['landing_title'] ?? '')) ?: 'Sitio');
        $shopPublic = shop_is_public($settings);
        $line = fn (string $s) => self::mdLabel($s);

        $md = "# {$line($name)}\n\n";
        $desc = $seo->description();
        if ($desc !== '') {
            $md .= "> {$desc}\n\n";
        }
        if (!empty($settings['landing_subtitle']) && seo_text($settings['landing_subtitle']) !== $desc) {
            $md .= seo_text($settings['landing_subtitle'], 300) . "\n\n";
        }

        // Páginas indexables
        $md .= "## Páginas\n\n";
        $md .= "- [Inicio]({$base}/): " . ($desc !== '' ? seo_text($desc, 140) : 'presentación, contacto y ubicaciones') . "\n";
        if ($shopPublic) {
            $md .= "- [{$line($seo->shopName())}]({$base}/shop): catálogo con precios y disponibilidad\n";
        }
        $md .= "\n";

        // Contacto (links activos) y ubicaciones
        $contact = [];
        foreach (Link::active()->orderBy('sort_order')->get() as $link) {
            $url = trim((string) $link->url);
            $title = $line((string) $link->title);
            if ($url === '' || $title === '' || !preg_match('#^(https?://|mailto:|tel:)#i', $url)) {
                continue;
            }
            $contact[] = '- [' . $title . '](' . self::mdUrl($url) . ')';
        }
        $places = [];
        foreach (Location::active()->orderBy('sort_order')->get() as $loc) {
            $label = $line((string) $loc->name);
            if ($loc->address) {
                $label .= ' — ' . $line((string) $loc->address);
            }
            $places[] = $loc->maps_link ? '- [' . $label . '](' . self::mdUrl($loc->maps_link) . ')' : "- {$label}";
        }
        if ($contact) {
            $md .= "## Contacto\n\n" . implode("\n", $contact) . "\n\n";
        }
        if ($places) {
            $md .= "## Ubicaciones\n\n" . implode("\n", $places) . "\n\n";
        }

        // Productos por categoría
        if ($shopPublic) {
            $md .= $this->llmsProducts($base, $seo);
        }

        // FAQ
        $faqs = FaqItem::active()->orderBy('sort_order')->get();
        if ($faqs->isNotEmpty()) {
            $md .= "## Preguntas frecuentes\n\n";
            foreach ($faqs as $faq) {
                $md .= '### ' . seo_text($faq->question, 300) . "\n\n" . seo_text($faq->answer, 1500) . "\n\n";
            }
        }

        $response->getBody()->write(rtrim($md) . "\n");

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    private function llmsProducts(string $base, StructuredDataService $seo): string
    {
        $currency = $seo->currency();
        $count = 0;
        $out = '';

        $render = function (MenuItem $item) use ($base, $currency, &$count): ?string {
            if ($count >= self::LLMS_MAX_PRODUCTS) {
                return null;
            }
            $count++;
            $prices = array_filter($item->all_prices, fn ($p) => $p > 0);
            $price = $prices
                ? (count($prices) > 1
                    ? 'desde ' . MenuItem::formatMoney(min($prices)) . ' ' . $currency
                    : MenuItem::formatMoney(reset($prices)) . ' ' . $currency)
                : 'consultar precio';
            $title = self::mdLabel((string) $item->title);
            $line = "- [{$title}]({$base}/shop#{$item->anchor}): {$price}";
            if ($item->is_out_of_stock) {
                $line .= ' (sin stock)';
            }
            $desc = seo_text($item->description ?? '', 160);
            if ($desc !== '') {
                $line .= ' — ' . $desc;
            }
            return $line;
        };

        $sections = MenuSection::active()->with('activeItems')->orderBy('sort_order')->orderBy('id')->get();
        foreach ($sections as $section) {
            $lines = array_filter(array_map($render, $section->activeItems->all()));
            if ($lines) {
                $out .= '### ' . self::mdLabel((string) $section->name) . "\n\n" . implode("\n", $lines) . "\n\n";
            }
        }
        $loose = MenuItem::active()->whereNull('section_id')->orderBy('sort_order')->orderBy('id')->get();
        $lines = array_filter(array_map($render, $loose->all()));
        if ($lines) {
            $out .= ($sections->isNotEmpty() ? "### Otros\n\n" : '') . implode("\n", $lines) . "\n\n";
        }

        return $out !== '' ? "## Productos\n\nPrecios en {$currency}. Pedidos desde [{$base}/shop]({$base}/shop).\n\n" . $out : '';
    }

    /** Texto seguro dentro de [..] en Markdown. */
    private static function mdLabel(string $s): string
    {
        return str_replace(['[', ']'], ['(', ')'], trim(preg_replace('/\s+/u', ' ', $s)));
    }

    /** URL segura dentro de (..) en Markdown. */
    private static function mdUrl(string $u): string
    {
        return str_replace([' ', '(', ')'], ['%20', '%28', '%29'], trim($u));
    }

    private function urlEntry(string $loc, string $lastmod, array $images): string
    {
        $xml  = "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        foreach (array_slice($images, 0, 1000) as $img) {
            $xml .= '    <image:image><image:loc>' . htmlspecialchars($img, ENT_XML1) . "</image:loc></image:image>\n";
        }
        $xml .= "  </url>\n";
        return $xml;
    }

    /** Fecha (W3C) del último cambio entre las tablas dadas; tablas inexistentes se ignoran. */
    private function lastmod(array $tables): string
    {
        $latest = null;
        foreach ($tables as $table) {
            try {
                $v = Capsule::table($table)->max('updated_at');
            } catch (\Throwable $e) {
                $v = null;
            }
            if ($v && ($latest === null || strtotime($v) > strtotime($latest))) {
                $latest = $v;
            }
        }
        return date('Y-m-d', $latest ? strtotime($latest) : time());
    }
}
