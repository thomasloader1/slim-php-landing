<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FaqItem;

class FaqItemTest extends TestCase
{
    /** @test */
    public function it_has_fillable_attributes()
    {
        $faq = new FaqItem();
        $fillable = $faq->getFillable();

        $this->assertContains('question', $fillable);
        $this->assertContains('answer', $fillable);
        $this->assertContains('sort_order', $fillable);
        $this->assertContains('active', $fillable);
    }

    /** @test */
    public function it_has_active_scope()
    {
        $this->assertTrue(method_exists(\App\Models\FaqItem::class, 'scopeActive'));
    }

    /** @test */
    public function it_can_create_a_faq_item()
    {
        $faq = new FaqItem([
            'question'   => '¿Hacen envíos?',
            'answer'     => 'Sí, hacemos envíos a todo el país.',
            'sort_order' => 1,
            'active'     => 1,
        ]);

        $this->assertEquals('¿Hacen envíos?', $faq->question);
        $this->assertEquals('Sí, hacemos envíos a todo el país.', $faq->answer);
        $this->assertEquals(1, $faq->sort_order);
    }

    /** @test */
    public function it_defaults_sort_order_to_zero()
    {
        $faq = new FaqItem(['question' => 'Test', 'answer' => 'Answer']);
        $this->assertEquals(0, $faq->sort_order);
    }

    /** @test */
    public function question_and_answer_are_strings()
    {
        $faq = new FaqItem([
            'question' => 'What is this?',
            'answer'   => 'This is a test FAQ item.',
        ]);

        $this->assertIsString($faq->question);
        $this->assertIsString($faq->answer);
    }
}
