<?php

namespace Tests\Feature;

use Tests\TestCase;

class FooterRenderTest extends TestCase
{
    public function test_footer_does_not_leak_raw_blade(): void
    {
        $html = $this->get(route('home'))->getContent();
        $this->assertStringNotContainsString('strtolower', $html, 'Raw Blade leaked into output');
        $this->assertStringContainsString('support@', $html, 'No support@ email rendered');
    }
}