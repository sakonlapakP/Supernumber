<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactMessageStoreTest extends TestCase
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

    public function test_it_stores_contact_message_data_to_database(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'สมชาย ใจดี',
            'phone' => '0812345678',
            'message' => 'ต้องการให้ช่วยแนะนำเบอร์ที่เหมาะกับงานขาย',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHas('contact_status_message');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'สมชาย ใจดี',
            'phone' => '0812345678',
            'message' => 'ต้องการให้ช่วยแนะนำเบอร์ที่เหมาะกับงานขาย',
        ]);
    }

    public function test_it_stores_contact_message_when_turnstile_verification_passes(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'สมหญิง พร้อมส่ง',
            'phone' => '0891112222',
            'message' => 'ขอให้ช่วยติดต่อกลับเพื่อแนะนำเบอร์สำหรับงานขาย',
            'cf-turnstile-response' => 'good-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHas('contact_status_message');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'สมหญิง พร้อมส่ง',
            'phone' => '0891112222',
        ]);
    }

    public function test_it_rejects_contact_message_when_turnstile_verification_fails(): void
    {
        $this->mockTurnstileFailure();

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'สมหญิง ไม่ผ่าน',
            'phone' => '0891113333',
            'message' => 'ขอให้ช่วยติดต่อกลับเรื่องแพ็กเกจ',
            'cf-turnstile-response' => 'bad-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_rejects_contact_phone_when_not_10_digits(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'สมหญิง สุขใจ',
            'phone' => '12345',
            'message' => 'อยากสอบถามรายละเอียดเพิ่มเติม',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHasErrors('phone');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_discards_honeypot_submissions(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'Spam Bot',
            'phone' => '0812345678',
            'message' => 'spam message',
            'website' => 'https://spam.example',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHas('contact_status_message');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_discards_messages_flagged_as_spam(): void
    {
        $this->mockTurnstileSuccess();

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'Marie Ketner',
            'phone' => '0827714900',
            'message' => 'Quick note, this free tool can promote your site across classified submitter lists and bring more traffic: https://classifiedsubmitter.com',
            'cf-turnstile-response' => 'test-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHas('contact_status_message');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_rate_limits_repeated_submissions(): void
    {
        $this->mockTurnstileSuccess();

        $server = [
            'REMOTE_ADDR' => '203.0.113.55',
            'HTTP_USER_AGENT' => 'ContactRateLimitTest',
        ];

        foreach (range(1, 5) as $attempt) {
            $response = $this
                ->withServerVariables($server)
                ->from('/contact-us')
                ->post('/contact-us', [
                    'name' => 'ลูกค้าทดลอง',
                    'phone' => '0812345678',
                    'message' => 'ทดสอบครั้งที่ ' . $attempt,
                    'cf-turnstile-response' => 'test-token',
                ]);

            $response->assertRedirect('/contact-us');
        }

        $blockedResponse = $this
            ->withServerVariables($server)
            ->from('/contact-us')
            ->post('/contact-us', [
                'name' => 'ลูกค้าทดลอง',
                'phone' => '0812345678',
                'message' => 'ทดสอบครั้งที่ 6',
                'cf-turnstile-response' => 'test-token',
            ]);

        $blockedResponse->assertRedirect('/contact-us');
        $blockedResponse->assertSessionHasErrors('contact');
        $this->assertSame(5, ContactMessage::query()->count());
    }
}
