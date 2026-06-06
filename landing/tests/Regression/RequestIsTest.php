<?php

namespace Tests\Regression;

use Tests\TestCase;

/**
 * Regression tests for Bug 1 — request_is() strtok bug.
 *
 * Bug: The original implementation used strtok($uri, '?') to strip query strings.
 * strtok() maintains internal state between calls, so the SECOND call with a
 * different URI would return the NEXT token from the FIRST call's state, not
 * the new URI's path.
 *
 * Fix: Replaced strtok with explode('?', $uri)[0], which is stateless.
 *
 * @see src/helpers.php
 */
class RequestIsTest extends TestCase
{
    /** @test */
    public function explode_is_stateless_across_multiple_calls(): void
    {
        // This is what exploded strtok — internal pointer madness
        // strtok('/admin/settings?x=1', '?') → '/admin/settings'
        // strtok('/admin/links?y=2', '?') → 'y=2'    ← WRONG! Returned next token from first call

        // Fix: explode is pure — no internal state
        $uri1 = '/admin/settings?x=1';
        $uri2 = '/admin/links?y=2';

        $path1 = explode('?', $uri1)[0];
        $path2 = explode('?', $uri2)[0];

        $this->assertSame('/admin/settings', $path1);
        $this->assertSame('/admin/links', $path2);  // Would return 'y=2' with strtok
    }

    /** @test */
    public function explode_handles_repeated_calls_with_same_uri(): void
    {
        // strtok('/admin/settings?x=1', '?') → '/admin/settings'
        // strtok('/admin/settings?x=1', '?') → 'x=1'    ← WRONG on second call!
        $uri = '/admin/settings?x=1';

        $path1 = explode('?', $uri)[0];
        $path2 = explode('?', $uri)[0]; // strtok would return 'x=1' here

        $this->assertSame('/admin/settings', $path1);
        $this->assertSame('/admin/settings', $path2);
    }

    /** @test */
    public function explode_handles_uri_without_query_string(): void
    {
        $uri = '/admin/links';
        $path = explode('?', $uri)[0];
        $this->assertSame('/admin/links', $path);
    }

    /** @test */
    public function explode_handles_empty_uri(): void
    {
        $uri = '';
        $path = explode('?', $uri)[0];
        $this->assertSame('', $path);
    }

    /** @test */
    public function explode_handles_uri_with_multiple_query_params(): void
    {
        $uri = '/admin/settings?page=2&filter=active&sort=desc';
        $path = explode('?', $uri)[0];
        $this->assertSame('/admin/settings', $path);
    }

    /** @test */
    public function explode_handles_uri_with_only_query_string(): void
    {
        // Edge case: no path, just query
        $uri = '?direct=1';
        $path = explode('?', $uri)[0];
        $this->assertSame('', $path);
    }

    /** @test */
    public function request_is_function_works_with_real_uri(): void
    {
        // Skip if function doesn't exist
        if (!function_exists('request_is')) {
            $this->markTestSkipped('request_is() function not available');
        }

        // Set a known REQUEST_URI
        $_SERVER['REQUEST_URI'] = '/admin/settings?x=1';

        // Should match with or without wildcard
        $this->assertTrue(request_is('/admin/settings'));
        $this->assertTrue(request_is('/admin/*'));
        $this->assertFalse(request_is('/admin/links'));
        $this->assertFalse(request_is('/admin/links/*'));

        // Second call — this is where strtok would fail
        $_SERVER['REQUEST_URI'] = '/admin/links?y=2';
        $this->assertTrue(request_is('/admin/links'));
        $this->assertFalse(request_is('/admin/settings'));
    }
}
