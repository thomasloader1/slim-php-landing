<?php

namespace Tests\Unit\Services;

use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Services\MigrationService;
use App\Services\ProductPriceService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

class ProductPriceServiceTest extends TestCase
{
    /** @test */
    public function applies_percent_with_rounding_and_limits(): void
    {
        $this->assertSame(1100.0, ProductPriceService::applyPercent(1000, 10));
        $this->assertSame(950.0, ProductPriceService::applyPercent(1000, -5));
        $this->assertSame(1234.57, ProductPriceService::applyPercent(1122.34, 10));      // centavos
        $this->assertSame(1230.0, ProductPriceService::applyPercent(1122.34, 10, 10));   // múltiplo de 10
        $this->assertSame(1200.0, ProductPriceService::applyPercent(1122.34, 10, 100));  // múltiplo de 100
        $this->assertSame(0.0, ProductPriceService::applyPercent(0, 50));
        $this->assertSame(999999.99, ProductPriceService::applyPercent(900000, 50), 'no supera DECIMAL(8,2)');
    }

    /** @test */
    public function adjusts_all_a_category_or_selected_items(): void
    {
        (new MigrationService())->migrate(Capsule::connection(), 'site');
        $conn = Capsule::connection();
        $conn->beginTransaction();
        try {
            $cat = MenuSection::create(['name' => 'Precio Test Cat', 'sort_order' => 1, 'active' => 1]);
            $a = MenuItem::create(['title' => 'PT A', 'price' => 1000, 'price_2' => 2000, 'section_id' => $cat->id, 'description' => '']);
            $b = MenuItem::create(['title' => 'PT B', 'price' => 500, 'description' => '']);
            $c = MenuItem::create(['title' => 'PT C', 'price' => 300, 'description' => '']);
            $svc = new ProductPriceService();

            // Parcial: solo tildados
            $this->assertSame(1, $svc->adjust([$b->id], null, 20, 0, true));
            $this->assertSame('600.00', (string) $b->fresh()->price);
            $this->assertSame('1000.00', (string) $a->fresh()->price);

            // Por categoría, sin tocar precios alternativos
            $this->assertSame(1, $svc->adjust(null, $cat->id, -10, 0, false));
            $this->assertSame('900.00', (string) $a->fresh()->price);
            $this->assertSame('2000.00', (string) $a->fresh()->price_2);

            // Total (limitado a estos 3 para no depender del resto de la DB) con alternativos y redondeo
            $this->assertSame(3, $svc->adjust([$a->id, $b->id, $c->id], null, 5, 10, true));
            $this->assertSame('950.00', (string) $a->fresh()->price);   // 945 → 950
            $this->assertSame('2100.00', (string) $a->fresh()->price_2);
            $this->assertNull($c->fresh()->price_2, 'un precio alternativo vacío sigue vacío');

            // Seleccionados vacío no toca nada
            $this->assertSame(0, $svc->adjust([], null, 50, 0, true));
        } finally {
            $conn->rollBack();
        }
    }
}
