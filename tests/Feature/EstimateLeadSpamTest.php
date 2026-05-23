<?php

namespace Tests\Feature;

use App\Models\EstimateLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EstimateLeadSpamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);
    }

    private function mockTurnstileSuccess(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ]),
        ]);
    }

    private function mockTurnstileFailure(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
            ]),
        ]);
    }

    public function test_it_stores_estimate_lead_when_verification_passes(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/estimate')->post('/estimate', [
            'first_name' => 'สมชาย',
            'last_name' => 'ดีใจ',
            'gender' => 'male',
            'birthday' => '1995-05-15',
            'work_type' => 'owner',
            'current_phone' => '0812345678',
            'main_phone' => '0898765432',
            'email' => 'somchai@example.com',
            'goal' => 'money',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('estimate_leads', [
            'first_name' => 'สมชาย',
            'last_name' => 'ดีใจ',
            'main_phone' => '0898765432',
            'email' => 'somchai@example.com',
        ]);
    }

    public function test_it_rejects_estimate_lead_when_turnstile_verification_fails(): void
    {
        $this->mockTurnstileFailure();

        $response = $this->from('/estimate')->post('/estimate', [
            'first_name' => 'สมชาย',
            'last_name' => 'ดีใจ',
            'gender' => 'male',
            'birthday' => '1995-05-15',
            'work_type' => 'owner',
            'current_phone' => '0812345678',
            'main_phone' => '0898765432',
            'email' => 'somchai@example.com',
            'goal' => 'money',
            'cf-turnstile-response' => 'invalid-token',
        ]);

        $response->assertRedirect('/estimate');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertSame(0, EstimateLead::query()->count());
    }

    public function test_it_discards_honeypot_submissions(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/estimate')->post('/estimate', [
            'first_name' => 'Spambot',
            'last_name' => 'Attack',
            'gender' => 'male',
            'work_type' => 'owner',
            'main_phone' => '0898765432',
            'email' => 'spambot@example.com',
            'goal' => 'money',
            'website' => 'http://spam-link.example',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect('/estimate');
        $response->assertSessionHas('estimate_status_message');
        $this->assertSame(0, EstimateLead::query()->count());
    }

    public function test_it_rejects_gibberish_names(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/estimate')->post('/estimate', [
            'first_name' => 'OkzMsNaBDcdanZsRmAP',
            'last_name' => 'dCKPfuvwAxCDuKsKQN',
            'gender' => 'male',
            'work_type' => 'owner',
            'main_phone' => '0898765432',
            'email' => 'spambot@example.com',
            'goal' => 'money',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect('/estimate');
        $response->assertSessionHas('estimate_status_message');
        $this->assertSame(0, EstimateLead::query()->count());
    }

    public function test_it_rejects_excessive_dots_in_gmail(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/estimate')->post('/estimate', [
            'first_name' => 'สมชาย',
            'last_name' => 'ดีใจ',
            'gender' => 'male',
            'work_type' => 'owner',
            'main_phone' => '0898765432',
            'email' => 'e.x.e.f.o.me.w.8.6@gmail.com',
            'goal' => 'money',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $response->assertRedirect('/estimate');
        $response->assertSessionHas('estimate_status_message');
        $this->assertSame(0, EstimateLead::query()->count());
    }

    public function test_it_rate_limits_estimate_submissions(): void
    {
        $this->mockTurnstileSuccess();

        $server = [
            'REMOTE_ADDR' => '203.0.113.66',
            'HTTP_USER_AGENT' => 'EstimateRateLimitTest',
        ];

        // Send 5 successful requests
        foreach (range(1, 5) as $attempt) {
            $response = $this
                ->withServerVariables($server)
                ->from('/estimate')
                ->post('/estimate', [
                    'first_name' => 'ผู้ส่งที่ ' . $attempt,
                    'last_name' => 'ดีใจ',
                    'gender' => 'male',
                    'work_type' => 'owner',
                    'main_phone' => '0898765432',
                    'email' => 'user' . $attempt . '@example.com',
                    'goal' => 'money',
                    'cf-turnstile-response' => 'valid-token',
                ]);

            $response->assertRedirect();
        }

        // 6th attempt should be blocked
        $blockedResponse = $this
            ->withServerVariables($server)
            ->from('/estimate')
            ->post('/estimate', [
                'first_name' => 'ผู้ส่งที่ 6',
                'last_name' => 'ดีใจ',
                'gender' => 'male',
                'work_type' => 'owner',
                'main_phone' => '0898765432',
                'email' => 'user6@example.com',
                'goal' => 'money',
                'cf-turnstile-response' => 'valid-token',
            ]);

        $blockedResponse->assertRedirect('/estimate');
        $blockedResponse->assertSessionHasErrors('main_phone');
        $this->assertSame(5, EstimateLead::query()->count());
    }
}
