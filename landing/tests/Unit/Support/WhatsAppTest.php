<?php

namespace Tests\Unit\Support;

use App\Support\WhatsApp;
use PHPUnit\Framework\TestCase;

class WhatsAppTest extends TestCase
{
    public function test_normalizes_formatted_number(): void
    {
        $this->assertSame('5491112345678', WhatsApp::normalizeNumber('+54 9 11 1234-5678'));
        $this->assertSame('5491112345678', WhatsApp::normalizeNumber('00 54 9 11 1234 5678'));
    }

    public function test_validates_length(): void
    {
        $this->assertTrue(WhatsApp::isValidNumber('+54 9 11 1234-5678'));
        $this->assertFalse(WhatsApp::isValidNumber('1234'));
        $this->assertFalse(WhatsApp::isValidNumber(''));
        $this->assertFalse(WhatsApp::isValidNumber(str_repeat('9', 16)));
    }

    public function test_web_url_targets_whatsapp_web_with_encoded_message(): void
    {
        $this->assertSame(
            'https://web.whatsapp.com/send?phone=5491112345678&text=Hola%21%20Quiero%20info%20%26%20precios',
            WhatsApp::webUrl('+54 9 11 1234-5678', 'Hola! Quiero info & precios')
        );
    }

    public function test_urls_omit_text_when_message_is_blank(): void
    {
        $this->assertSame('https://web.whatsapp.com/send?phone=5491112345678', WhatsApp::webUrl('5491112345678', '  '));
        $this->assertSame('https://wa.me/5491112345678', WhatsApp::appUrl('5491112345678', null));
    }

    public function test_app_url_uses_wa_me(): void
    {
        $this->assertSame('https://wa.me/5491112345678?text=Hola%20%C3%B1', WhatsApp::appUrl('+54 9 11 1234 5678', 'Hola ñ'));
    }
}
