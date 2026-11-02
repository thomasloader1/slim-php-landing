<?php

namespace Tests\Unit\Seo;

use App\Controllers\Admin\SeoAdminController;
use App\Controllers\Front\SitemapController;
use App\Middleware\RobotsHeaderMiddleware;
use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Services\PageLayoutService;
use App\Services\StructuredDataService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * SEO / indexación: solo / y /shop son indexables; JSON-LD con URLs absolutas;
 * robots bloquea bots de entrenamiento IA pero no a buscadores/asistentes.
 */
class SeoIndexingTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'optica.test';
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    // ── helpers ────────────────────────────────────────────────────

    public function test_site_base_url_prefers_configured_url_and_falls_back_to_host(): void
    {
        $this->assertSame('https://optica.com.ar', site_base_url(['seo_site_url' => 'https://optica.com.ar/']));
        $this->assertSame('http://optica.test', site_base_url([]));

        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertSame('https://optica.test', site_base_url(['seo_site_url' => '']));
    }

    public function test_site_base_url_ignores_malicious_host_header(): void
    {
        $_SERVER['HTTP_HOST'] = 'evil.com/"><script>';
        $this->assertSame('http://localhost', site_base_url([]));
    }

    public function test_absolute_url_keeps_external_urls_and_prefixes_local_paths(): void
    {
        $s = ['seo_site_url' => 'https://optica.com.ar'];
        $this->assertSame('https://optica.com.ar/uploads/a.jpg', absolute_url('/uploads/a.jpg', $s));
        $this->assertSame('https://cdn.x/a.jpg', absolute_url('https://cdn.x/a.jpg', $s));
        $this->assertSame('', absolute_url('', $s));
    }

    public function test_seo_title_does_not_repeat_brand(): void
    {
        $s = ['site_name' => 'Optica Ramos Mejia'];
        $this->assertSame('Optica Ramos Mejia', seo_title('Optica Ramos Mejia', $s));
        $this->assertSame('Lentes | Optica Ramos Mejia', seo_title('Lentes', $s));
        $this->assertSame('Optica Ramos Mejia', seo_title('', $s));
    }

    public function test_seo_text_strips_html_and_truncates_on_word(): void
    {
        $this->assertSame('Hola mundo', seo_text("<p>Hola\n  <b>mundo</b></p>"));
        $out = seo_text(str_repeat('palabra ', 40), 50);
        $this->assertLessThanOrEqual(50, mb_strlen($out));
        $this->assertStringEndsWith('…', $out);
    }

    public function test_seo_lang_converts_locale(): void
    {
        $this->assertSame('es-AR', seo_lang(['seo_locale' => 'es_AR']));
        $this->assertSame('es-AR', seo_lang(['seo_locale' => '"><x']));
    }

    // ── JSON-LD ────────────────────────────────────────────────────

    public function test_landing_graph_for_local_business_is_absolute_and_linked(): void
    {
        $seo = new StructuredDataService([
            'seo_site_url'      => 'https://optica.com.ar',
            'site_name'         => 'Optica Ramos Mejia',
            'seo_schema_type'   => 'LocalBusiness',
            'seo_business_type' => 'Optician',
            'seo_address'       => 'Av. de Mayo 18, Ramos Mejía, Buenos Aires',
            'seo_description'   => '<p>Más de 40 años</p>',
            'landing_avatar_url'=> '/uploads/logo.jpg',
            'menu_brand_name'   => 'Tienda',
        ]);
        $links = [
            ['type' => 'social', 'url' => 'https://instagram.com/optica'],
            ['type' => 'phone',  'url' => 'https://wa.me/5491112345678'],
            ['type' => 'email',  'url' => 'mailto:hola@optica.com.ar'],
        ];
        $graph = $seo->landingGraph($links, [])['@graph'];
        $types = array_column($graph, '@type');
        $this->assertSame(['WebSite', 'WebPage', 'Optician'], $types);

        $entity = $graph[2];
        $this->assertSame('https://optica.com.ar/#entity', $entity['@id']);
        $this->assertSame('https://optica.com.ar/uploads/logo.jpg', $entity['image']);
        $this->assertSame('Más de 40 años', $entity['description']);
        $this->assertSame('+5491112345678', $entity['telephone']);
        $this->assertSame('hola@optica.com.ar', $entity['email']);
        $this->assertSame(['https://instagram.com/optica'], $entity['sameAs']);
        $this->assertSame('Ramos Mejía', $entity['address']['addressLocality']);
        // Formatos ambiguos no se parten (mejor calle completa que localidad equivocada)
        $odd = (new StructuredDataService(['seo_schema_type' => 'LocalBusiness', 'seo_address' => 'Calle 1, Local 4, CABA, BA']))->entityNode([], []);
        $this->assertSame('Calle 1, Local 4, CABA, BA', $odd['address']['streetAddress']);
        $this->assertArrayNotHasKey('addressLocality', $odd['address']);
        $this->assertSame('AR', $entity['address']['addressCountry']);
    }

    public function test_invalid_business_type_falls_back_to_local_business(): void
    {
        $seo = new StructuredDataService(['seo_schema_type' => 'LocalBusiness', 'seo_business_type' => '"><script>']);
        $this->assertSame('LocalBusiness', $seo->entityType());
        $this->assertSame('Person', (new StructuredDataService(['seo_schema_type' => 'Hack']))->entityType());
    }

    public function test_shop_graph_lists_products_with_offers_and_stock(): void
    {
        $seo = new StructuredDataService(['seo_site_url' => 'https://optica.com.ar', 'site_name' => 'Optica', 'seo_currency' => 'ARS']);

        $a = (new MenuItem())->forceFill(['id' => 1, 'sku' => 'AB 12', 'title' => 'Anteojo', 'price' => 1000, 'stock' => 0, 'image_url' => '/uploads/a.png']);
        $b = (new MenuItem())->forceFill(['id' => 2, 'sku' => null, 'title' => 'Mate', 'price' => 495, 'price_label_1' => 'Chico', 'price_2' => 990, 'price_label_2' => 'Grande', 'stock' => null]);
        $section = (new MenuSection())->forceFill(['id' => 9, 'name' => 'Sol']);
        $section->setRelation('activeItems', new Collection([$a]));

        $c = (new MenuItem())->forceFill(['id' => 3, 'sku' => 'X', 'title' => 'Consultar', 'price' => null]);

        $graph = $seo->shopGraph([$section], [$b, $c])['@graph'];
        $this->assertSame(['CollectionPage', 'BreadcrumbList', 'WebSite', 'Person', 'ItemList'], array_column($graph, '@type'));

        $list = $graph[4]['itemListElement'];
        // Sin precio: ListItem simple (Product sin offers es inválido para Google)
        $this->assertArrayNotHasKey('item', $list[2]);
        $this->assertSame('https://optica.com.ar/shop#prod-x', $list[2]['url']);

        $items = array_column($list, 'item');
        $this->assertSame('https://optica.com.ar/shop#prod-ab-12', $items[0]['url']);
        $this->assertSame('https://schema.org/OutOfStock', $items[0]['offers']['availability']);
        $this->assertSame('1000', $items[0]['offers']['price']);
        $this->assertSame('ARS', $items[0]['offers']['priceCurrency']);
        $this->assertSame('https://optica.com.ar/uploads/a.png', $items[0]['image']);
        $this->assertSame('Sol', $items[0]['category']);

        $this->assertSame('https://optica.com.ar/shop#prod-2', $items[1]['url']);
        // Una Offer por precio, con su etiqueta
        $this->assertSame(['Chico', 'Grande'], array_column($items[1]['offers'], 'name'));
        $this->assertSame(['495', '990'], array_column($items[1]['offers'], 'price'));
        $this->assertSame(['@id' => 'https://optica.com.ar/#entity'], $items[1]['offers'][0]['seller']);
    }

    public function test_shop_title_differs_from_landing_title(): void
    {
        $seo = new StructuredDataService(['site_name' => 'Optica', 'menu_brand_name' => 'Optica']);
        $this->assertSame('Tienda online | Optica', $seo->shopTitle());
    }

    public function test_faq_page_only_with_answered_questions(): void
    {
        $seo = new StructuredDataService([]);
        $this->assertNull($seo->faqPage([]));
        $faq = $seo->faqPage([(object) ['question' => '¿Hacen envíos?', 'answer' => '<b>Sí</b>, a todo el país.']]);
        $this->assertSame('FAQPage', $faq['@type']);
        $this->assertSame('Sí, a todo el país.', $faq['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_encode_cannot_break_out_of_script_tag(): void
    {
        $json = StructuredDataService::encode(['name' => '</script><script>alert(1)</script>']);
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertSame('</script><script>alert(1)</script>', json_decode($json, true)['name']);
    }

    // ── FAQ block ──────────────────────────────────────────────────

    public function test_faq_is_a_landing_core_block_hidden_for_existing_layouts(): void
    {
        $service = new PageLayoutService('landing');
        $this->assertArrayHasKey('faq', $service->blockTypes());

        // Layout guardado antes de existir el bloque: se agrega oculto (no cambia la landing en vivo)
        $layout = $service->decode('[{"type":"links","visible":true}]');
        $faq = array_values(array_filter($layout, fn ($b) => $b['type'] === 'faq'))[0];
        $this->assertFalse($faq['visible']);
    }

    // ── robots / noindex ───────────────────────────────────────────

    public function test_robots_blocks_training_bots_but_not_search_assistants(): void
    {
        $this->assertContains('GPTBot', SitemapController::AI_TRAINING_BOTS);
        $this->assertContains('Google-Extended', SitemapController::AI_TRAINING_BOTS);
        foreach (['Googlebot', 'Bingbot', 'OAI-SearchBot', 'ChatGPT-User', 'PerplexityBot', 'Claude-SearchBot', 'Claude-User'] as $bot) {
            $this->assertNotContains($bot, SitemapController::AI_TRAINING_BOTS, "$bot debe poder rastrear");
        }
        $this->assertContains('/admin', SitemapController::DISALLOWED_PATHS);
    }

    /** @dataProvider robotsHeaderCases */
    public function test_x_robots_tag_only_spares_landing_shop_and_crawler_files(string $method, string $path, int $status, bool $noindex): void
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, 'http://optica.test' . $path);
        $handler = new class ($status) implements RequestHandlerInterface {
            public function __construct(private int $status) {}
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse($this->status);
            }
        };

        $response = (new RobotsHeaderMiddleware())->process($request, $handler);
        $expected = $noindex ? 'noindex, nofollow' : ($path === '/llms.txt' ? 'noindex' : '');
        $this->assertSame($expected, $response->getHeaderLine('X-Robots-Tag'), "$method $path $status");
    }

    public static function robotsHeaderCases(): array
    {
        return [
            ['GET', '/', 200, false],
            ['GET', '/shop', 200, false],
            ['GET', '/shop/', 200, false],
            ['GET', '/robots.txt', 200, false],
            ['GET', '/sitemap.xml', 200, false],
            ['GET', '/llms.txt', 200, false],
            ['GET', '/llms.txt', 404, true],
            ['GET', '/shop', 404, true],
            ['GET', '/admin/login', 200, true],
            ['GET', '/admin', 302, true],
            ['GET', '/api/links', 200, true],
            ['GET', '/healthz', 200, true],
            ['GET', '/menu', 301, true],
            ['POST', '/shop/orders', 200, true],
            ['GET', '/no-existe', 404, true],
        ];
    }

    // ── admin ──────────────────────────────────────────────────────

    public function test_admin_normalizes_seo_values(): void
    {
        $this->assertSame('https://optica.com.ar', SeoAdminController::normalize('seo_site_url', ' optica.com.ar/ '));
        $this->assertSame('', SeoAdminController::normalize('seo_site_url', 'javascript:alert(1)'));
        $this->assertSame('Optician', SeoAdminController::normalize('seo_business_type', 'Optician'));
        $this->assertSame('', SeoAdminController::normalize('seo_business_type', 'Óptica <b>'));
        $this->assertSame('ARS', SeoAdminController::normalize('seo_currency', 'EUR'));
        $this->assertSame('optica', SeoAdminController::normalize('seo_twitter_handle', '@optica'));
        $this->assertSame('0', SeoAdminController::normalize('seo_noindex', 'yes'));
    }
}
