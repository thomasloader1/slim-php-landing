<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido (comanda) hecho desde /shop. El stock se reserva al crearlo y se
 * devuelve si se rechaza (ver App\Services\OrderService).
 */
class Order extends Model
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::PENDING  => 'Pendiente',
        self::ACCEPTED => 'Aceptado',
        self::REJECTED => 'Rechazado',
    ];

    protected $table = 'orders';

    protected $fillable = ['code', 'customer_name', 'customer_phone', 'status', 'total', 'ip_hash', 'decided_at', 'decided_by'];

    protected $casts = ['total' => 'decimal:2', 'decided_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id')->orderBy('id');
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTotalFormattedAttribute(): string
    {
        return MenuItem::formatMoney($this->total);
    }

    public static function pendingCount(): int
    {
        try {
            return self::where('status', self::PENDING)->count();
        } catch (\Throwable $e) {
            return 0; // base sin migrar todavía
        }
    }
}
