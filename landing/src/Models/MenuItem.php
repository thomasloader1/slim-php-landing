<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    use HasActiveScope;

    protected $table = 'menu_items';

    protected $fillable = [
        'section_id', 'sku', 'title', 'price', 'stock',
        'price_label_1', 'price_2', 'price_label_2',
        'price_3', 'price_label_3', 'unit',
        'description', 'image_url', 'flavors', 'gift_note',
        'badge_type', 'sort_order', 'active',
    ];

    protected $casts = [
        'price'   => 'decimal:2',
        'price_2' => 'decimal:2',
        'price_3' => 'decimal:2',
        'stock'   => 'integer',
    ];

    /** True si se controla stock y no quedan unidades (stock NULL = sin control) */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock !== null && $this->stock <= 0;
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MenuSection::class, 'section_id');
    }

    /** Precio principal formateado, ej: "$10.000" */
    public function getPriceFormattedAttribute(): string
    {
        return self::formatMoney($this->price);
    }

    /** Precio 2 formateado */
    public function getPrice2FormattedAttribute(): ?string
    {
        return $this->price_2 !== null
            ? self::formatMoney($this->price_2)
            : null;
    }

    /** Precio 3 formateado */
    public function getPrice3FormattedAttribute(): ?string
    {
        return $this->price_3 !== null
            ? self::formatMoney($this->price_3)
            : null;
    }

    /** "$10.000" o "$2.500,50": decimales sólo si el precio los tiene */
    public static function formatMoney($value): string
    {
        $v = (float) $value;
        $decimals = abs($v - round($v)) >= 0.005 ? 2 : 0;
        return '$' . number_format($v, $decimals, ',', '.');
    }

    /**
     * Ancla estable del producto en /shop (deep link para JSON-LD, llms.txt y buscadores):
     * "prod-{sku}" normalizado, o "prod-{id}" si no tiene SKU.
     */
    public function getAnchorAttribute(): string
    {
        $sku = strtolower(trim((string) $this->sku));
        $sku = trim(preg_replace('/[^a-z0-9]+/', '-', $sku), '-');
        if ($sku !== '') {
            return 'prod-' . $sku;
        }
        return 'prod-' . ($this->id ?? substr(md5((string) $this->title), 0, 8));
    }

    /** Precios del ítem (principal + alternativos) como floats, sin nulos. */
    public function getAllPricesAttribute(): array
    {
        return array_values(array_map('floatval', array_filter(
            [$this->price, $this->price_2, $this->price_3],
            fn ($p) => $p !== null && $p !== ''
        )));
    }

    /** Devuelve los sabores como array, descartando vacíos */
    public function getFlavorsArrayAttribute(): array
    {
        if (empty($this->flavors)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $this->flavors)));
    }

    /** True si el ítem tiene más de un precio configurado */
    public function getIsMultiPriceAttribute(): bool
    {
        return $this->price_2 !== null;
    }
}
