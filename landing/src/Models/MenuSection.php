<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuSection extends Model
{
    protected $table = 'menu_sections';

    protected $fillable = ['name', 'icon', 'note', 'note_type', 'sort_order', 'active'];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'section_id');
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'section_id')
                    ->where('active', 1)
                    ->orderBy('sort_order');
    }
}
