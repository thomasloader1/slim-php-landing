<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $table = 'menu_items';

    protected $fillable = [
        'section_id', 'title', 'price',
        'description', 'image_url', 'sort_order', 'active'
    ];

    protected $casts = ['price' => 'decimal:2'];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MenuSection::class, 'section_id');
    }

    // Precio formateado para la vista pública, ej: "$1.250,00"
    public function getPriceFormattedAttribute(): string
    {
        return '$' . number_format((float) $this->price, 2, ',', '.');
    }
}
