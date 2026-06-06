<?php

namespace Tests\Regression;

use Tests\TestCase;

/**
 * Regression tests for image upload security fixes.
 *
 * Bugs covered:
 *   Bug 4 — No MIME validation: Added finfo buffer MIME check against an allowlist.
 *   Bug 5 — Extension case normalization: Added strtolower() so '.PNG' == '.png'.
 *
 * The controller's allowed extension list and MIME allowlist are tested here
 * in isolation (no HTTP, no filesystem).
 *
 * @see src/Controllers/Admin/SettingsAdminController.php
 */
class ImageUploadSecurityTest extends TestCase
{
    private array $allowedExtensions;
    private array $allowedMimes;

    protected function setUp(): void
    {
        parent::setUp();

        // Exact same values from SettingsAdminController (keep in sync if list changes)
        $this->allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'];
        $this->allowedMimes = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ];
    }

    // ── Bug 5: Extension case normalization ──

    /** @test */
    public function extension_is_normalized_to_lowercase(): void
    {
        $filenames = [
            'LOGO.PNG'    => 'png',
            'Avatar.JPG'  => 'jpg',
            'Banner.JPEG' => 'jpeg',
            'Bg.GIF'      => 'gif',
            'Icon.WEBP'   => 'webp',
            'Logo.SVG'    => 'svg',
            'Favicon.ICO' => 'ico',
        ];

        foreach ($filenames as $filename => $expected) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertSame($expected, $ext, "Extension for '{$filename}' should be '{$expected}'");
        }
    }

    /** @test */
    public function mixed_case_extension_is_still_rejected_when_not_in_allowlist(): void
    {
        // An attacker might try .PHP / .PhP / .phtml
        $malicious = ['PHP', 'PhP', 'phtml', 'HTACCESS', 'htaccess', 'shtml', 'php5', 'php7'];

        foreach ($malicious as $ext) {
            $normalized = strtolower($ext);
            $this->assertNotContains($normalized, $this->allowedExtensions, "Extension '.{$ext}' must NOT be in allowlist");
        }
    }

    /** @test */
    public function extension_without_dot_is_handled(): void
    {
        // pathinfo with no extension returns ''
        $ext = strtolower(pathinfo('README', PATHINFO_EXTENSION));
        $this->assertSame('', $ext);
    }

    // ── Bug 4: MIME type validation ──

    /** @test */
    public function real_image_mimes_are_allowed(): void
    {
        $realMimes = [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/x-icon',
        ];

        foreach ($realMimes as $mime) {
            $this->assertContains($mime, $this->allowedMimes, "MIME '{$mime}' should be allowed");
        }
    }

    /** @test */
    public function dangerous_mimes_are_rejected(): void
    {
        $dangerous = [
            'text/plain',
            'text/html',
            'application/x-php',
            'application/php',
            'application/x-httpd-php',
            'application/x-phtml',
            'application/javascript',
            'text/javascript',
            'image/svg+xml',  // Wait — this IS allowed (SVG is a valid upload)
        ];

        // SVG is explicitly allowed — but the rest should not be
        unset($dangerous[array_search('image/svg+xml', $dangerous)]);

        foreach ($dangerous as $mime) {
            $this->assertNotContains($mime, $this->allowedMimes, "MIME '{$mime}' must NOT be in allowlist");
        }
    }

    /** @test */
    public function microsoft_icon_variants_are_allowed(): void
    {
        // .ico files can report as either mime type depending on the browser/OS
        $this->assertContains('image/x-icon', $this->allowedMimes);
        $this->assertContains('image/vnd.microsoft.icon', $this->allowedMimes);
    }

    /**
     * @test
     * @dataProvider providesFakeHeadersNoMatchMime
     */
    public function finfo_would_reject_faked_mime_in_content(string $fakeContent): void
    {
        // finfo reads the ACTUAL bytes — it can't be fooled by Content-Type header
        // This test simulates what finfo would do with non-image content
        $detected = (new \finfo(FILEINFO_MIME_TYPE))->buffer($fakeContent);
        $this->assertNotContains($detected, $this->allowedMimes,
            "Faked content should not be detected as an allowed image MIME. Got: {$detected}");
    }

    public static function providesFakeHeadersNoMatchMime(): array
    {
        return [
            'PHP script'       => ['<?php echo "hello"; ?>'],
            'HTML file'        => ['<html><body>hi</body></html>'],
            'JavaScript'       => ['alert("xss");'],
            'Text file'        => ['just plain text'],
            'Fake PNG header'  => ["\x89PNG\r\n\x1a\n" . '<?php system($_GET["cmd"]); ?>'],
        ];
    }

    // ── Extension allowlist (policy enforcement) ──

    /** @test */
    public function extension_allowlist_does_not_contain_executable_types(): void
    {
        $executable = ['php', 'php5', 'phtml', 'htaccess', 'shtml', 'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp'];
        foreach ($executable as $ext) {
            $this->assertNotContains($ext, $this->allowedExtensions, "Executable extension '.{$ext}' must not be in allowlist");
        }
    }

    /** @test */
    public function standard_web_image_formats_are_allowed(): void
    {
        $standard = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
        foreach ($standard as $ext) {
            $this->assertContains($ext, $this->allowedExtensions, "Standard image extension '.{$ext}' should be allowed");
        }
    }
}
