<?php

namespace Tests\Unit\Views;

use Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 * Unit tests for the demo-badge Blade partial.
 *
 * The partial should render a fixed-position "DEMO" badge
 * only when $demoMode is true, and output nothing otherwise.
 */
class DemoBadgePartialTest extends TestCase
{
    private Factory $view;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem();
        $cachePath = sys_get_temp_dir() . '/blade-cache-' . uniqid();
        @mkdir($cachePath, 0777, true);

        $compiler = new BladeCompiler($filesystem, $cachePath);
        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });

        $viewPaths = [__DIR__ . '/../../../resources/views'];
        $finder = new FileViewFinder($filesystem, $viewPaths);
        $dispatcher = new Dispatcher(new Container());

        $this->view = new Factory($engineResolver, $finder, $dispatcher);
    }

    /** @test */
    public function it_renders_the_badge_when_demo_mode_is_true()
    {
        $html = $this->view->make('partials/demo-badge', ['demoMode' => true])->render();

        $this->assertStringContainsString('DEMO', $html);
        $this->assertStringContainsString('fixed', $html);
        $this->assertStringContainsString('bottom-4', $html);
        $this->assertStringContainsString('right-4', $html);
        $this->assertStringContainsString('z-50', $html);
        $this->assertStringContainsString('bg-orange-500', $html);
        $this->assertStringContainsString('text-white', $html);
    }

    /** @test */
    public function it_renders_nothing_when_demo_mode_is_false()
    {
        $html = $this->view->make('partials/demo-badge', ['demoMode' => false])->render();

        $this->assertStringNotContainsString('DEMO', $html);
        $this->assertEmpty(trim($html));
    }

    /** @test */
    public function it_renders_nothing_when_demo_mode_is_not_set()
    {
        $html = $this->view->make('partials/demo-badge', [])->render();

        $this->assertEmpty(trim($html));
    }
}
