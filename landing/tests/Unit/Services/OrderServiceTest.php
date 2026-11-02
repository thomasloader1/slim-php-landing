<?php

namespace Tests\Unit\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Services\MigrationService;
use App\Services\OrderService;
use App\Support\RateLimiter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    private static bool $migrated = false;
    private OrderService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            (new MigrationService())->migrate(Capsule::connection(), 'site');
            self::$migrated = true;
        }
        Capsule::connection()->beginTransaction();
        $this->svc = new OrderService();
    }

    protected function tearDown(): void
    {
        Capsule::connection()->rollBack();
        parent::tearDown();
    }

    private function item(array $attrs): MenuItem
    {
        return MenuItem::create($attrs + ['title' => 'Prod', 'price' => 100, 'description' => '', 'active' => 1]);
    }

    /** @test */
    public function creates_order_with_db_prices_variants_and_reserves_stock(): void
    {
        $pizza = $this->item(['title' => 'Pizza', 'price' => 8000, 'price_label_1' => 'Chica', 'price_2' => 10000, 'price_label_2' => 'Grande', 'stock' => 5]);
        $agua = $this->item(['title' => 'Agua', 'price' => 1200]); // sin control de stock

        $r = $this->svc->create([
            ['id' => $pizza->id, 'variant' => 2, 'qty' => 2, 'price' => 1], // el precio del cliente se ignora
            ['id' => $pizza->id, 'variant' => 1, 'qty' => 1],
            ['id' => $agua->id, 'qty' => 3],
        ], 'Juan Pérez', '+54 9 11 5555-1234', '10.0.0.1');

        $this->assertSame([], $r['errors']);
        $order = $r['order'];
        $this->assertMatchesRegularExpression('/^P-\d{6}$/', $order->code);
        $this->assertSame(Order::PENDING, $order->status);
        $this->assertSame('31600.00', (string) $order->total); // 2*10000 + 8000 + 3*1200
        $this->assertSame(2, $pizza->fresh()->stock, 'reserva 3 unidades de pizza (todas las variantes)');
        $this->assertNull($agua->fresh()->stock);
        $this->assertSame(['Pizza (Grande)', 'Pizza (Chica)', 'Agua'], $order->items->pluck('display_name')->all());
        $this->assertSame([true, true, false], $order->items->pluck('stock_reserved')->all());
    }

    /** @test */
    public function insufficient_stock_fails_atomically(): void
    {
        $a = $this->item(['title' => 'A', 'stock' => 10]);
        $b = $this->item(['title' => 'B', 'stock' => 1]);
        $before = Order::count();

        $r = $this->svc->create([['id' => $a->id, 'qty' => 2], ['id' => $b->id, 'qty' => 2]], 'Ana', '1155551234');

        $this->assertNull($r['order']);
        $this->assertSame(['B: quedan 1.'], array_values($r['errors']));
        $this->assertArrayHasKey("{$b->id}:1", $r['errors']);
        $this->assertSame(10, $a->fresh()->stock, 'no se descuenta nada si una línea falla');
        $this->assertSame($before, Order::count());
    }

    /** @test */
    public function rejects_invalid_customer_cart_variants_and_inactive_products(): void
    {
        $inactive = $this->item(['title' => 'Viejo', 'active' => 0]);
        $single = $this->item(['title' => 'Único']);

        $r = $this->svc->create([], 'J', '123');
        $this->assertEqualsCanonicalizing(['name', 'phone', 'cart'], array_keys($r['errors']));

        $r = $this->svc->create([['id' => $inactive->id, 'qty' => 1], ['id' => $single->id, 'variant' => 2, 'qty' => 1]], 'Ana', '1155551234');
        $this->assertNull($r['order']);
        $this->assertStringContainsString('no está disponible', $r['errors']["{$inactive->id}:1"]);
        $this->assertStringContainsString('esa opción', $r['errors']["{$single->id}:2"]);
    }

    /** @test */
    public function enforces_max_units_per_order(): void
    {
        Capsule::table('settings')->updateOrInsert(['setting_key' => 'shop_max_items'], ['setting_value' => '3']);
        $p = $this->item([]);
        $r = $this->svc->create([['id' => $p->id, 'qty' => 4]], 'Ana', '1155551234');
        $this->assertStringContainsString('hasta 3 unidades', $r['errors']['cart']);
    }

    /** @test */
    public function reject_returns_stock_once_and_accept_keeps_it(): void
    {
        $p = $this->item(['stock' => 5]);
        $o1 = $this->svc->create([['id' => $p->id, 'qty' => 2]], 'Ana', '1155551234')['order'];
        $o2 = $this->svc->create([['id' => $p->id, 'qty' => 1]], 'Beto', '1155551235')['order'];
        $this->assertSame(2, $p->fresh()->stock);

        $this->assertTrue($this->svc->decide($o1, Order::REJECTED, 'tester'));
        $this->assertSame(4, $p->fresh()->stock);
        $this->assertFalse($this->svc->decide($o1, Order::REJECTED, 'tester'), 'no se rechaza dos veces');
        $this->assertSame(4, $p->fresh()->stock, 'no devuelve dos veces');

        $this->assertTrue($this->svc->decide($o2, Order::ACCEPTED, 'tester'));
        $this->assertSame(4, $p->fresh()->stock);
        $this->assertSame('tester', $o2->fresh()->decided_by);
        $this->assertFalse($this->svc->decide($o2, Order::REJECTED, 'tester'), 'un aceptado ya no se puede rechazar');
    }

    /** @test */
    public function rejecting_after_product_deleted_does_not_fail(): void
    {
        $p = $this->item(['stock' => 3]);
        $order = $this->svc->create([['id' => $p->id, 'qty' => 1]], 'Ana', '1155551234')['order'];
        $p->delete();

        $this->assertTrue($this->svc->decide($order, Order::REJECTED, 'tester'));
        $this->assertNull($order->fresh()->items->first()->menu_item_id);
    }

    /** @test */
    public function builds_whatsapp_messages(): void
    {
        $p = $this->item(['title' => 'Pizza', 'price' => 10000, 'price_label_1' => 'Grande']);
        $order = $this->svc->create([['id' => $p->id, 'qty' => 2]], 'Juan', '1155551234')['order'];

        $msg = OrderService::whatsappMessage($order);
        $this->assertStringContainsString("Pedido {$order->code}", $msg);
        $this->assertStringContainsString('• 2 x Pizza (Grande) — $20.000', $msg);
        $this->assertStringContainsString('Total: $20.000', $msg);
        $this->assertStringContainsString('Nombre: Juan', $msg);

        $this->svc->decide($order, Order::ACCEPTED, 't');
        $this->assertStringContainsString('fue aceptado', OrderService::customerNotice($order->fresh()));
    }

    /** @test */
    public function customer_notice_uses_editable_template_with_placeholders(): void
    {
        $p = $this->item(['title' => 'Pizza', 'price' => 10000]);
        $order = $this->svc->create([['id' => $p->id, 'qty' => 2]], 'Juan', '1155551234')['order'];
        $this->svc->decide($order, Order::ACCEPTED, 't');
        $order = $order->fresh();

        $msg = OrderService::customerNotice($order, [
            'shop_notice_accepted' => "Listo {nombre}: {pedido} por {total}.\n{detalle}\nAlias: {alias} {desconocida}",
            'menu_payment_alias'   => 'mi.alias',
        ]);
        $this->assertSame("Listo Juan: {$order->code} por \$20.000.\n• 2 x Pizza — \$20.000\nAlias: mi.alias {desconocida}", $msg);

        // Plantilla vacía → texto de fábrica
        $this->assertStringContainsString('fue aceptado', OrderService::customerNotice($order, ['shop_notice_accepted' => '  ']));
    }

    /** @test */
    public function rate_limiter_blocks_after_max_hits(): void
    {
        $key = 'test:' . uniqid();
        $this->assertTrue(RateLimiter::hit($key, 2, 60));
        $this->assertTrue(RateLimiter::hit($key, 2, 60));
        $this->assertFalse(RateLimiter::hit($key, 2, 60));
        $this->assertTrue(RateLimiter::hit('test:otra-' . uniqid(), 2, 60), 'otra clave no se ve afectada');
    }
}
