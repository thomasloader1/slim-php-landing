<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'address',
        'embed_code',
        'url',
        'mode',
        'sort_order',
        'active'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * Extrae la URL src del iframe embed_code, o devuelve null.
     */
    public function getEmbedSrc(): ?string
    {
        if ($this->embed_code && preg_match('/src=["\']([^"\']+)["\']/i', $this->embed_code, $m)) {
            return $m[1];
        }
        return null;
    }
}
