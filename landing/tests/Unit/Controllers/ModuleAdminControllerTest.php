<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Admin\ModuleAdminController;
use App\Support\Permissions;

class ModuleAdminControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = ['context' => 'master'];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /** @test */
    public function every_toggleable_sidebar_section_has_a_switch(): void
    {
        $keys = ModuleAdminController::moduleKeys();
        foreach (Permissions::CATALOG as $key => $section) {
            if ($section['module'] !== null) {
                $this->assertContains($section['module'], $keys, "falta el interruptor de {$key}");
            }
        }
        foreach (['module_builder_enabled', 'module_orders_enabled', 'module_users_enabled', 'module_seo_enabled', 'module_favicon_enabled', 'module_settings_enabled'] as $flag) {
            $this->assertContains($flag, $keys);
        }
    }

    /** @test */
    public function keeps_landing_block_switches(): void
    {
        $keys = ModuleAdminController::moduleKeys();
        foreach (['module_title_enabled', 'module_avatar_enabled', 'module_bio_enabled'] as $flag) {
            $this->assertContains($flag, $keys);
        }
    }

    /** @test */
    public function footer_is_a_root_only_setting(): void
    {
        $this->assertContains('landing_footer_enabled', ModuleAdminController::moduleKeys());
        $this->assertSame('https://gomepab.com.ar', ModuleAdminController::FOOTER_FIELDS['landing_footer_url'][0]);

        // Ajustes (que pueden usar roles no root) no acepta las claves del footer
        $src = file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/Admin/SettingsAdminController.php');
        preg_match('/\$allowedKeys = \[(.*?)\];/s', $src, $m);
        $this->assertNotEmpty($m);
        $this->assertStringNotContainsString('landing_footer', $m[1]);
    }

    /** @test */
    public function shop_link_only_when_the_shop_is_public(): void
    {
        $this->assertTrue(shop_is_public(['menu_enabled' => '1']));
        $this->assertFalse(shop_is_public(['menu_enabled' => '0']));
        $this->assertFalse(shop_is_public(['menu_enabled' => '1', 'module_menu_enabled' => '0']), 'módulo apagado: sin link y /shop 404');
    }

    /** @test */
    public function does_not_overwrite_settings_without_a_switch(): void
    {
        // demo_mode estaba en la lista sin interruptor: cada guardado lo pisaba con 0
        $this->assertNotContains('demo_mode', ModuleAdminController::moduleKeys());
    }

    /** @test */
    public function site_manager_switch_only_exists_in_master(): void
    {
        $this->assertContains('module_site_manager_enabled', ModuleAdminController::moduleKeys());
        $_SESSION = ['context' => 'site:5'];
        $this->assertNotContains('module_site_manager_enabled', ModuleAdminController::moduleKeys());
    }

    /** @test */
    public function modules_section_is_root_only(): void
    {
        $this->assertSame('root', Permissions::CATALOG['modules']['scope']);
        $this->assertArrayNotHasKey('modules', Permissions::assignable());
    }
}
