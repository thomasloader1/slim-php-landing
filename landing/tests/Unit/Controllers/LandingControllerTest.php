<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Front\LandingController;
use App\Entities\LinkEntity;
use App\Services\DemoDataService;

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
    public function it_accepts_demo_data_service_in_constructor()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $demoService = new DemoDataService(true);
        $controller = new LandingController($container, $demoService);

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

    /** @test */
    public function checks_demo_mode_attribute_from_request()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $viewMock = $this->createMock(\Jenssegers\Blade\Blade::class);
        $viewMock->method('make')->willReturn($this->renderedView());

        $container->method('get')
            ->with('view')
            ->willReturn($viewMock);

        $demoService = $this->createMock(DemoDataService::class);
        $controller = new LandingController($container, $demoService);

        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        // When demo_mode is ON, the controller should check the attribute
        $request->expects($this->atLeastOnce())
            ->method('getAttribute')
            ->with('demo_mode');

        $response->method('getBody')
            ->willReturn($this->createMock(\Psr\Http\Message\StreamInterface::class));

        // We expect the view render to be called
        $viewMock->expects($this->once())
            ->method('make')
            ->with('index', $this->arrayHasKey('settings'));

        $controller->__invoke($request, $response);
    }

    /** @test */
    public function it_passes_demo_mode_to_view_data()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $viewMock = $this->createMock(\Jenssegers\Blade\Blade::class);
        $viewMock->method('make')->willReturn($this->renderedView());

        $container->method('get')
            ->with('view')
            ->willReturn($viewMock);

        $demoService = $this->createMock(DemoDataService::class);
        $controller = new LandingController($container, $demoService);

        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        // Simulate demo_mode being ON
        $request->method('getAttribute')
            ->with('demo_mode')
            ->willReturn(true);

        $response->method('getBody')
            ->willReturn($this->createMock(\Psr\Http\Message\StreamInterface::class));

        // The view data MUST include the 'demoMode' key
        $viewMock->expects($this->once())
            ->method('make')
            ->with('index', $this->callback(function ($data) {
                return array_key_exists('demoMode', $data) && $data['demoMode'] === true;
            }));

        $controller->__invoke($request, $response);
    }

    /** Mock de View cuyo render() devuelve string (el controller lo escribe en el body). */
    private function renderedView(): \Illuminate\View\View
    {
        $view = $this->createMock(\Illuminate\View\View::class);
        $view->method('render')->willReturn('');
        return $view;
    }
}
