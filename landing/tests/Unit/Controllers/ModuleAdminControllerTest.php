<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Admin\ModuleAdminController;

class ModuleAdminControllerTest extends TestCase
{
    /** @test */
    public function module_keys_includes_demo_mode()
    {
        $keys = ModuleAdminController::MODULE_KEYS;

        $this->assertIsArray($keys);
        $this->assertContains('demo_mode', $keys);
    }

    /** @test */
    public function module_keys_includes_existing_keys()
    {
        $keys = ModuleAdminController::MODULE_KEYS;

        $this->assertContains('module_title_enabled', $keys);
        $this->assertContains('module_avatar_enabled', $keys);
        $this->assertContains('module_links_enabled', $keys);
        $this->assertContains('module_bio_enabled', $keys);
        $this->assertContains('module_locations_enabled', $keys);
        $this->assertContains('module_menu_enabled', $keys);
        $this->assertContains('module_site_manager_enabled', $keys);
    }

    /** @test */
    public function controller_accepts_container()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $controller = new ModuleAdminController($container);
        $this->assertInstanceOf(ModuleAdminController::class, $controller);
    }

    /** @test */
    public function has_index_and_update_methods()
    {
        $this->assertTrue(method_exists(ModuleAdminController::class, 'index'));
        $this->assertTrue(method_exists(ModuleAdminController::class, 'update'));
    }
}
