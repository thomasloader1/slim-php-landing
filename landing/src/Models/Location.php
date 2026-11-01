<?php

namespace App\Models;

use App\Support\MapEmbed;
use App\Support\WhatsApp;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasActiveScope;

    protected $table = 'locations';

    protected $fillable = [
        'name', 'address', 'whatsapp', 'whatsapp_message',
        'embed_code', 'url', 'mode',
        'sort_order', 'active',
    ];

    /**
     * URL para el <iframe> del mapa.
     * 1) src del código "Insertar mapa" pegado (solo hosts de Google permitidos por CSP)
     * 2) si no hay código, se deriva del link de Google Maps o de la dirección
     */
    public function getEmbedSrc(): ?string
    {
        return MapEmbed::srcFromIframe($this->embed_code)
            ?? MapEmbed::fromUrlOrAddress($this->url, $this->address);
    }

    /** URL para abrir la ubicación en Google Maps (botón / "Cómo llegar"). */
    public function getMapsLinkAttribute(): ?string
    {
        if (!empty($this->url)) {
            return $this->url;
        }
        if (!empty($this->address)) {
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($this->address);
        }
        return null;
    }

    /**
     * URL de WhatsApp (wa.me) con mensaje pre-cargado.
     * En escritorio la landing la reemplaza por WhatsApp Web (whatsapp_web_url).
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        if (empty($this->whatsapp)) {
            return null;
        }
        return WhatsApp::appUrl($this->whatsapp, $this->whatsapp_message);
    }

    public function getWhatsappWebUrlAttribute(): ?string
    {
        if (empty($this->whatsapp)) {
            return null;
        }
        return WhatsApp::webUrl($this->whatsapp, $this->whatsapp_message);
    }
}
