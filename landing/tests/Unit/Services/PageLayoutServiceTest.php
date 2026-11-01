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
        $expected = ['profile', 'title', 'links', 'bio', 'locations'];
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

        $this->assertSame(['links', 'heading', 'title', 'profile', 'bio', 'locations'], $this->types($layout));
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
        // 40 permitidos + 5 core agregados al final
        $this->assertCount(PageLayoutService::MAX_BLOCKS + 5, $layout);
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
}
