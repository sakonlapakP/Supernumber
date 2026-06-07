<?php

namespace Tests\Feature;

use App\Models\BookingActivityLog;
use App\Models\LikayBooking;
use App\Models\LikaySeat;
use App\Models\SuntarapornBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creates_a_book_log_with_snapshot(): void
    {
        $staff = User::factory()->create([
            'name'      => 'Staff Member',
            'role'      => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        $this->withSession(['suntaraporn_user_id' => $staff->id])
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => ['A_1', 'A_2'],
                'first_name' => 'สมชาย',
                'last_name'  => 'ใจดี',
                'phone'      => '0812345678',
            ])
            ->assertOk();

        $log = BookingActivityLog::where('system', 'suntaraporn')
            ->where('action', 'book')
            ->firstOrFail();

        $this->assertSame('Staff Member', $log->actor_name);
        $this->assertSame('สมชาย ใจดี', $log->customer_name);
        $this->assertSame('0812345678', $log->phone);
        $this->assertSame('2026-10-31', $log->show_date->format('Y-m-d'));
        $this->assertEqualsCanonicalizing(['A_1', 'A_2'], $log->seat_keys);
        $this->assertNotNull($log->total_price);
    }

    public function test_cancel_log_snapshot_survives_booking_deletion(): void
    {
        $manager = User::factory()->create([
            'name'      => 'Boss',
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);
        $session = ['likay_user_id' => $manager->id];

        // U_1 อยู่โซน yellow (โซน purple ถูกจองโดย migration King Power แล้ว)
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys'  => ['U_1'],
                'first_name' => 'ยกเลิก',
                'last_name'  => 'ทดสอบ',
                'phone'      => '0899999999',
            ])
            ->assertOk();

        $booking = LikayBooking::where('first_name', 'ยกเลิก')->firstOrFail();

        $this->withSession($session)
            ->deleteJson(route('likay.cancel', $booking->id))
            ->assertOk();

        // booking ถูกลบแล้ว แต่ log ยังเก็บ snapshot ไว้
        $this->assertDatabaseMissing('likay_bookings', ['id' => $booking->id]);

        $log = BookingActivityLog::where('system', 'likay')
            ->where('action', 'cancel')
            ->firstOrFail();

        $this->assertSame('Boss', $log->actor_name);
        $this->assertSame('ยกเลิก ทดสอบ', $log->customer_name);
        $this->assertSame(['U_1'], $log->seat_keys);
    }

    public function test_reset_creates_reset_log(): void
    {
        $manager = User::factory()->create([
            'name'      => 'Manager',
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);
        $session = ['likay_user_id' => $manager->id];

        // U_1/U_2 อยู่โซน yellow (purple ถูกจองโดย migration King Power แล้ว)
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys'  => ['U_1', 'U_2'],
                'first_name' => 'ก่อน',
                'last_name'  => 'รีเซ็ต',
                'phone'      => '0800000000',
            ])
            ->assertOk();

        $this->withSession($session)
            ->postJson(route('likay.reset'))
            ->assertOk();

        $log = BookingActivityLog::where('system', 'likay')
            ->where('action', 'reset')
            ->firstOrFail();

        $this->assertSame('Manager', $log->actor_name);
        // reset เก็บ snapshot ที่นั่งที่จองอยู่ทั้งหมด ณ เวลานั้น (รวมที่เราเพิ่งจอง)
        $this->assertContains('U_1', $log->seat_keys);
        $this->assertContains('U_2', $log->seat_keys);
    }

    public function test_search_creates_search_log(): void
    {
        $manager = User::factory()->create([
            'name'      => 'Searcher',
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession(['likay_user_id' => $manager->id])
            ->get(route('likay.bookings', ['search' => 'สมชาย']))
            ->assertOk();

        $log = BookingActivityLog::where('system', 'likay')
            ->where('action', 'search')
            ->firstOrFail();

        $this->assertSame('Searcher', $log->actor_name);
        $this->assertSame('สมชาย', $log->search_query);

        // ค้นหาว่าง ไม่สร้าง log
        $this->withSession(['likay_user_id' => $manager->id])
            ->get(route('likay.bookings'))
            ->assertOk();

        $this->assertSame(1, BookingActivityLog::where('action', 'search')->count());
    }

    public function test_history_page_is_manager_only(): void
    {
        $manager = User::factory()->create([
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role'      => User::ROLE_LIKAY,
            'is_active' => true,
        ]);

        // staff → ถูก redirect ออกจากหน้า history
        $this->withSession(['likay_user_id' => $staff->id])
            ->get(route('likay.history'))
            ->assertRedirect(route('likay.index'));

        // manager → เข้าได้
        $this->withSession(['likay_user_id' => $manager->id])
            ->get(route('likay.history'))
            ->assertOk()
            ->assertSee('ประวัติการทำรายการ');
    }

    public function test_history_paginates_at_50_per_page(): void
    {
        $manager = User::factory()->create([
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        // สร้าง log 60 รายการ → 2 หน้า (50 + 10)
        for ($i = 1; $i <= 60; $i++) {
            BookingActivityLog::record([
                'system'       => 'likay',
                'action'       => 'search',
                'actor_name'   => 'Manager',
                'search_query' => 'q' . $i,
            ]);
        }

        $session = ['likay_user_id' => $manager->id];

        // หน้า 1 มีปุ่มถัดไป + เห็น stat รวม 60
        $this->withSession($session)
            ->get(route('likay.history'))
            ->assertOk()
            ->assertSee('ถัดไป')
            ->assertSee('หน้า 1 / 2', false);

        // หน้า 2 โหลดได้
        $this->withSession($session)
            ->get(route('likay.history', ['page' => 2]))
            ->assertOk()
            ->assertSee('หน้า 2 / 2', false);
    }

    public function test_long_customer_name_is_logged_without_overflow(): void
    {
        $staff = User::factory()->create([
            'name'      => 'Staff',
            'role'      => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        // ชื่อ+สกุลยาวเต็มพิกัด (100 + 100 ตัว) → customer_name = 201 ตัว
        $first = str_repeat('ก', 100);
        $last  = str_repeat('ข', 100);

        $this->withSession(['suntaraporn_user_id' => $staff->id])
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => ['A_1'],
                'first_name' => $first,
                'last_name'  => $last,
                'phone'      => '0812345678',
            ])
            ->assertOk();

        // log ต้องถูกบันทึก (ไม่ถูก drop เพราะ overflow)
        $log = BookingActivityLog::where('action', 'book')->firstOrFail();
        $this->assertSame(201, mb_strlen($log->customer_name));
    }

    public function test_long_search_query_is_truncated_not_dropped(): void
    {
        $manager = User::factory()->create([
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $longQuery = str_repeat('x', 300);

        $this->withSession(['likay_user_id' => $manager->id])
            ->get(route('likay.bookings', ['search' => $longQuery]))
            ->assertOk();

        $log = BookingActivityLog::where('action', 'search')->firstOrFail();
        $this->assertSame(255, mb_strlen($log->search_query));
    }

    public function test_empty_reset_does_not_create_log(): void
    {
        $manager = User::factory()->create([
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        // reset ทั้งที่ไม่มีที่นั่งจอง → ไม่ควรสร้าง log
        $this->withSession(['suntaraporn_user_id' => $manager->id])
            ->postJson(route('suntaraporn.reset'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, BookingActivityLog::where('action', 'reset')->count());
    }

    public function test_history_date_range_filter_excludes_old_entries(): void
    {
        $manager = User::factory()->create([
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        // log เก่า (ปี 2020) + log ใหม่ (วันนี้)
        $old = BookingActivityLog::create([
            'system' => 'likay', 'action' => 'search',
            'actor_name' => 'M', 'search_query' => 'OLD_ENTRY',
        ]);
        $old->created_at = '2020-01-01 10:00:00';
        $old->save();

        BookingActivityLog::record([
            'system' => 'likay', 'action' => 'search',
            'actor_name' => 'M', 'search_query' => 'NEW_ENTRY',
        ]);

        // กรองตั้งแต่วันนี้ → เห็นเฉพาะรายการใหม่
        $this->withSession(['likay_user_id' => $manager->id])
            ->get(route('likay.history', ['from' => now()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('NEW_ENTRY')
            ->assertDontSee('OLD_ENTRY');
    }

    public function test_suntaraporn_history_filters_by_show_date(): void
    {
        $manager = User::factory()->create([
            'name'      => 'M',
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);
        $session = ['suntaraporn_user_id' => $manager->id];

        // จองคนละวัน
        $this->withSession($session)
            ->postJson(route('suntaraporn.book', ['date' => '2026-10-31']), [
                'seat_keys' => ['A_1'], 'first_name' => 'วันแรก', 'last_name' => 'x', 'phone' => '0810000001',
            ])->assertOk();
        $this->withSession($session)
            ->postJson(route('suntaraporn.book', ['date' => '2026-11-01']), [
                'seat_keys' => ['A_1'], 'first_name' => 'วันสอง', 'last_name' => 'x', 'phone' => '0810000002',
            ])->assertOk();

        // history รอบ 31 ต.ค. เห็นเฉพาะ log วันนั้น
        $this->withSession($session)
            ->get(route('suntaraporn.history', ['date' => '2026-10-31']))
            ->assertOk()
            ->assertSee('วันแรก')
            ->assertDontSee('วันสอง');
    }
}
