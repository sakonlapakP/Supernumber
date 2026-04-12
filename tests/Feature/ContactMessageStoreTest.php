<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactMessageStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_contact_message_data_to_database(): void
    {
        $response = $this->post('/contact-us', [
            'name' => 'สมชาย ใจดี',
            'phone' => '081-234-5678',
            'message' => 'ต้องการให้ช่วยแนะนำเบอร์ที่เหมาะกับงานขาย',
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
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ]),
        ]);

        $response = $this->post('/contact-us', [
            'name' => 'สมหญิง พร้อมส่ง',
            'phone' => '089-111-2222',
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
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
            ]),
        ]);

        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'สมหญิง ไม่ผ่าน',
            'phone' => '089-111-3333',
            'message' => 'ขอให้ช่วยติดต่อกลับเรื่องแพ็กเกจ',
            'cf-turnstile-response' => 'bad-token',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHasErrors('cf-turnstile-response');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_rejects_contact_phone_when_not_10_digits(): void
    {
        $response = $this->from('/contact-us')->post('/contact-us', [
            'name' => 'สมหญิง สุขใจ',
            'phone' => '12345',
            'message' => 'อยากสอบถามรายละเอียดเพิ่มเติม',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHasErrors('phone');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_discards_honeypot_submissions(): void
    {
        $response = $this->post('/contact-us', [
            'name' => 'Spam Bot',
            'phone' => '0812345678',
            'message' => 'spam message',
            'website' => 'https://spam.example',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHas('contact_status_message');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_discards_messages_flagged_as_spam(): void
    {
        $response = $this->post('/contact-us', [
            'name' => 'Marie Ketner',
            'phone' => '0827714900',
            'message' => 'Quick note, this free tool can promote your site across classified submitter lists and bring more traffic: https://classifiedsubmitter.com',
        ]);

        $response->assertRedirect('/contact-us');
        $response->assertSessionHas('contact_status_message');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_it_rate_limits_repeated_submissions(): void
    {
        $server = [
            'REMOTE_ADDR' => '203.0.113.55',
            'HTTP_USER_AGENT' => 'ContactRateLimitTest',
        ];

        foreach (range(1, 5) as $attempt) {
            $response = $this
                ->withServerVariables($server)
                ->post('/contact-us', [
                    'name' => 'ลูกค้าทดลอง',
                    'phone' => '0812345678',
                    'message' => 'ทดสอบครั้งที่ ' . $attempt,
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
            ]);

        $blockedResponse->assertRedirect('/contact-us');
        $blockedResponse->assertSessionHasErrors('contact');
        $this->assertSame(5, ContactMessage::query()->count());
    }
}
