<?php

namespace App\Services;

class DemoDataService
{
    private bool $demoMode;
    private string $jsonPath;
    private ?array $data = null;

    public function __construct(bool $demoMode = false, ?string $jsonPath = null)
    {
        $this->demoMode = $demoMode;
        $this->jsonPath = $jsonPath ?? __DIR__ . '/../../storage/demo-data.json';
    }

    public function getLinks(): array
    {
        return $this->load()['links'] ?? [];
    }

    public function getLocations(): array
    {
        return $this->load()['locations'] ?? [];
    }

    public function getMenuSections(): array
    {
        return $this->load()['menu_sections'] ?? [];
    }

    public function getMenuItems(): array
    {
        return $this->load()['menu_items'] ?? [];
    }

    public function getFaqItems(): array
    {
        return $this->load()['faq'] ?? [];
    }

    public function getSettings(): array
    {
        return $this->load()['settings'] ?? [];
    }

    private function load(): array
    {
        if (!$this->demoMode) {
            return [];
        }

        if ($this->data !== null) {
            return $this->data;
        }

        if (!file_exists($this->jsonPath)) {
            return [];
        }

        $content = file_get_contents($this->jsonPath);
        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        $this->data = $decoded;
        return $this->data;
    }
}
