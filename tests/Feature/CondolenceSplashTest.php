<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CondolenceSplashTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that condolence splash screen renders before the expiry date.
     */
    public function test_condolence_splash_renders_before_expiry(): void
    {
        // Travel to June 20, 2026 (before September 13, 2026)
        Carbon::setTestNow(Carbon::parse('2026-06-20 12:00:00'));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="condolence-splash"', false);
        $response->assertSee('images/condolences.jpg', false);

        // Reset time
        Carbon::setTestNow();
    }

    /**
     * Test that condolence splash screen does not render after the expiry date.
     */
    public function test_condolence_splash_does_not_render_after_expiry(): void
    {
        // Travel to September 14, 2026 (after September 13, 2026)
        Carbon::setTestNow(Carbon::parse('2026-09-14 12:00:00'));

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('id="condolence-splash"');

        // Reset time
        Carbon::setTestNow();
    }
}
