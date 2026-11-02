<?php

namespace Tests\Unit\Services;

use App\Services\PageLayoutService;
use PHPUnit\Framework\TestCase;

class PageLayoutServiceTest extends TestCase
{
    private PageLayoutService $svc;

    protected function setUp(): void
    {
        $this->svc = new PageLayoutService();
    }

    private function types(array $layout): array
    {
        return array_column($layout, 'type');
    }

    public function test_empty_or_invalid_json_returns_historic_order(): void
    {
        $expected = ['profile', 'title', 'links', 'bio', 'locations', 'faq'];
        $this->assertSame($expected, $this->types($this->svc->decode(null)));
        $this->assertSame($expected, $this->types($this->svc->decode('')));
        $this->assertSame($expected, $this->types($this->svc->decode('{not json')));
    }

    public function test_keeps_custom_order_and_appends_missing_core_blocks_hidden(): void
    {
        $layout = $this->svc->sanitize([
            ['type' => 'links', 'visible' => true],
            ['type' => 'heading', 'id' => 'b_abc123', 'data' => ['text' => 'Hola']],
            ['type' => 'title', 'visible' => false],
        ]);

        $this->assertSame(['links', 'heading', 'title', 'profile', 'bio', 'locations', 'faq'], $this->types($layout));
        $this->assertFalse($layout[2]['visible']);
        $this->assertFalse($layout[3]['visible']); // profile faltaba -> oculto
        $this->assertSame('b_abc123', $layout[1]['id']);
        $this->assertSame('core_links', $layout[0]['id']);
    }

    public function test_drops_unknown_types_and_duplicate_core_blocks(): void
    {
        $layout = $this->svc->sanitize([
            ['type' => 'links'],
            ['type' => 'links'],
            ['type' => '<script>'],
            'basura',
        ]);
        $this->assertSame(1, count(array_filter($layout, fn ($b) => $b['type'] === 'links')));
        $this->assertNotContains('<script>', $this->types($layout));
    }

