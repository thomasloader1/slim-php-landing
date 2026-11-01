<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DemoDataService;

class DemoDataServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/demo_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*.*'));
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    private function writeJson(string $filename, array $data): string
    {
        $path = $this->tempDir . '/' . $filename;
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        return $path;
    }

    /** @test */
    public function returns_empty_arrays_when_demo_mode_is_off()
    {
        $service = new DemoDataService(false, $this->writeJson('demo.json', [
            'links' => [['title' => 'Test Link']],
        ]));

        $this->assertSame([], $service->getLinks());
        $this->assertSame([], $service->getLocations());
        $this->assertSame([], $service->getMenuSections());
        $this->assertSame([], $service->getMenuItems());
        $this->assertSame([], $service->getFaqItems());
        $this->assertSame([], $service->getSettings());
    }

    /** @test */
    public function returns_links_with_correct_keys_when_demo_is_on()
    {
        $demoData = [
            'links' => [
                ['title' => 'Instagram', 'url' => 'https://instagram.com/demo', 'type' => 'url', 'icon' => 'fa-brands fa-instagram', 'color' => '#ffffff', 'bg_color' => null, 'sort_order' => 0, 'active' => 1],
                ['title' => 'Facebook', 'url' => 'https://facebook.com/demo', 'type' => 'url', 'icon' => 'fa-brands fa-facebook', 'color' => '#1877f2', 'bg_color' => null, 'sort_order' => 1, 'active' => 1],
            ],
        ];

        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));
        $links = $service->getLinks();

        $this->assertCount(2, $links);
        $this->assertSame('Instagram', $links[0]['title']);
        $this->assertSame('https://facebook.com/demo', $links[1]['url']);
        $this->assertArrayHasKey('icon', $links[0]);
        $this->assertArrayHasKey('sort_order', $links[1]);
        $this->assertArrayHasKey('active', $links[0]);
    }

    /** @test */
    public function returns_locations_with_correct_keys_when_demo_is_on()
    {
        $demoData = [
            'locations' => [
                ['name' => 'Local Centro', 'address' => 'Av. Siempre Viva 123', 'whatsapp' => '541112345678', 'whatsapp_message' => 'Hola!', 'embed_code' => null, 'url' => null, 'mode' => 'address', 'sort_order' => 0, 'active' => 1],
            ],
        ];

        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));
        $locations = $service->getLocations();

        $this->assertCount(1, $locations);
        $this->assertSame('Local Centro', $locations[0]['name']);
        $this->assertSame('Av. Siempre Viva 123', $locations[0]['address']);
        $this->assertArrayHasKey('whatsapp', $locations[0]);
        $this->assertArrayHasKey('sort_order', $locations[0]);
    }

    /** @test */
    public function returns_menu_sections_with_items_keys_when_demo_is_on()
    {
        $demoData = [
            'menu_sections' => [
                ['name' => 'Pizzas', 'icon' => 'fa-pizza', 'note' => 'Las mejores pizzas', 'note_type' => 'info', 'sort_order' => 0, 'active' => 1],
            ],
        ];

        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));
        $sections = $service->getMenuSections();

        $this->assertCount(1, $sections);
        $this->assertSame('Pizzas', $sections[0]['name']);
        $this->assertSame('fa-pizza', $sections[0]['icon']);
        $this->assertArrayHasKey('sort_order', $sections[0]);
    }

    /** @test */
    public function returns_menu_items_with_correct_keys_when_demo_is_on()
    {
        $demoData = [
            'menu_items' => [
                ['section_id' => 1, 'title' => 'Muzzarella', 'price' => '1500.00', 'description' => 'Pizza clásica', 'unit' => null, 'flavors' => null, 'badge_type' => null, 'sort_order' => 0, 'active' => 1],
            ],
        ];

        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));
        $items = $service->getMenuItems();

        $this->assertCount(1, $items);
        $this->assertSame('Muzzarella', $items[0]['title']);
        $this->assertSame('1500.00', $items[0]['price']);
        $this->assertArrayHasKey('section_id', $items[0]);
        $this->assertArrayHasKey('description', $items[0]);
    }

    /** @test */
    public function returns_faq_items_with_correct_keys_when_demo_is_on()
    {
        $demoData = [
            'faq' => [
                ['question' => '¿Cómo hago un pedido?', 'answer' => 'Llámanos por WhatsApp', 'sort_order' => 0, 'active' => 1],
            ],
        ];

        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));
        $faq = $service->getFaqItems();

        $this->assertCount(1, $faq);
        $this->assertSame('¿Cómo hago un pedido?', $faq[0]['question']);
        $this->assertSame('Llámanos por WhatsApp', $faq[0]['answer']);
        $this->assertArrayHasKey('sort_order', $faq[0]);
    }

    /** @test */
    public function returns_settings_as_key_value_pairs_when_demo_is_on()
    {
        $demoData = [
            'settings' => [
                'site_name' => 'Mi Demo',
                'landing_title' => 'Landing Demo',
                'landing_accent_color' => '#fec771',
            ],
        ];

        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));
        $settings = $service->getSettings();

        $this->assertCount(3, $settings);
        $this->assertSame('Mi Demo', $settings['site_name']);
        $this->assertSame('Landing Demo', $settings['landing_title']);
        $this->assertSame('#fec771', $settings['landing_accent_color']);
    }

    /** @test */
    public function returns_empty_arrays_when_json_file_is_missing()
    {
        $missingPath = $this->tempDir . '/nonexistent.json';
        $service = new DemoDataService(true, $missingPath);

        $this->assertSame([], $service->getLinks());
        $this->assertSame([], $service->getLocations());
        $this->assertSame([], $service->getMenuSections());
        $this->assertSame([], $service->getMenuItems());
        $this->assertSame([], $service->getFaqItems());
        $this->assertSame([], $service->getSettings());
    }

    /** @test */
    public function returns_empty_arrays_when_json_is_malformed()
    {
        $path = $this->tempDir . '/malformed.json';
        file_put_contents($path, '{invalid json}');
        $service = new DemoDataService(true, $path);

        $this->assertSame([], $service->getLinks());
        $this->assertSame([], $service->getSettings());
    }

    /** @test */
    public function returns_empty_array_for_missing_key_when_demo_is_on()
    {
        $demoData = ['links' => [['title' => 'Test']]];
        $service = new DemoDataService(true, $this->writeJson('demo.json', $demoData));

        $this->assertCount(1, $service->getLinks());
        $this->assertSame([], $service->getLocations());
        $this->assertSame([], $service->getMenuSections());
        $this->assertSame([], $service->getMenuItems());
        $this->assertSame([], $service->getFaqItems());
        $this->assertSame([], $service->getSettings());
    }

    /** @test */
    public function loads_json_file_only_once()
    {
        $demoData = [
            'links' => [['title' => 'Test Link']],
            'settings' => ['site_name' => 'Demo'],
        ];
        $path = $this->tempDir . '/demo.json';
        file_put_contents($path, json_encode($demoData));

        $service = new DemoDataService(true, $path);

        // First call
        $links = $service->getLinks();
        $this->assertCount(1, $links);

        // Modify the file on disk (simulate concurrent change)
        file_put_contents($path, json_encode(['links' => []]));

        // Second call should still return cached data
        $links2 = $service->getLinks();
        $this->assertCount(1, $links2, 'Service should cache parsed JSON and not re-read');
    }
}
