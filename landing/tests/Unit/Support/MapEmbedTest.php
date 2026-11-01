<?php

namespace Tests\Unit\Support;

use App\Support\MapEmbed;
use PHPUnit\Framework\TestCase;

class MapEmbedTest extends TestCase
{
    public function test_extracts_src_from_google_iframe(): void
    {
        $html = '<iframe src="https://www.google.com/maps/embed?pb=!1m18&amp;x=1" width="600"></iframe>';
        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18&x=1', MapEmbed::srcFromIframe($html));
    }

    public function test_rejects_iframe_from_other_hosts_or_schemes(): void
    {
        $this->assertNull(MapEmbed::srcFromIframe('<iframe src="https://evil.example/maps"></iframe>'));
        $this->assertNull(MapEmbed::srcFromIframe('<iframe src="javascript:alert(1)"></iframe>'));
        $this->assertNull(MapEmbed::srcFromIframe('<div>sin iframe</div>'));
        $this->assertNull(MapEmbed::srcFromIframe(null));
    }

    public function test_uses_coordinates_from_google_maps_link(): void
    {
        $url = 'https://www.google.com/maps/place/Obelisco/@-34.6037345,-58.3815704,17z/data=!3m1';
        $this->assertSame('https://www.google.com/maps?q=-34.6037345%2C-58.3815704&output=embed', MapEmbed::fromUrlOrAddress($url));
    }

    public function test_uses_q_param_and_place_name(): void
    {
        $this->assertSame(
            'https://www.google.com/maps?q=Av%20Corrientes%201234&output=embed',
            MapEmbed::fromUrlOrAddress('https://maps.google.com/?q=Av+Corrientes+1234')
        );
        $this->assertSame(
            'https://www.google.com/maps?q=Teatro%20Col%C3%B3n%2C%20Buenos%20Aires&output=embed',
            MapEmbed::fromUrlOrAddress('https://www.google.com.ar/maps/place/Teatro+Col%C3%B3n,+Buenos+Aires/')
        );
    }

    public function test_short_links_fall_back_to_address(): void
    {
        $this->assertSame(
            'https://www.google.com/maps?q=San%20Mart%C3%ADn%20500%2C%20Rosario&output=embed',
            MapEmbed::fromUrlOrAddress('https://maps.app.goo.gl/AbCdEf123', 'San Martín 500, Rosario')
        );
        $this->assertNull(MapEmbed::fromUrlOrAddress('https://maps.app.goo.gl/AbCdEf123'));
    }

    public function test_plain_text_in_url_field_is_treated_as_address(): void
    {
        $this->assertSame('https://www.google.com/maps?q=Calle%20Falsa%20123&output=embed', MapEmbed::fromUrlOrAddress('Calle Falsa 123'));
        $this->assertNull(MapEmbed::fromUrlOrAddress(null, null));
    }
}
