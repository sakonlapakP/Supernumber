<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