    public function test_regenerates_invalid_or_duplicate_custom_ids(): void
    {
        $layout = $this->svc->sanitize([
            ['type' => 'text', 'id' => 'b_same1'],
            ['type' => 'text', 'id' => 'b_same1'],
            ['type' => 'text', 'id' => '"><img>'],
        ]);
        $ids = array_column(array_slice($layout, 0, 3), 'id');
        $this->assertCount(3, array_unique($ids));
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^b_[a-z0-9]{4,24}$/', $id);
        }
    }

    public function test_button_url_and_icon_are_sanitized(): void
    {
        $sanitize = fn (array $data) => $this->svc->sanitize([['type' => 'button', 'data' => $data]])[0]['data'];

        $this->assertSame('', $sanitize(['url' => 'javascript:alert(1)'])['url']);
        $this->assertSame('', $sanitize(['url' => 'data:text/html,x'])['url']);
        $this->assertSame('', $sanitize(['url' => '//evil.example'])['url']);
        $this->assertSame('https://instagram.com/x', $sanitize(['url' => 'instagram.com/x'])['url']);
        $this->assertSame('mailto:a@b.com', $sanitize(['url' => 'mailto:a@b.com'])['url']);
        $this->assertSame('/menu', $sanitize(['url' => '/menu'])['url']);
        $this->assertSame('fa-calendar-check', $sanitize(['icon' => 'fa-calendar-check'])['icon']);
        $this->assertSame('', $sanitize(['icon' => 'fa-x" onclick="alert(1)'])['icon']);
        $this->assertSame('glass', $sanitize(['style' => 'neon'])['style']);
    }

    public function test_text_fields_are_trimmed_and_capped_and_enums_defaulted(): void
    {
        $block = $this->svc->sanitize([[
            'type' => 'heading',
            'data' => ['text' => '  ' . str_repeat('a', 500) . '  ', 'size' => 'xxl', 'align' => 'justify', 'extra' => 'x'],
        ]])[0];

        $this->assertSame(150, mb_strlen($block['data']['text']));
        $this->assertSame('md', $block['data']['size']);
        $this->assertSame('center', $block['data']['align']);
        $this->assertArrayNotHasKey('extra', $block['data']);
    }

    public function test_core_blocks_never_store_data(): void
    {
        $layout = $this->svc->sanitize([['type' => 'bio', 'data' => ['text' => 'x']]]);
        $this->assertSame([], $layout[0]['data']);
    }

    public function test_limits_number_of_blocks(): void
    {
        $raw = array_fill(0, 100, ['type' => 'divider']);
        $layout = $this->svc->sanitize($raw);
        // 40 permitidos + 6 core de la landing agregados al final
        $this->assertCount(PageLayoutService::MAX_BLOCKS + 6, $layout);
    }

    public function test_renderable_respects_visibility_and_module_flags(): void
    {
        $links = ['type' => 'links', 'visible' => true];
        $this->assertTrue($this->svc->isRenderable($links, []));
        $this->assertFalse($this->svc->isRenderable($links, ['module_links_enabled' => '0']));
        $this->assertFalse($this->svc->isRenderable(['type' => 'links', 'visible' => false], []));
        $this->assertTrue($this->svc->isRenderable(['type' => 'text', 'visible' => true], ['module_links_enabled' => '0']));
    }

    public function test_encode_decode_roundtrip(): void
    {
        $layout = $this->svc->sanitize([['type' => 'text', 'id' => 'b_abcd', 'data' => ['text' => 'Ñandú "hola"', 'align' => 'left']]]);
        $this->assertSame($layout, $this->svc->decode($this->svc->encode($layout)));
    }

    public function test_text_block_keeps_safe_formatting_and_drops_scripts(): void
    {
        $html = '<p><strong>Hola</strong> <a href="https://ej.com">link</a> <a href="javascript:alert(1)">x</a></p>'
            . '<ul><li>uno</li></ul><script>alert(1)</script><img src=x onerror=alert(1)>';
        $layout = $this->svc->sanitize([['type' => 'text', 'id' => 'b_rich1', 'data' => ['text' => $html]]]);
        $text = $layout[array_key_last($layout)]['data']['text'] ?? '';
        foreach ($layout as $b) { if (($b['id'] ?? '') === 'b_rich1') { $text = $b['data']['text']; } }

        $this->assertStringContainsString('<strong>Hola</strong>', $text);
        $this->assertStringContainsString('<a href="https://ej.com"', $text);
        $this->assertStringContainsString('>link</a>', $text);
        $this->assertStringContainsString('<ul><li>uno</li></ul>', $text);
        $this->assertStringNotContainsString('script', $text);
        $this->assertStringNotContainsString('javascript:', $text);
        $this->assertStringNotContainsString('onerror', $text);
        $this->assertSame($text, \App\Support\HtmlSanitizer::bio($text), 'sanitizar de nuevo no cambia nada');
    }

    /* ── Página tienda ──────────────────────────────────────────── */

    public function test_shop_default_layout_and_setting_key(): void
    {
        $shop = new PageLayoutService('shop');
        $this->assertSame('shop_layout', $shop->settingKey());
        $this->assertSame('page_layout', $this->svc->settingKey());
        $layout = $shop->decode(null);
        $this->assertSame(['shop_header', 'products', 'payments'], $this->types($layout));
        $this->assertSame('3', $layout[1]['data']['columns']);
        $this->assertTrue($layout[1]['data']['show_search']);
    }

    public function test_unknown_page_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PageLayoutService('admin');
    }

    public function test_pages_do_not_mix_core_blocks(): void
    {
        $shop = (new PageLayoutService('shop'))->sanitize([['type' => 'links'], ['type' => 'products'], ['type' => 'map']]);
        $this->assertNotContains('links', $this->types($shop));
        $this->assertNotContains('map', $this->types($shop), 'el mapa no es un componente de la tienda');

        $landing = $this->svc->sanitize([['type' => 'products'], ['type' => 'banner', 'id' => 'b_ban1']]);
        $this->assertNotContains('products', $this->types($landing));
        $this->assertContains('banner', $this->types($landing));
    }

    public function test_products_options_are_bounded(): void
    {
        $block = (new PageLayoutService('shop'))->sanitize([[
            'type' => 'products',
            'data' => ['columns' => '9', 'card_ratio' => 'circle', 'show_search' => false, 'show_filters' => '0', 'group_by_category' => 'nope'],
        ]])[0];
        $this->assertSame('3', $block['data']['columns']);
        $this->assertSame('square', $block['data']['card_ratio']);
        $this->assertFalse($block['data']['show_search']);
        $this->assertFalse($block['data']['show_filters']);
        $this->assertTrue($block['data']['group_by_category'], 'valor inválido → default');
        $this->assertSame('lg', $block['data']['image_size'], 'sin valor → imagen grande');

        $size = fn ($v) => (new PageLayoutService('shop'))->sanitize([['type' => 'products', 'data' => ['image_size' => $v]]])[0]['data']['image_size'];
        $this->assertSame('none', $size('none'));
        $this->assertSame('sm', $size('sm'));
        $this->assertSame('lg', $size('xl'));
    }

    public function test_banner_image_urls_and_options_are_sanitized(): void
    {
        $banner = fn (array $data) => $this->svc->sanitize([['type' => 'banner', 'id' => 'b_ban1', 'data' => $data]])[0]['data'];

        $this->assertSame('/uploads/banner_1.jpg', $banner(['image' => '/uploads/banner_1.jpg'])['image']);
        $this->assertSame('https://cdn.ej.com/a.webp', $banner(['image' => 'cdn.ej.com/a.webp'])['image']);
        $this->assertSame('', $banner(['image' => 'javascript:alert(1)'])['image']);
        $this->assertSame('', $banner(['image' => 'http://inseguro.com/a.jpg'])['image'], 'solo https');
        $this->assertSame('', $banner(['cta_url' => 'javascript:alert(1)'])['cta_url']);
        $this->assertSame(80, $banner(['overlay' => 500])['overlay']);
        $this->assertSame(35, $banner(['overlay' => 'x'])['overlay']);
        $this->assertFalse($banner(['show_text' => false])['show_text']);
        $this->assertTrue($banner([])['show_text']);
        $this->assertSame('md', $banner(['height' => 'xxl'])['height']);
    }

    public function test_carousel_slides_are_sanitized_capped_and_need_image(): void
    {
        $slides = array_fill(0, 12, ['image' => '/uploads/s.jpg', 'title' => 'Hola', 'cta_url' => 'javascript:x', 'extra' => 'y']);
        $slides[1] = ['title' => 'Sin imagen'];
        $slides[2] = 'basura';
        $data = $this->svc->sanitize([['type' => 'carousel', 'id' => 'b_car1', 'data' => ['slides' => $slides, 'interval' => 99]]])[0]['data'];

        $this->assertLessThanOrEqual(PageLayoutService::MAX_SLIDES, count($data['slides']));
        foreach ($data['slides'] as $s) {
            $this->assertSame('/uploads/s.jpg', $s['image']);
            $this->assertSame('', $s['cta_url']);
            $this->assertArrayNotHasKey('extra', $s);
        }
        $this->assertSame(10, $data['interval']);
        $this->assertSame([], $this->svc->sanitize([['type' => 'carousel', 'data' => ['slides' => 'x']]])[0]['data']['slides']);
    }

    public function test_shop_layout_roundtrip(): void
    {
        $shop = new PageLayoutService('shop');
        $layout = $shop->sanitize([
            ['type' => 'carousel', 'id' => 'b_car1', 'data' => ['slides' => [['image' => '/uploads/a.jpg', 'title' => 'Ñ']], 'show_text' => false]],
            ['type' => 'products', 'data' => ['columns' => '4']],
        ]);
        $this->assertSame($layout, $shop->decode($shop->encode($layout)));
    }

    public function test_plain_text_sanitizing_is_idempotent(): void
    {
        $once = \App\Support\HtmlSanitizer::bio("Ñandú \"hola\" & chau\nsegunda línea");
        $twice = \App\Support\HtmlSanitizer::bio($once);
        $this->assertStringContainsString('&amp; chau<br>', $once);
        $this->assertStringNotContainsString('&amp;quot;', $twice, 'no se escapa dos veces');
        $this->assertStringNotContainsString('&amp;amp;', $twice);
        $this->assertSame($twice, \App\Support\HtmlSanitizer::bio($twice), 'estable desde la segunda pasada');
        $this->assertSame(strip_tags($once), strip_tags(html_entity_decode($twice)) === strip_tags(html_entity_decode($once)) ? strip_tags($once) : 'distinto');
    }
}
