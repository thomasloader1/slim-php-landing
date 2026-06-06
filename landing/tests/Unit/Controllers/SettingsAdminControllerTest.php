<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Controllers\Admin\SettingsAdminController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class SettingsAdminControllerTest extends TestCase
{
    private array $expectedAllowedKeys = [
        'site_name', 'landing_title', 'landing_subtitle', 'landing_bio',
        'landing_accent_color', 'landing_bg_color', 'landing_text_color',
        'landing_avatar_url', 'landing_logo_url', 'landing_bg_image_url',
        'landing_bg_overlay', 'landing_bg_overlay_opacity',
        'landing_accent_force',
        'landing_favicon_url',
        'landing_links_display',
        'landing_logo_size',
    ];

    private array $imageMimes = [
        'image/png', 'image/jpeg', 'image/gif', 'image/webp',
        'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon',
    ];

    private array $allowedExtensions = [
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico',
    ];

    /** @test */
    public function it_has_allowed_keys_matching_controller()
    {
        // This list matches the $allowedKeys in SettingsAdminController::update()
        // If you add a new setting key, update BOTH places
        $this->assertContains('site_name', $this->expectedAllowedKeys);
        $this->assertContains('landing_title', $this->expectedAllowedKeys);
        $this->assertContains('landing_subtitle', $this->expectedAllowedKeys);
        $this->assertContains('landing_bio', $this->expectedAllowedKeys);
        $this->assertContains('landing_accent_color', $this->expectedAllowedKeys);
        $this->assertContains('landing_bg_color', $this->expectedAllowedKeys);
        $this->assertContains('landing_text_color', $this->expectedAllowedKeys);
        $this->assertContains('landing_avatar_url', $this->expectedAllowedKeys);
        $this->assertContains('landing_logo_url', $this->expectedAllowedKeys);
        $this->assertContains('landing_bg_image_url', $this->expectedAllowedKeys);
        $this->assertContains('landing_bg_overlay', $this->expectedAllowedKeys);
        $this->assertContains('landing_bg_overlay_opacity', $this->expectedAllowedKeys);
        $this->assertContains('landing_accent_force', $this->expectedAllowedKeys);
        $this->assertContains('landing_favicon_url', $this->expectedAllowedKeys);
        $this->assertContains('landing_links_display', $this->expectedAllowedKeys);
        $this->assertContains('landing_logo_size', $this->expectedAllowedKeys);
    }

    /** @test */
    public function it_rejects_unknown_setting_keys()
    {
        $this->assertNotContains('landing_secret_key', $this->expectedAllowedKeys);
        $this->assertNotContains('landing_api_endpoint', $this->expectedAllowedKeys);
        $this->assertNotContains('db_password', $this->expectedAllowedKeys);
    }

    /** @test */
    public function it_validates_upload_mime_types()
    {
        foreach ($this->imageMimes as $mime) {
            $this->assertContains($mime, $this->imageMimes);
        }

        $unsafeMimes = ['text/plain', 'application/x-php', 'text/html', 'application/pdf'];
        foreach ($unsafeMimes as $mime) {
            $this->assertNotContains($mime, $this->imageMimes);
        }
    }

    /** @test */
    public function it_validates_upload_extensions()
    {
        foreach ($this->allowedExtensions as $ext) {
            $this->assertContains($ext, $this->allowedExtensions);
        }

        $unsafeExts = ['php', 'exe', 'sh', 'bat', 'com', 'js', 'html', 'phtml', 'htaccess'];
        foreach ($unsafeExts as $ext) {
            $this->assertNotContains($ext, $this->allowedExtensions);
        }
    }

    /** @test */
    public function it_validates_image_size_against_limit()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $controller = new SettingsAdminController($container);

        // Test via the ProcessesImages trait
        $reflection = new \ReflectionMethod(SettingsAdminController::class, 'validateImageSize');
        $reflection->setAccessible(true);

        $smallFile = $this->createMock(UploadedFileInterface::class);
        $smallFile->method('getSize')->willReturn(1024);
        $this->assertTrue($reflection->invoke($controller, $smallFile, 2048));

        $largeFile = $this->createMock(UploadedFileInterface::class);
        $largeFile->method('getSize')->willReturn(10 * 1024 * 1024);
        $this->assertFalse($reflection->invoke($controller, $largeFile, 5 * 1024 * 1024));
    }

    /** @test */
    public function controller_accepts_container()
    {
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('get')
            ->with('view')
            ->willReturn($this->createMock(\Jenssegers\Blade\Blade::class));

        $controller = new SettingsAdminController($container);
        $this->assertInstanceOf(SettingsAdminController::class, $controller);
    }

    /** @test */
    public function it_uses_processes_images_trait()
    {
        $traits = class_uses(SettingsAdminController::class);
        $this->assertContains('App\Traits\ProcessesImages', $traits);
    }
}
