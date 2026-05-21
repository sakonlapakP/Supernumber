<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use App\Models\PhoneNumberStatusLog;
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
        $this->travelTo('2026-05-20 13:45:00');

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
            ->assertSee('064-451-4194')
            ->assertSee('คุณ สมชาย ทดสอบ')
            ->assertSee('0811112222')
            ->assertDontSee('<th>ผลรวม</th>', false)
            ->assertDontSee('<th>ประเภท</th>', false)
            ->assertDontSee('<th>วันเวลาจอง</th>', false);
    }

    public function test_admin_hold_number_shows_admin_name_when_no_order_reserved_it(): void
    {
        $this->travelTo('2026-05-20 14:10:00');

        PhoneNumber::query()->create([
            'phone_number' => '0649998888',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 1199,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Hold Tester',
            'username' => 'hold-admin-direct',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($admin))
            ->post(route('admin.hold-numbers.add'), [
                'phone_number' => '0649998888',
            ])
            ->assertRedirect(route('admin.hold-numbers'));

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.hold-numbers'))
            ->assertOk()
            ->assertSee('064-999-8888')
            ->assertSee('Admin Hold Tester');
    }

    public function test_admin_hold_numbers_are_listed_newest_hold_time_first(): void
    {
        $olderNumber = PhoneNumber::query()->create([
            'phone_number' => '0641112222',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 1199,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $newerNumber = PhoneNumber::query()->create([
            'phone_number' => '0643334444',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 1199,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $newerLog = PhoneNumberStatusLog::query()->create([
            'phone_number_id' => $newerNumber->id,
            'action' => 'hold',
            'from_status' => PhoneNumber::STATUS_ACTIVE,
            'to_status' => PhoneNumber::STATUS_HOLD,
        ]);
        $newerLog->forceFill([
            'created_at' => '2026-05-20 10:00:00',
            'updated_at' => '2026-05-20 10:00:00',
        ])->save();

        $olderLog = PhoneNumberStatusLog::query()->create([
            'phone_number_id' => $olderNumber->id,
            'action' => 'hold',
            'from_status' => PhoneNumber::STATUS_ACTIVE,
            'to_status' => PhoneNumber::STATUS_HOLD,
        ]);
        $olderLog->forceFill([
            'created_at' => '2026-05-20 15:30:00',
            'updated_at' => '2026-05-20 15:30:00',
        ])->save();

        $admin = User::factory()->create([
            'username' => 'hold-admin-sort',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($admin))
            ->get(route('admin.hold-numbers'))
            ->assertOk()
            ->assertSeeInOrder([
                '064-111-2222',
                '064-333-4444',
            ]);
    }

    public function test_activate_hold_number_requires_confirm_text(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0645556666',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 1199,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $admin = User::factory()->create([
            'username' => 'hold-admin-confirm',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($admin))
            ->post(route('admin.hold-numbers.activate', $phoneNumber), [
                'confirmation' => 'confirm',
            ])
            ->assertSessionHas('error_message');

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $this->withSession($this->adminSession($admin))
            ->post(route('admin.hold-numbers.activate', $phoneNumber), [
                'confirmation' => 'Confirm',
            ])
            ->assertSessionHas('status_message');

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_ACTIVE,
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
