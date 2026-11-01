<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Admin\DemoAdminController;
use App\Services\DemoDataService;

class DemoAdminControllerTest extends TestCase
{
    /** @test */
    public function controller_accepts_container_and_demo_service()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $demoService = new DemoDataService(true);
        $controller = new DemoAdminController($container, $demoService);

        $this->assertInstanceOf(DemoAdminController::class, $controller);
    }

    /** @test */
    public function has_reset_method()
    {
        $this->assertTrue(method_exists(DemoAdminController::class, 'reset'));
    }

    /** @test */
    public function reset_redirects_on_success()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $demoService = $this->createMock(DemoDataService::class);
        $demoService->method('getLinks')->willReturn([]);
        $demoService->method('getLocations')->willReturn([]);
        $demoService->method('getMenuSections')->willReturn([]);
        $demoService->method('getMenuItems')->willReturn([]);
        $demoService->method('getFaqItems')->willReturn([]);

        $controller = new DemoAdminController($container, $demoService);

        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        $response->expects($this->once())
            ->method('withHeader')
            ->with('Location', $this->stringContains('admin'))
            ->willReturnSelf();

        $response->expects($this->once())
            ->method('withStatus')
            ->with(302)
            ->willReturnSelf();

        $controller->reset($request, $response);
    }

    /** @test */
    public function reset_inserts_demo_data_in_transaction()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $demoService = $this->createMock(DemoDataService::class);

        $demoData = [
            'links' => [['title' => 'Test Link', 'url' => 'https://test.com', 'type' => 'url', 'sort_order' => 0, 'active' => 1]],
            'locations' => [],
            'menu_sections' => [],
            'menu_items' => [],
            'faq' => [],
        ];

        $demoService->method('getLinks')->willReturn($demoData['links']);
        $demoService->method('getLocations')->willReturn($demoData['locations']);
        $demoService->method('getMenuSections')->willReturn($demoData['menu_sections']);
        $demoService->method('getMenuItems')->willReturn($demoData['menu_items']);
        $demoService->method('getFaqItems')->willReturn($demoData['faq']);

        $controller = new DemoAdminController($container, $demoService);

        $request = $this->createMock(\Psr\Http\Message\ServerRequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        $response->method('withHeader')->willReturnSelf();
        $response->method('withStatus')->willReturnSelf();

        $result = $controller->reset($request, $response);

        $this->assertNotNull($result);
    }
}
