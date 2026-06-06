<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Front\LandingController;
use App\Entities\LinkEntity;

class LandingControllerTest extends TestCase
{
    /** @test */
    public function it_accepts_a_container()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $controller = new LandingController($container);
        $this->assertInstanceOf(LandingController::class, $controller);
    }

    /** @test */
    public function it_uses_link_entity_to_wrap_link_data()
    {
        $data = [
            'id' => 1,
            'title' => 'Instagram',
            'url' => 'https://instagram.com/test',
            'type' => 'url',
            'icon' => 'fa-brands fa-instagram',
            'color' => '#ffffff',
            'bg_color' => null,
            'sort_order' => 0,
            'active' => 1,
        ];

        $entity = new LinkEntity($data);
        $this->assertEquals('Instagram', $entity->title);
        $this->assertEquals('https://instagram.com/test', $entity->url);
        $this->assertEquals('fa-brands fa-instagram', $entity->icon);
        $this->assertEquals('#ffffff', $entity->color);
        $this->assertNull($entity->bgColor);
    }

    /** @test */
    public function link_entity_uses_defaults_for_missing_data()
    {
        $entity = new LinkEntity([]);

        $this->assertEquals('', $entity->title);
        $this->assertEquals('', $entity->url);
        $this->assertEquals('url', $entity->type);
        $this->assertEquals('fa-link', $entity->icon);
        $this->assertEquals('#fec771', $entity->color);
        $this->assertNull($entity->bgColor);
    }

    /** @test */
    public function link_entity_get_title_escapes_html()
    {
        $entity = new LinkEntity(['title' => '<script>alert("xss")</script>']);
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $entity->getTitle());
    }

    /** @test */
    public function link_entity_get_icon_html_adds_fa_solid_prefix_when_missing()
    {
        $entity = new LinkEntity(['icon' => 'fa-camera']);
        $html = $entity->getIconHtml();
        $this->assertStringContainsString('fa-solid', $html);
        $this->assertStringContainsString('fa-camera', $html);
    }

    /** @test */
    public function link_entity_get_icon_html_preserves_existing_style_prefix()
    {
        $entity = new LinkEntity(['icon' => 'fa-brands fa-github']);
        $html = $entity->getIconHtml();
        $this->assertStringNotContainsString('fa-solid', $html);
        $this->assertStringContainsString('fa-brands fa-github', $html);
    }
}
