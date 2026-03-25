<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'name', 'address', 'whatsapp', 'whatsapp_message',
        'embed_code', 'url', 'mode',
        'sort_order', 'active',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /** Extrae la URL src del iframe embed_code, o devuelve null. */
    public function getEmbedSrc(): ?string
    {
        if ($this->embed_code && preg_match('/src=["\']([^"\']+)["\']/i', $this->embed_code, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * URL de WhatsApp lista para usar en href.
     * Acepta número con o sin prefijo +/00, limpia espacios y guiones.
     */
    /**
     * URL de WhatsApp lista para usar en href.
     * Incluye mensaje pre-cargado si está configurado (WhatsApp API).
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        if (empty($this->whatsapp)) {
            return null;
        }
        $number = preg_replace('/[^0-9]/', '', $this->whatsapp);
        $url    = 'https://wa.me/' . $number;
        if (!empty($this->whatsapp_message)) {
            $url .= '?text=' . rawurlencode($this->whatsapp_message);
        }
        return $url;
    }
}
