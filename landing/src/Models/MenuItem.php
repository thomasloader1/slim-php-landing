<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $table = 'menu_items';

    protected $fillable = [
        'section_id', 'title', 'price',
        'price_label_1', 'price_2', 'price_label_2',
        'price_3', 'price_label_3', 'unit',
        'description', 'image_url', 'flavors', 'gift_note',
        'badge_type', 'sort_order', 'active',
    ];

    protected $casts = [
        'price'   => 'decimal:2',
        'price_2' => 'decimal:2',
        'price_3' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MenuSection::class, 'section_id');
    }

    /** Precio principal formateado, ej: "$10.000" */
    public function getPriceFormattedAttribute(): string
    {
        return '$' . number_format((float) $this->price, 0, ',', '.');
    }

    /** Precio 2 formateado */
    public function getPrice2FormattedAttribute(): ?string
    {
        return $this->price_2 !== null
            ? '$' . number_format((float) $this->price_2, 0, ',', '.')
            : null;
    }

    /** Precio 3 formateado */
    public function getPrice3FormattedAttribute(): ?string
    {
        return $this->price_3 !== null
            ? '$' . number_format((float) $this->price_3, 0, ',', '.')
            : null;
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
