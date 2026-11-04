<?php

namespace Tests\Unit\Controllers;

use App\Controllers\Front\ShopCartSessionController;
use App\Models\MenuItem;
use App\Services\MigrationService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

class ShopCartSessionControllerTest extends TestCase
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
        foreach (['menu_enabled' => '1', 'shop_enabled' => '1', 'module_menu_enabled' => '1', 'module_orders_enabled' => '1', 'whatsapp_number' => '+54 9 11 1234-5678'] as $k => $v) {
            Capsule::table('settings')->updateOrInsert(['setting_key' => $k], ['setting_value' => $v]);
        }
        $this->product = MenuItem::create(['title' => 'Empanada', 'price' => 900, 'description' => '', 'active' => 1, 'stock' => 10]);
        // Iniciar sesión para que $_SESSION esté disponible
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        Capsule::connection()->rollBack();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    private function post(array $body, array $headers = ['Content-Type' => 'application/json', 'Origin' => 'http://shop.test'], string $ip = '1.2.3.4'): array
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', 'http://shop.test/shop/cart/save', ['REMOTE_ADDR' => $ip])
            ->withBody((new StreamFactory())->createStream(json_encode($body)));
        foreach ($headers as $k => $v) {
            $request = $request->withHeader($k, $v);
        }
        // Simular contexto de tenant (master)
        $request = $request->withAttribute('context', 'master');
        $response = (new ShopCartSessionController())->save($request, new Response());
        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    private function getSession(array $headers = ['Origin' => 'http://shop.test']): array
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://shop.test/shop/cart/session');
        foreach ($headers as $k => $v) {
            $request = $request->withHeader($k, $v);
        }
        // Simular contexto de tenant (master)
        $request = $request->withAttribute('context', 'master');
        $response = (new ShopCartSessionController())->session($request, new Response());
        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    private function validItems(array $extra = []): array
    {
        return $extra + ['items' => [['id' => $this->product->id, 'variant' => 1, 'qty' => 2]]];
    }

    /** @test */
    public function saves_cart_to_session_and_returns_normalized_cart(): void
    {
        [$status, $json] = $this->post($this->validItems());

        $this->assertSame(200, $status);
        $this->assertArrayHasKey('saved', $json);
        $this->assertTrue($json['saved']);
        $this->assertSame(1, $json['items_count']);
        $this->assertSame(2, $json['units']);

        // Verificar que se guardó en $_SESSION con el nuevo esquema
        $this->assertArrayHasKey('shop_cart', $_SESSION);
        $sessionCart = $_SESSION['shop_cart'];
        $this->assertSame('master', $sessionCart['tenant_id']);
        $this->assertArrayHasKey('items', $sessionCart);
        $this->assertArrayHasKey('updated_at', $sessionCart);
        $this->assertArrayHasKey('version', $sessionCart);
        $this->assertSame(1, $sessionCart['version']);
        $this->assertCount(1, $sessionCart['items']);
        $this->assertSame($this->product->id, $sessionCart['items'][0]['id']);
        $this->assertSame(1, $sessionCart['items'][0]['variant']);
        $this->assertSame(2, $sessionCart['items'][0]['qty']);
        $this->assertSame((float) $this->product->price, $sessionCart['items'][0]['price']);
        $this->assertSame(10, $sessionCart['items'][0]['max']);
    }

    /** @test */
    public function rejects_when_shop_closed(): void
    {
        Capsule::table('settings')->where('setting_key', 'shop_enabled')->update(['setting_value' => '0']);
        $this->assertSame(503, $this->post($this->validItems())[0]);
    }

    /** @test */
    public function rejects_invalid_content_type_or_origin(): void
    {
        $this->assertSame(403, $this->post($this->validItems(), ['Content-Type' => 'application/json', 'Origin' => 'https://evil.com'])[0]);
        $this->assertSame(403, $this->post($this->validItems(), ['Content-Type' => 'application/x-www-form-urlencoded', 'Origin' => 'http://shop.test'])[0]);
        $this->assertSame(403, $this->post($this->validItems(), ['Content-Type' => 'application/json'])[0]);
    }

    /** @test */
    public function rejects_invalid_payload(): void
    {
        $this->assertSame(400, $this->post(['items' => 'not-an-array'])[0]);
        $this->assertSame(400, $this->post([])[0]);
    }

    /** @test */
    public function validates_items_against_tenant_menu_items(): void
    {
        // Producto inexistente
        [$status, $json] = $this->post(['items' => [['id' => 99999, 'variant' => 1, 'qty' => 1]]]);
        $this->assertSame(422, $status);
        $this->assertArrayHasKey('errors', $json);
        $this->assertStringContainsString('ya no está disponible', $json['errors']['99999:1']);
    }

    /** @test */
    public function validates_variant_exists_and_price(): void
    {
        // Producto sin variante 2
        [$status, $json] = $this->post(['items' => [['id' => $this->product->id, 'variant' => 2, 'qty' => 1]]]);
        $this->assertSame(422, $status);
        $this->assertArrayHasKey('errors', $json);
        $this->assertStringContainsString('esa opción', $json['errors']["{$this->product->id}:2"]);
    }

    /** @test */
    public function validates_stock(): void
    {
        // Pedir más de lo que hay en stock
        [$status, $json] = $this->post(['items' => [['id' => $this->product->id, 'variant' => 1, 'qty' => 20]]]);
        $this->assertSame(422, $status);
        $this->assertArrayHasKey('errors', $json);
        $this->assertStringContainsString('quedan 10', $json['errors']["{$this->product->id}:1"]);
    }

    /** @test */
    public function ignores_inactive_products(): void
    {
        $inactive = MenuItem::create(['title' => 'Viejo', 'price' => 100, 'description' => '', 'active' => 0]);
        [$status, $json] = $this->post(['items' => [['id' => $inactive->id, 'variant' => 1, 'qty' => 1]]]);
        $this->assertSame(422, $status);
        $this->assertStringContainsString('ya no está disponible', $json['errors']["{$inactive->id}:1"]);
    }

    /** @test */
    public function normalizes_and_groups_same_product_variant(): void
    {
        [$status, $json] = $this->post(['items' => [
            ['id' => $this->product->id, 'variant' => 1, 'qty' => 1],
            ['id' => $this->product->id, 'variant' => 1, 'qty' => 2],
        ]]);

        $this->assertSame(200, $status);
        $this->assertCount(1, $_SESSION['shop_cart']['items']);
        $this->assertSame(3, $_SESSION['shop_cart']['items'][0]['qty']);
    }

    /** @test */
    public function empty_items_clears_session_cart(): void
    {
        // Primero guardar algo
        $this->post($this->validItems());
        $this->assertNotEmpty($_SESSION['shop_cart']['items']);

        // Luego vaciar
        [$status, $json] = $this->post(['items' => []]);
        $this->assertSame(200, $status);
        $this->assertSame([], $json);
        $this->assertSame([], $_SESSION['shop_cart']['items'] ?? []);
    }

    /** @test */
    public function validates_qty_and_variant_bounds(): void
    {
        // qty <= 0 se ignora
        [$status, $json] = $this->post(['items' => [['id' => $this->product->id, 'variant' => 1, 'qty' => 0]]]);
        $this->assertSame(200, $status);
        $this->assertSame([], $_SESSION['shop_cart']['items'] ?? []);

        // variant fuera de rango se ignora
        [$status, $json] = $this->post(['items' => [['id' => $this->product->id, 'variant' => 5, 'qty' => 1]]]);
        $this->assertSame(200, $status);
        $this->assertSame([], $_SESSION['shop_cart']['items'] ?? []);
    }

    /** @test */
    public function validates_max_items_per_order(): void
    {
        Capsule::table('settings')->where('setting_key', 'shop_max_items')->update(['setting_value' => '5']);
        [$status, $json] = $this->post(['items' => [['id' => $this->product->id, 'variant' => 1, 'qty' => 10]]]);
        $this->assertSame(422, $status);
        $this->assertArrayHasKey('errors', $json);
        $this->assertStringContainsString('hasta 5 unidades', $json['errors']['cart']);
    }

    /** @test */
    public function session_returns_empty_when_no_session_cart(): void
    {
        [$status, $json] = $this->getSession();
        $this->assertSame(200, $status);
        $this->assertSame([], $json['items']);
        $this->assertSame(0, $json['updated_at']);
    }

    /** @test */
    public function session_returns_session_cart_when_tenant_matches(): void
    {
        // Guardar carrito primero
        $this->post($this->validItems());
        $this->assertNotEmpty($_SESSION['shop_cart']['items']);

        // Cargar
        [$status, $json] = $this->getSession();
        $this->assertSame(200, $status);
        $this->assertCount(1, $json['items']);
        $this->assertSame($this->product->id, $json['items'][0]['id']);
        $this->assertSame(1, $json['items'][0]['variant']);
        $this->assertSame(2, $json['items'][0]['qty']);
        $this->assertGreaterThan(0, $json['updated_at']);
    }

    /** @test */
    public function session_returns_empty_when_tenant_mismatch(): void
    {
        // Guardar carrito con tenant 'master'
        $this->post($this->validItems());
        $this->assertNotEmpty($_SESSION['shop_cart']['items']);

        // Intentar cargar con tenant distinto (site:42)
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://shop.test/shop/cart/session')
            ->withHeader('Origin', 'http://shop.test')
            ->withAttribute('context', 'site:42');
        $response = (new ShopCartSessionController())->session($request, new Response());
        [$status, $json] = [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];

        $this->assertSame(200, $status);
        $this->assertSame([], $json['items']);
        $this->assertSame(0, $json['updated_at']);
    }

    /** @test */
    public function session_rejects_foreign_origin(): void
    {
        $this->post($this->validItems()); // guardar algo primero
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://shop.test/shop/cart/session')
            ->withHeader('Origin', 'https://evil.com')
            ->withAttribute('context', 'master');
        $response = (new ShopCartSessionController())->session($request, new Response());
        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function session_rejects_when_shop_closed(): void
    {
        Capsule::table('settings')->where('setting_key', 'shop_enabled')->update(['setting_value' => '0']);
        $this->assertSame(503, $this->getSession()[0]);
    }
}
