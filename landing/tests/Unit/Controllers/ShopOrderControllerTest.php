<?php

namespace Tests\Unit\Controllers;

use App\Controllers\Front\ShopOrderController;
use App\Models\MenuItem;
use App\Services\MigrationService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class ShopOrderControllerTest extends TestCase
{
    private static bool $migrated = false;
    private MenuItem $product;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            (new MigrationService())->migrate(Capsule::connection(), 'site');
            self::$migrated = true;
        }
        Capsule::connection()->beginTransaction();
        foreach (['menu_enabled' => '1', 'shop_enabled' => '1', 'module_menu_enabled' => '1', 'whatsapp_number' => '+54 9 11 1234-5678'] as $k => $v) {
            Capsule::table('settings')->updateOrInsert(['setting_key' => $k], ['setting_value' => $v]);
        }
        Capsule::table('rate_limits')->delete();
        $this->product = MenuItem::create(['title' => 'Empanada', 'price' => 900, 'description' => '', 'active' => 1, 'stock' => 10]);
    }

    protected function tearDown(): void
    {
        Capsule::connection()->rollBack();
        parent::tearDown();
    }

    private function post(array $body, array $headers = ['Content-Type' => 'application/json', 'Origin' => 'http://shop.test'], string $ip = '1.2.3.4'): array
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', 'http://shop.test/shop/orders', ['REMOTE_ADDR' => $ip])
            ->withBody((new StreamFactory())->createStream(json_encode($body)));
        foreach ($headers as $k => $v) {
            $request = $request->withHeader($k, $v);
        }
        $response = (new ShopOrderController())->store($request, new Response());
        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    private function validBody(array $extra = []): array
    {
        return $extra + ['name' => 'Juan', 'phone' => '11 5555-1234', 'website' => '', 'items' => [['id' => $this->product->id, 'qty' => 2]]];
    }

    /** @test */
    public function creates_order_and_returns_whatsapp_links_to_the_landing_number(): void
    {
        [$status, $json] = $this->post($this->validBody());

        $this->assertSame(201, $status);
        $this->assertMatchesRegularExpression('/^P-\d{6}$/', $json['code']);
        $this->assertSame('$1.800', $json['total']);
        $this->assertStringStartsWith('https://wa.me/5491112345678?text=', $json['wa_app']);
        $this->assertStringContainsString(rawurlencode('Pedido ' . $json['code']), $json['wa_app']);
        $this->assertStringStartsWith('https://web.whatsapp.com/send?phone=5491112345678', $json['wa_web']);
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    /** @test */
    public function closed_shop_or_missing_whatsapp_is_rejected(): void
    {
        Capsule::table('settings')->where('setting_key', 'shop_enabled')->update(['setting_value' => '0']);
        $this->assertSame(404, $this->post($this->validBody())[0]);

        Capsule::table('settings')->where('setting_key', 'shop_enabled')->update(['setting_value' => '1']);
        Capsule::table('settings')->where('setting_key', 'whatsapp_number')->update(['setting_value' => '']);
        $this->assertSame(503, $this->post($this->validBody())[0]);
    }

    /** @test */
    public function foreign_origin_form_posts_and_honeypot_are_rejected(): void
    {
        $this->assertSame(403, $this->post($this->validBody(), ['Content-Type' => 'application/json', 'Origin' => 'https://evil.com'])[0]);
        $this->assertSame(403, $this->post($this->validBody(), ['Content-Type' => 'application/x-www-form-urlencoded', 'Origin' => 'http://shop.test'])[0]);
        $this->assertSame(403, $this->post($this->validBody(), ['Content-Type' => 'application/json'])[0], 'sin Origin ni Referer');
        $this->assertSame(422, $this->post($this->validBody(['website' => 'spam']))[0]);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    /** @test */
    public function invalid_data_returns_errors_per_field_and_item(): void
    {
        [$status, $json] = $this->post(['name' => '', 'phone' => 'x', 'items' => [['id' => $this->product->id, 'qty' => 50]]]);
        $this->assertSame(422, $status);
        $this->assertArrayHasKey('name', $json['errors']);
        $this->assertArrayHasKey('phone', $json['errors']);

        [$status, $json] = $this->post($this->validBody(['items' => [['id' => $this->product->id, 'qty' => 11]]]));
        $this->assertSame(422, $status);
        $this->assertSame('Empanada: quedan 10.', $json['errors']["{$this->product->id}:1"]);
    }

    /** @test */
    public function too_many_orders_from_the_same_ip_are_throttled(): void
    {
        for ($i = 0; $i < ShopOrderController::MAX_ORDERS_PER_IP; $i++) {
            $this->assertSame(201, $this->post($this->validBody(['items' => [['id' => $this->product->id, 'qty' => 1]]]), ip: '9.9.9.9')[0]);
        }
        [$status, $json] = $this->post($this->validBody(['items' => [['id' => $this->product->id, 'qty' => 1]]]), ip: '9.9.9.9');
        $this->assertSame(429, $status);
        $this->assertStringContainsString('Esperá unos minutos', $json['error']);
    }
}
