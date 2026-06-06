<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Location;

class LocationTest extends TestCase
{
    /** @test */
    public function it_has_fillable_attributes()
    {
        $location = new Location();
        $fillable = $location->getFillable();

        $expected = [
            'name', 'address', 'whatsapp', 'whatsapp_message',
            'embed_code', 'url', 'mode', 'sort_order', 'active',
        ];

        foreach ($expected as $attr) {
            $this->assertContains($attr, $fillable, "Missing fillable: {$attr}");
        }
    }

    /** @test */
    public function it_has_active_scope()
    {
        $this->assertTrue(method_exists(\App\Models\Location::class, 'scopeActive'));
    }

    /** @test */
    public function it_can_create_a_location()
    {
        $location = new Location([
            'name'    => 'Sucursal Centro',
            'address' => 'Av. Siempre Viva 123',
            'mode'    => 'embed',
            'sort_order' => 0,
            'active'  => 1,
        ]);

        $this->assertEquals('Sucursal Centro', $location->name);
        $this->assertEquals('Av. Siempre Viva 123', $location->address);
        $this->assertEquals('embed', $location->mode);
    }

    /** @test */
    public function it_returns_embed_src_from_embed_code()
    {
        $location = new Location([
            'embed_code' => '<iframe src="https://maps.google.com/maps?q=123" width="600"></iframe>',
        ]);

        $this->assertEquals('https://maps.google.com/maps?q=123', $location->getEmbedSrc());
    }

    /** @test */
    public function it_returns_null_embed_src_when_no_embed_code()
    {
        $location = new Location();
        $this->assertNull($location->getEmbedSrc());
    }

    /** @test */
    public function it_returns_null_embed_src_when_no_match()
    {
        $location = new Location(['embed_code' => '<div>no iframe</div>']);
        $this->assertNull($location->getEmbedSrc());
    }

    /** @test */
    public function it_generates_whatsapp_url()
    {
        $location = new Location([
            'whatsapp'        => '+54 9 11 5555-1234',
            'whatsapp_message' => 'Hola, quiero info',
        ]);

        $url = $location->getWhatsappUrlAttribute();
        $this->assertStringStartsWith('https://wa.me/5491155551234', $url);
        $this->assertStringContainsString('text=Hola%2C%20quiero%20info', $url);
    }

    /** @test */
    public function it_returns_null_whatsapp_url_when_empty()
    {
        $location = new Location();
        $this->assertNull($location->getWhatsappUrlAttribute());
    }

    /** @test */
    public function it_accepts_mode_button_or_embed()
    {
        $button = new Location(['mode' => 'button']);
        $embed  = new Location(['mode' => 'embed']);

        $this->assertEquals('button', $button->mode);
        $this->assertEquals('embed', $embed->mode);
    }
}
