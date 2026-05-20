<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminHoldNumbersTest extends TestCase
{
    use RefreshDatabase;

    public function test_postpaid_order_holds_number_and_admin_hold_page_lists_it(): void
    {
        Storage::fake('public');

        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0644514194',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 1199,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->post(route('book.save-step2'), [
            'ordered_number' => '064-451-4194',
            'selected_package' => 1199,
            'title_prefix' => 'คุณ',
            'first_name' => 'สมชาย',
            'last_name' => 'ทดสอบ',
            'email' => 'postpaid@example.com',
            'current_phone' => '0811112222',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time_slot' => '10:00-11:00',
            'payment_slip' => UploadedFile::fake()->image('slip.jpg'),
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $admin = User::factory()->create([
            'username' => 'hold-admin',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.hold-numbers'))
            ->assertOk()
            ->assertSee('064-451-4194');
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
