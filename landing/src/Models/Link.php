<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $table = 'links';
    
    protected $fillable = [
        'title',
        'url',
        'type',
        'icon',
        'color',
        'sort_order',
        'active'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getIconHtml()
    {
        $icon = $this->icon ?: 'fa-link';
        // Add fa-solid as default prefix if no style prefix is present
        if (!preg_match('/fa-(solid|brands|regular|light|thin)/', $icon)) {
            $icon = "fa-solid " . $icon;
        }
        return "<i class=\"{$icon}\"></i>";
    }
}
