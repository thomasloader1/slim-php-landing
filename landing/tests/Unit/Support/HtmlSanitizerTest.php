<?php

namespace Tests\Unit\Support;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_quill_formatting(): void
    {
        $html = '<p><strong>Hola</strong> soy <em>chef</em></p><ol><li>Uno</li></ol><ul><li>Dos</li></ul>';
        $this->assertSame($html, HtmlSanitizer::bio($html));
    }

    public function test_removes_scripts_handlers_and_unknown_tags(): void
    {
        $out = HtmlSanitizer::bio('<p onclick="x()">Hola<script>alert(1)</script></p><img src=x onerror=alert(1)><div style="color:red">texto</div>');
        $this->assertSame('<p>Hola</p>texto', $out);
    }

    public function test_links_only_allow_safe_schemes(): void
    {
        $this->assertSame(
            '<p><a href="https://ok.com" target="_blank" rel="noopener noreferrer nofollow">ok</a></p>',
            HtmlSanitizer::bio('<p><a href="https://ok.com" onclick="x">ok</a></p>')
        );
        $this->assertSame('<p>malo</p>', HtmlSanitizer::bio('<p><a href="javascript:alert(1)">malo</a></p>'));
        $this->assertSame('<p>malo</p>', HtmlSanitizer::bio('<p><a href=" JaVaScRiPt:alert(1)">malo</a></p>'));
    }

    public function test_empty_quill_document_becomes_empty_string(): void
    {
        $this->assertSame('', HtmlSanitizer::bio('<p><br></p>'));
        $this->assertSame('', HtmlSanitizer::bio('   '));
        $this->assertSame('', HtmlSanitizer::bio(null));
    }

    public function test_plain_text_is_escaped_with_line_breaks(): void
    {
        $this->assertSame('Línea 1<br>' . "\n" . 'a &lt; b &amp; c', HtmlSanitizer::bio("Línea 1\na < b & c"));
    }

    public function test_preserves_utf8(): void
    {
        $this->assertSame('<p>Ñandú café</p>', HtmlSanitizer::bio('<p>Ñandú café</p>'));
    }

    public function test_sanitizing_twice_is_stable(): void
    {
        $once = HtmlSanitizer::bio('<p><b>x</b><a href="mailto:a@b.c">m</a></p><iframe src="x"></iframe>');
        $this->assertSame($once, HtmlSanitizer::bio($once));
    }
}
