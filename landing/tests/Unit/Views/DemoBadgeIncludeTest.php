<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

/**
 * Structural tests verifying that all required views include
 * the demo-badge partial.
 *
 * These tests read the raw Blade source files to ensure the
 * @include directive is present before </body> (front-end views)
 * or in the main content area (admin layout).
 */
class DemoBadgeIncludeTest extends TestCase
{
    /** @test */
    public function index_view_includes_demo_badge_partial()
    {
        $content = file_get_contents(__DIR__ . '/../../../resources/views/index.blade.php');
        $this->assertStringContainsString(
            "@include('partials/demo-badge')",
            $content,
            'index.blade.php must include the demo-badge partial'
        );
        // The include should be before </body>
        $bodyClosePos = strrpos($content, '</body>');
        $includePos = strrpos($content, "@include('partials/demo-badge')");
        $this->assertNotFalse($includePos, 'The @include directive must exist');
        $this->assertLessThan(
            $bodyClosePos,
            $includePos,
            'The @include must appear before </body> in index.blade.php'
        );
    }

    /** @test */
    public function menu_index_view_includes_demo_badge_partial()
    {
        $content = file_get_contents(__DIR__ . '/../../../resources/views/menu/index.blade.php');
        $this->assertStringContainsString(
            "@include('partials/demo-badge')",
            $content,
            'menu/index.blade.php must include the demo-badge partial'
        );
        // The include should be before </body>
        $bodyClosePos = strrpos($content, '</body>');
        $includePos = strrpos($content, "@include('partials/demo-badge')");
        $this->assertNotFalse($includePos, 'The @include directive must exist');
        $this->assertLessThan(
            $bodyClosePos,
            $includePos,
            'The @include must appear before </body> in menu/index.blade.php'
        );
    }

    /** @test */
    public function admin_layout_includes_demo_badge_partial()
    {
        $content = file_get_contents(__DIR__ . '/../../../resources/views/admin/layout.blade.php');
        $this->assertStringContainsString(
            "@include('partials/demo-badge')",
            $content,
            'admin/layout.blade.php must include the demo-badge partial'
        );
    }
}
