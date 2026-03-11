<?php

namespace App\Entities;

class LinkEntity
{
    public string $title;
    public string $url;
    public string $type;
    public string $icon;
    public string $color;

    public function __construct(array $data)
    {
        $this->title = $data['title'] ?? '';
        $this->url   = $data['url']   ?? '';
        $this->type  = $data['type']  ?? 'url';
        $this->icon  = $data['icon']  ?? 'fa-link';
        $this->color = $data['color'] ?? '#fec771';
    }

    public function getIconHtml(): string
    {
        return "<i class='fas {$this->icon}'></i>";
    }
    
    public function getTitle(): string
    {
        return htmlspecialchars($this->title);
    }
}
