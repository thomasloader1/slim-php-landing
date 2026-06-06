<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Link;

class LinkTest extends TestCase
{
    /** @test */
    public function it_can_create_a_link()
    {
        $link = new Link([
            'title' => 'Instagram',
            'url' => 'https://instagram.com/test',
            'type' => 'url',
            'icon' => 'fa-brands fa-instagram',
            'color' => '#ffffff',
            'bg_color' => null,
            'sort_order' => 0,
            'active' => 1,
        ]);

        $this->assertEquals('Instagram', $link->title);
        $this->assertEquals('https://instagram.com/test', $link->url);
        $this->assertEquals('fa-brands fa-instagram', $link->icon);
    }

    /** @test */
    public function it_returns_icon_html_with_fa_solid_prefix()
    {
        $link = new Link(['icon' => 'fa-link']);
        $html = $link->getIconHtml();
        $this->assertStringContainsString('fa-solid fa-link', $html);
        $this->assertStringContainsString('<i class="', $html);
    }

    /** @test */
    public function it_does_not_duplicate_fa_prefix()
    {
        $link = new Link(['icon' => 'fa-brands fa-instagram']);
        $html = $link->getIconHtml();
        $this->assertStringContainsString('fa-brands fa-instagram', $html);
        $this->assertStringNotContainsString('fa-solid fa-brands', $html);
    }

    /** @test */
    public function it_has_active_scope()
    {
        $this->assertTrue(method_exists(\App\Models\Link::class, 'scopeActive'));
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $link = new Link();
        $fillable = $link->getFillable();
        $this->assertContains('title', $fillable);
        $this->assertContains('url', $fillable);
        $this->assertContains('icon', $fillable);
        $this->assertContains('color', $fillable);
        $this->assertContains('bg_color', $fillable);
        $this->assertContains('sort_order', $fillable);
        $this->assertContains('active', $fillable);
        $this->assertContains('type', $fillable);
    }
}
