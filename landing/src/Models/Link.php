<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasActiveScope;

    protected $table = 'links';
    
    protected $fillable = [
        'title',
        'url',
        'type',
        'icon',
        'color',
        'bg_color',
        'sort_order',
        'active'
    ];

    public function getIconHtml(): string
    {
        $icon = $this->icon ?: 'fa-link';
        // Add fa-solid as default prefix if no style prefix is present
        if (!preg_match('/fa-(solid|brands|regular|light|thin)/', $icon)) {
            $icon = "fa-solid " . $icon;
        }
        return '<i class="' . htmlspecialchars($icon, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"></i>';
    }
}
