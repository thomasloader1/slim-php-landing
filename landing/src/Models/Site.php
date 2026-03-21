<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = [
        'name', 'domain', 'db_host', 'db_port', 'db_name',
        'db_user', 'db_pass_encrypted', 'plan_name',
        'plan_start', 'plan_end', 'domain_expiry',
        'status', 'redirect_url', 'auto_suspend', 'notes', 'last_status_sync'
    ];

    protected $casts = [
        'plan_start'    => 'date',
        'plan_end'      => 'date',
        'domain_expiry' => 'date',
    ];

    public function isExpired(): bool
    {
        return $this->plan_end->isPast();
    }

    public function daysUntilPlanExpiry(): int
    {
        return (int) now()->diffInDays($this->plan_end, false);
    }

    public function daysUntilDomainExpiry(): ?int
    {
        if (!$this->domain_expiry) {
            return null;
        }
        return (int) now()->diffInDays($this->domain_expiry, false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('plan_end', '<', now()->toDateString());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->where('plan_end', '<=', now()->addDays($days)->toDateString())
            ->where('plan_end', '>=', now()->toDateString());
    }
}
