<?php

namespace Tests\Unit\Services;

use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Services\MigrationService;
use App\Services\ProductCsvService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

class ProductCsvServiceTest extends TestCase
{
    private ProductCsvService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ProductCsvService();
    }

    // ── Precios ──────────────────────────────────────────────────

    /** @test */
    public function parses_argentine_and_international_price_formats(): void
    {
        $cases = [
            '1500' => 1500.0, '1500,50' => 1500.5, '1.500' => 1500.0, '10.000' => 10000.0,
            '1.500,50' => 1500.5, '$ 1.500' => 1500.0, '1500.50' => 1500.5, '1,500.50' => 1500.5,
            '1.000.000' => 1000000.0, '0' => 0.0, '12.5' => 12.5,
        ];
        foreach ($cases as $in => $expected) {
            $this->assertSame($expected, ProductCsvService::parsePrice((string) $in), "precio \"{$in}\"");
        }
    }

    /** @test */
    public function rejects_invalid_prices(): void
    {
        foreach (['', 'abc', '-100', '12a', '1,2,3.4.5x'] as $in) {
            $this->assertNull(ProductCsvService::parsePrice($in), "precio \"{$in}\"");
        }
    }

    // ── Parseo ───────────────────────────────────────────────────

    /** @test */
    public function template_round_trips_through_parser_without_errors(): void
    {
        $parsed = $this->svc->parse($this->svc->template());

        $this->assertSame([], $parsed['errors']);
        $this->assertSame(ProductCsvService::COLUMNS, $parsed['columns']);
        $this->assertCount(2, $parsed['rows']);
        foreach ($parsed['rows'] as $row) {
            $this->assertSame([], $row['errors']);
        }
        $this->assertSame(2500.5, $parsed['rows'][1]['data']['precio']);
        $this->assertNull($parsed['rows'][1]['data']['stock']);
        $this->assertSame(24, $parsed['rows'][0]['data']['stock']);
    }

    /** @test */
    public function accepts_comma_delimiter_windows_1252_and_header_aliases(): void
    {
        $csv = mb_convert_encoding("SKU,Categoría,Producto,Precio,Imagen\r\nX1,Panadería,Pan de campo,\"1.200,00\",\r\n", 'Windows-1252', 'UTF-8');

        $parsed = $this->svc->parse($csv);

        $this->assertSame([], $parsed['errors']);
        $this->assertSame(['codigo', 'categoria', 'nombre', 'precio', 'foto_url'], $parsed['columns']);
        $row = $parsed['rows'][0];
        $this->assertSame([], $row['errors']);
        $this->assertSame('Panadería', $row['data']['categoria']);
        $this->assertSame(1200.0, $row['data']['precio']);
        $this->assertNull($row['data']['foto_url']);
    }

    /** @test */
    public function reports_missing_required_columns(): void
    {
        $parsed = $this->svc->parse("codigo;nombre\nA;B\n");

        $this->assertCount(1, $parsed['errors']);
        $this->assertStringContainsString('precio', $parsed['errors'][0]);
        $this->assertSame([], $parsed['rows']);
    }

    /** @test */
    public function flags_row_errors_with_spreadsheet_line_numbers(): void
    {
        $csv = "codigo;nombre;precio;foto_url;stock;activo\n"
            . "A;Uno;100;;5;si\n"
            . "\n"                                        // fila vacía: se ignora
            . ";;abc;ftp://x/y.jpg;-1;quizas\n"           // fila 4: todo mal
            . "a;Repetido;100;;;\n";                      // fila 5: código repetido (sin distinguir mayúsculas)

        $parsed = $this->svc->parse($csv);

        $this->assertCount(3, $parsed['rows']);
        $this->assertSame([], $parsed['rows'][0]['errors']);

        $bad = $parsed['rows'][1];
        $this->assertSame(4, $bad['line']);
        $joined = implode(' | ', $bad['errors']);
        foreach (['nombre', 'Precio inválido', 'http', 'Stock inválido', 'activo'] as $needle) {
            $this->assertStringContainsString($needle, $joined);
        }

        $dup = $parsed['rows'][2];
        $this->assertSame(5, $dup['line']);
        $this->assertStringContainsString('fila 2', implode(' ', $dup['errors']));
    }

    /** @test */
    public function export_escapes_formulas_and_parser_restores_them(): void
    {
        $item = new MenuItem(['sku' => '=HYPERLINK("x")', 'title' => '-Promo', 'price' => 10, 'stock' => null, 'active' => 1]);
        $item->setRelation('section', null);

        $csv = $this->svc->export([$item]);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'-Promo", $csv);

        $row = $this->svc->parse($csv)['rows'][0];
        $this->assertSame('=HYPERLINK("x")', $row['data']['codigo']);
        $this->assertSame('-Promo', $row['data']['nombre']);
    }

    /** @test */
    public function category_key_ignores_case_accents_and_extra_spaces(): void
    {
        $this->assertSame(
            ProductCsvService::categoryKey('Panadería y Café'),
            ProductCsvService::categoryKey('  PANADERIA   y cafe ')
        );
        $this->assertNotSame(ProductCsvService::categoryKey('Panes'), ProductCsvService::categoryKey('Pan'));
    }

    // ── Importación contra la DB de test ─────────────────────────

    /** @test */
    public function import_links_products_to_existing_category_ignoring_case_and_accents(): void
    {
        (new MigrationService())->migrate(Capsule::connection(), 'site');
        $conn = Capsule::connection();
        $conn->beginTransaction();
        try {
            $existing = MenuSection::create(['name' => 'Panadería Test', 'sort_order' => 1, 'active' => 1]);
            $before = MenuSection::count();

            $csv = "codigo;categoria;nombre;precio\n"
                . "U-1;panaderia test;Pan 1;100\n"
                . "U-2;PANADERÍA   TEST ;Pan 2;100\n"
                . "U-3;Nueva Cat Test;Otro;100\n"
                . "U-4;nueva cat test;Otro 2;100\n";
            $parsed = $this->svc->plan($this->svc->parse($csv));

            $this->assertSame(['Nueva Cat Test'], $parsed['new_categories']);

            $stats = $this->svc->apply($parsed['columns'], array_column($parsed['rows'], 'data'));

            $this->assertSame(1, $stats['categories']);
            $this->assertSame($before + 1, MenuSection::count());
            $this->assertSame([$existing->id], MenuItem::whereIn('sku', ['U-1', 'U-2'])->pluck('section_id')->unique()->values()->all());
            $newCat = MenuSection::where('name', 'Nueva Cat Test')->first();
            $this->assertSame(2, MenuItem::where('section_id', $newCat->id)->count());
        } finally {
            $conn->rollBack();
        }
    }

    /** @test */
    public function migration_merges_duplicate_categories_and_enforces_unique_name(): void
    {
        $conn = Capsule::connection();
        $migrator = new MigrationService();
        $migrator->migrate($conn, 'site');

        // Simula una DB vieja: sin índice, con categorías repetidas y sin el upgrade 0007 registrado
        $upgrade = 'upgrades/site/0007_unique_categories.sql';
        $conn->statement('ALTER TABLE menu_sections DROP INDEX uq_menu_sections_name');
        $conn->table('schema_migrations')->where('filename', $upgrade)->delete();
        $ids = [];
        try {
            foreach (['ZZ Dup Test', 'zz dup test ', 'ZZ DÚP TEST'] as $i => $name) {
                $ids[] = $conn->table('menu_sections')->insertGetId(['name' => $name, 'sort_order' => 90 + $i, 'active' => 1]);
            }
            $itemId = $conn->table('menu_items')->insertGetId(['title' => 'ZZ item', 'price' => 1, 'section_id' => $ids[2]]);

            $migrator->migrate($conn, 'site');

            $this->assertSame([$ids[0]], $conn->table('menu_sections')->whereIn('id', $ids)->pluck('id')->all());
            $this->assertSame($ids[0], (int) $conn->table('menu_items')->where('id', $itemId)->value('section_id'));

            $this->expectException(\Illuminate\Database\QueryException::class);
            $conn->table('menu_sections')->insert(['name' => 'zz DUP test', 'sort_order' => 99, 'active' => 1]);
        } finally {
            $conn->table('menu_items')->where('title', 'ZZ item')->delete();
            $conn->table('menu_sections')->whereIn('id', $ids)->delete();
            $conn->table('schema_migrations')->where('filename', $upgrade)->delete();
            $migrator->migrate($conn, 'site'); // asegura el índice si el test falló a mitad
        }
    }

    /** @test */
    public function apply_creates_updates_and_keeps_photo_when_cell_is_empty(): void
    {
        (new MigrationService())->migrate(Capsule::connection(), 'site'); // DDL: antes de la transacción
        $conn = Capsule::connection();
        $conn->beginTransaction();
        try {
            $existing = MenuItem::create([
                'sku' => 'T-UPD', 'title' => 'Viejo', 'price' => 1, 'stock' => 3,
                'image_url' => 'https://img.example/viejo.jpg', 'description' => '', 'active' => 1,
            ]);

            $csv = "codigo;categoria;nombre;precio;foto_url;stock;activo\n"
                . "t-upd;Cat Test Import;Nuevo nombre;2.500,50;;0;no\n"
                . "T-NEW;cat test import;Producto nuevo;100;https://img.example/n.jpg;;\n"
                . ";;Sin codigo;50;;;\n";
            $parsed = $this->svc->plan($this->svc->parse($csv));

            $this->assertSame(['update', 'create', 'create'], array_column($parsed['rows'], 'action'));

            $stats = $this->svc->apply($parsed['columns'], array_column($parsed['rows'], 'data'));
            $this->assertSame(['created' => 2, 'updated' => 1, 'categories' => 1], $stats);

            $existing->refresh();
            $this->assertSame('Nuevo nombre', $existing->title);
            $this->assertSame('2500.50', (string) $existing->price);
            $this->assertSame(0, $existing->stock);
            $this->assertTrue($existing->is_out_of_stock);
            $this->assertSame(0, (int) $existing->active);
            $this->assertSame('https://img.example/viejo.jpg', $existing->image_url, 'foto vacía no debe borrar la actual');

            $new = MenuItem::where('sku', 'T-NEW')->first();
            $this->assertNotNull($new);
            $this->assertNull($new->stock);
            $this->assertFalse($new->is_out_of_stock);
            $this->assertSame($existing->section_id, $new->section_id, 'categoría sin distinguir mayúsculas');
            $this->assertSame('Cat Test Import', MenuSection::find($new->section_id)->name);
        } finally {
            $conn->rollBack();
        }
    }
}
