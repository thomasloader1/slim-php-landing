<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Admin\LinkAdminController;

class LinkAdminControllerTest extends TestCase
{
    /** @test */
    public function it_has_crud_methods()
    {
        $this->assertTrue(method_exists(LinkAdminController::class, 'index'));
        $this->assertTrue(method_exists(LinkAdminController::class, 'create'));
        $this->assertTrue(method_exists(LinkAdminController::class, 'store'));
        $this->assertTrue(method_exists(LinkAdminController::class, 'edit'));
        $this->assertTrue(method_exists(LinkAdminController::class, 'update'));
        $this->assertTrue(method_exists(LinkAdminController::class, 'delete'));
    }

    /** @test */
    public function validate_link_data_rejects_empty_title()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => '',
            'url' => 'https://example.com',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('título', $error);
    }

    /** @test */
    public function validate_link_data_rejects_title_over_150_chars()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => str_repeat('a', 151),
            'url' => 'https://example.com',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('150', $error);
    }

    /** @test */
    public function validate_link_data_rejects_empty_url()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => 'My Link',
            'url' => '',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('URL', $error);
    }

    /** @test */
    public function validate_link_data_rejects_invalid_url()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => 'My Link',
            'url' => 'not-a-url',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('URL', $error);
    }

    /** @test */
    public function validate_link_data_rejects_invalid_color_hex()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => 'My Link',
            'url' => 'https://example.com',
            'color' => 'not-a-hex',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('color', strtolower($error));
    }

    /** @test */
    public function validate_link_data_rejects_invalid_bg_color_hex()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => 'My Link',
            'url' => 'https://example.com',
            'bg_color' => '#zzz',
        ]);

        $this->assertNotNull($error);
        $this->assertStringContainsString('color de fondo', strtolower($error));
    }

    /** @test */
    public function validate_link_data_accepts_valid_data()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => 'My Link',
            'url' => 'https://example.com',
            'color' => '#ff6600',
            'bg_color' => '#000000',
        ]);

        $this->assertNull($error);
    }

    /** @test */
    public function validate_link_data_allows_empty_color_and_bg_color()
    {
        $controller = $this->createPartialMock(LinkAdminController::class, []);
        $reflection = new \ReflectionMethod(LinkAdminController::class, 'validateLinkData');
        $reflection->setAccessible(true);

        $error = $reflection->invoke($controller, [
            'title' => 'My Link',
            'url' => 'https://example.com',
        ]);

        $this->assertNull($error);
    }

    /** @test */
    public function controller_accepts_container()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $controller = new LinkAdminController($container);
        $this->assertInstanceOf(LinkAdminController::class, $controller);
    }
}
