<?php

namespace App\Entities;

class LinkEntity
{
    public string $title;
    public string $url;
    public string $type;
    public string $icon;
    public string $color;
    public ?string $bgColor;

    public function __construct(array $data)
    {
        $this->title   = $data['title'] ?? '';
        $this->url     = $data['url']   ?? '';
        $this->type    = $data['type']  ?? 'url';
        $this->icon    = $data['icon']  ?? 'fa-link';
        $this->color   = $data['color'] ?? '#fec771';
        $this->bgColor = $data['bg_color'] ?? null;
    }

    public function getIconHtml(): string
    {
        $icon = $this->icon ?: 'fa-link';
        if (!preg_match('/fa-(solid|brands|regular|light|thin)/', $icon)) {
            $icon = "fa-solid " . $icon;
        }
        return "<i class=\"{$icon}\"></i>";
    }
    
    public function getTitle(): string
    {
        return htmlspecialchars($this->title);
    }
}
