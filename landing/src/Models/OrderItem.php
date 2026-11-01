<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Línea de un pedido: copia del producto al momento de pedir. */
class OrderItem extends Model
{
    protected $table = 'order_items';

    public $timestamps = false;

    protected $fillable = ['order_id', 'menu_item_id', 'title', 'variant_label', 'unit_price', 'quantity', 'subtotal', 'stock_reserved'];

    protected $casts = ['unit_price' => 'decimal:2', 'subtotal' => 'decimal:2', 'quantity' => 'integer', 'stock_reserved' => 'boolean'];

    public function getDisplayNameAttribute(): string
    {
        return $this->title . ($this->variant_label ? " ({$this->variant_label})" : '');
    }
}
