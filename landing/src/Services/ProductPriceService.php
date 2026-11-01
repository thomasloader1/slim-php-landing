<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Actualización masiva de precios de productos por porcentaje
 * (+10 = aumento del 10 %, -5 = descuento del 5 %).
 */
class ProductPriceService
{
    public const MIN_PERCENT = -90.0;
    public const MAX_PERCENT = 1000.0;

    /** Redondeos permitidos: 0 = centavos, 10 / 100 = múltiplo más cercano. */
    public const ROUNDINGS = [0, 1, 10, 100];

    /**
     * Precio nuevo: aplica el porcentaje, redondea y lo mantiene entre 0 y el
     * máximo de la columna DECIMAL(8,2).
     */
    public static function applyPercent(float $price, float $percent, int $roundTo = 0): float
    {
        $new = $price * (1 + $percent / 100);
        $new = $roundTo > 0 ? round($new / $roundTo) * $roundTo : round($new, 2);
        return max(0.0, min(ProductCsvService::MAX_PRICE, $new));
    }

    /**
     * @param int[]|null $ids       productos a actualizar (null = todos)
     * @param int|null   $sectionId solo los de esta categoría (combinable con $ids)
     * @return int productos cuyo precio cambió
     */
    public function adjust(?array $ids, ?int $sectionId, float $percent, int $roundTo, bool $includeAltPrices): int
    {
        $query = MenuItem::query();
        if ($ids !== null) {
            $query->whereIn('id', $ids ?: [0]);
        }
        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        $changed = 0;
        Capsule::connection()->transaction(function () use ($query, $percent, $roundTo, $includeAltPrices, &$changed) {
            foreach ($query->lockForUpdate()->get() as $item) {
                $update = ['price' => self::applyPercent((float) $item->price, $percent, $roundTo)];
                if ($includeAltPrices) {
                    foreach (['price_2', 'price_3'] as $col) {
                        if ($item->{$col} !== null) {
                            $update[$col] = self::applyPercent((float) $item->{$col}, $percent, $roundTo);
                        }
                    }
                }
                $dirty = array_filter($update, fn ($v, $col) => abs($v - (float) $item->{$col}) >= 0.005, ARRAY_FILTER_USE_BOTH);
                if ($dirty) {
                    $item->update($dirty);
                    $changed++;
                }
            }
        });
        return $changed;
    }
}
