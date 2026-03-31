<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_contact_messages_page(): void
    {
        ContactMessage::query()->create([
            'name' => 'ลูกค้าทดสอบ',
            'phone' => '0812345678',
            'message' => 'ขอให้ติดต่อกลับเรื่องแพ็กเกจ',
            'submitted_at' => now(),
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-contact-view',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.contact-messages'));

        $response->assertOk();
        $response->assertSee('Contact Messages');
        $response->assertSee('ลูกค้าทดสอบ');
        $response->assertSee('0812345678');
    }

    public function test_admin_can_delete_contact_message(): void
    {
        $message = ContactMessage::query()->create([
            'name' => 'ลูกค้าลบข้อมูล',
            'phone' => '0899999999',
            'message' => 'รบกวนติดต่อกลับ',
            'submitted_at' => now(),
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-contact-delete',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->delete(route('admin.contact-messages.delete', $message));

        $response->assertRedirect();
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('contact_messages', [
            'id' => $message->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
