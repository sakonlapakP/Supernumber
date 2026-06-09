<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LikayBooking;
use App\Models\LikaySeat;
use App\Models\LikayZone;
use App\Services\LikaySeatMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikayLiveAtTheTheaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_seating_pages_display_the_rendered_total_seat_count(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->assertSame(585, LikaySeatMap::totalSeats());

        $this->get(route('likay.public'))
            ->assertOk()
            ->assertSee('id="stat-total">585', false)
            ->assertSee('const TOTAL     = 585;', false)
            ->assertDontSee('data-key="L_32"', false);

        $this->withSession($this->likaySession($manager))
            ->get(route('likay.index'))
            ->assertOk()
            ->assertSee('id="stat-total">585', false)
            ->assertSee('const total     = 585;', false)
            ->assertDontSee('data-key="L_32"', false);
    }

    public function test_purple_zone_is_booked_for_king_power_by_migration(): void
    {
        $purpleSeats = array_keys(array_filter(
            LikaySeatMap::seats(),
            fn (string $zone): bool => $zone === 'purple'
        ));

        $this->assertCount(81, $purpleSeats);

        $booking = LikayBooking::where('first_name', 'king power')
            ->where('last_name', '-')
            ->where('phone', '0646323915')
            ->firstOrFail();

        $this->assertSame(405000, $booking->total_price);
        $this->assertNull($booking->slip_path);
        $this->assertSame(81, LikaySeat::whereIn('seat_key', $purpleSeats)
            ->where('is_booked', true)
            ->where('booking_id', $booking->id)
            ->count());
    }

    // ── Sponsor Seats ─────────────────────────────────────────────

    public function test_mark_sponsor_creates_zero_price_booking_and_blocks_rebooking(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_LIKAY, 'is_active' => true]);
        $session = $this->likaySession($staff);
        [$s1, $s2] = $this->freeSeats(2);

        $this->withSession($session)
            ->postJson(route('likay.sponsor.mark'), ['seat_keys' => [$s1, $s2], 'sponsor_name' => 'King Power'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('likay_bookings', [
            'first_name'  => 'King Power',
            'total_price' => 0,
            'is_sponsor'  => true,
        ]);
        foreach ([$s1, $s2] as $key) {
            $this->assertDatabaseHas('likay_seats', ['seat_key' => $key, 'is_booked' => true]);
        }

        // ลูกค้าจองทับไม่ได้ → 409
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys' => [$s1], 'first_name' => 'ลูกค้า', 'last_name' => 'ทดสอบ', 'phone' => '0812345678',
            ])
            ->assertStatus(409);
    }

    public function test_sponsor_seat_is_sold_to_public_but_flagged_for_admin(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_LIKAY, 'is_active' => true]);
        $session = $this->likaySession($staff);
        [$s1] = $this->freeSeats(1);

        $this->withSession($session)
            ->postJson(route('likay.sponsor.mark'), ['seat_keys' => [$s1], 'sponsor_name' => 'Sponsor X'])
            ->assertOk();

        // public เห็นเป็น "ขายแล้ว"
        $this->assertContains($s1, $this->getJson(route('likay.live-state'))->json('booked'));

        // ฝั่งแอดมิน booking-info บอกว่าเป็น sponsor + ฿0
        $this->withSession($session)
            ->getJson(route('likay.booking-info', $s1))
            ->assertOk()
            ->assertJson(['success' => true, 'is_sponsor' => true, 'total_price' => 0, 'first_name' => 'Sponsor X']);
    }

    public function test_sponsor_seat_is_excluded_from_booked_revenue(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);
        $session = $this->likaySession($manager);

        // เลือกที่นั่งโซนที่มีราคา > 0 (ไม่ใช่ purple ที่ migration จองไว้แล้ว)
        $prices = LikayZone::pluck('price', 'slug')->all();
        $pricedSeat = null;
        foreach (LikaySeatMap::seats() as $key => $zone) {
            if ($zone !== 'purple' && ($prices[$zone] ?? 0) > 0) { $pricedSeat = $key; break; }
        }
        $this->assertNotNull($pricedSeat);

        $this->withSession($session)
            ->postJson(route('likay.sponsor.mark'), ['seat_keys' => [$pricedSeat]])
            ->assertOk();

        // รายได้ "ขายแล้ว" ต้องยังเท่ากับ purple (฿405,000) — ที่นั่ง sponsor ฿0 ไม่ถูกบวกเพิ่ม
        $this->withSession($session)
            ->get(route('likay.index'))
            ->assertOk()
            ->assertSee('id="stat-sold-revenue" style="color:#2e7d32;font-size:16px;">฿405,000', false);
    }

    public function test_unmark_sponsor_frees_whole_group_and_allows_rebooking(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_LIKAY, 'is_active' => true]);
        $session = $this->likaySession($staff);
        [$s1, $s2] = $this->freeSeats(2);

        $this->withSession($session)
            ->postJson(route('likay.sponsor.mark'), ['seat_keys' => [$s1, $s2], 'sponsor_name' => 'Sp'])
            ->assertOk();

        // ปลดด้วยที่นั่งใดที่นั่งหนึ่ง → ปล่อยทั้งกลุ่ม
        $this->withSession($session)
            ->postJson(route('likay.sponsor.unmark'), ['seat_keys' => [$s1]])
            ->assertOk()
            ->assertJson(['success' => true]);

        foreach ([$s1, $s2] as $key) {
            $this->assertDatabaseHas('likay_seats', ['seat_key' => $key, 'is_booked' => false, 'booking_id' => null]);
        }
        $this->assertDatabaseMissing('likay_bookings', ['is_sponsor' => true]);

        // จองที่นั่งเดิมได้ใหม่
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys' => [$s1], 'first_name' => 'ใหม่', 'last_name' => 'ทดสอบ', 'phone' => '0812345678',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_mark_sponsor_rejects_already_booked_seat(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_LIKAY, 'is_active' => true]);
        $session = $this->likaySession($staff);
        [$s1] = $this->freeSeats(1);

        $this->withSession($session)->postJson(route('likay.book'), [
            'seat_keys' => [$s1], 'first_name' => 'ลูกค้า', 'last_name' => 'ทดสอบ', 'phone' => '0812345678',
        ])->assertOk();

        $this->withSession($session)
            ->postJson(route('likay.sponsor.mark'), ['seat_keys' => [$s1]])
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_sponsor_endpoints_require_auth(): void
    {
        [$s1] = $this->freeSeats(1);
        $this->postJson(route('likay.sponsor.mark'), ['seat_keys' => [$s1]])->assertStatus(401);
        $this->postJson(route('likay.sponsor.unmark'), ['seat_keys' => [$s1]])->assertStatus(401);
    }

    public function test_login_flow(): void
    {
        $user = User::factory()->create([
            'username' => 'likay_staff',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_LIKAY,
            'is_active' => true,
        ]);

        // 1. หน้าล็อกอินโหลดได้
        $this->get(route('likay.login'))
            ->assertOk();

        // 2. ล็อกอินรหัสผ่านผิด → กลับมาพร้อม error
        $this->post(route('likay.login'), [
            'username' => 'likay_staff',
            'password' => 'wrong_password',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('username');

        // 3. ล็อกอินถูกต้อง → ไปหน้าหลัก
        $this->post(route('likay.login'), [
            'username' => 'likay_staff',
            'password' => 'password123',
        ])
        ->assertRedirect(route('likay.index'))
        ->assertSessionHas('likay_user_id', $user->id);

        // 4. ล็อกเอาต์ → ล้าง session
        $this->withSession($this->likaySession($user))
            ->post(route('likay.logout'))
            ->assertRedirect(route('likay.login'))
            ->assertSessionMissing('likay_user_id');
    }

    public function test_complete_booking_journey(): void
    {
        $staff = User::factory()->create([
            'name'      => 'Likay Staff Member',
            'role'      => User::ROLE_LIKAY,
            'is_active' => true,
        ]);

        $session = $this->likaySession($staff);
        [$s1, $s2, $s3] = $this->freeSeats(3);

        // 1. หน้าหลักโหลดได้
        $this->withSession($session)
            ->get(route('likay.index'))
            ->assertOk()
            ->assertSee('Likay Staff Member');

        // 2. Broadcast select
        $this->withSession($session)
            ->postJson(route('likay.select'), ['seat_keys' => [$s1, $s2, $s3]])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 3. จองที่นั่ง
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys'  => [$s1, $s2, $s3],
                'first_name' => 'กิตติ',
                'last_name'  => 'จริงใจ',
                'phone'      => '0812345678',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 4. ตรวจสอบ DB
        $booking = LikayBooking::where('first_name', 'กิตติ')->firstOrFail();
        $this->assertSame('Likay Staff Member', $booking->booker_name);

        foreach ([$s1, $s2, $s3] as $key) {
            $this->assertDatabaseHas('likay_seats', [
                'seat_key'   => $key,
                'is_booked'  => true,
                'booking_id' => $booking->id,
            ]);
        }

        // 5. Booking info popup
        $resp = $this->withSession($session)
            ->getJson(route('likay.booking-info', $s2))
            ->assertOk()
            ->assertJson([
                'success'     => true,
                'first_name'  => 'กิตติ',
                'last_name'   => 'จริงใจ',
                'phone'       => '0812345678',
                'booker_name' => 'Likay Staff Member',
            ])
            ->json();

        $this->assertEqualsCanonicalizing([$s1, $s2, $s3], $resp['all_seats']);

        // 6. จองซ้ำ → 409
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys'  => [$s1],
                'first_name' => 'คนอื่น',
                'last_name'  => 'ซ้ำ',
                'phone'      => '0899999999',
            ])
            ->assertStatus(409);

        // 7. รายการจองแสดงชื่อลูกค้า
        $this->withSession($session)
            ->get(route('likay.bookings'))
            ->assertOk()
            ->assertSee('กิตติ');

        // 8. Deselect
        $this->withSession($session)
            ->postJson(route('likay.deselect'), ['seat_keys' => [$s1, $s2, $s3]])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_cancel_booking_frees_seats(): void
    {
        $manager = User::factory()->create([
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role'      => User::ROLE_LIKAY,
            'is_active' => true,
        ]);

        $mgrSession   = $this->likaySession($manager);
        $staffSession = $this->likaySession($staff);
        [$s1, $s2] = $this->freeSeats(2);

        // จองที่นั่ง
        $this->withSession($mgrSession)
            ->postJson(route('likay.book'), [
                'seat_keys'  => [$s1, $s2],
                'first_name' => 'กานต์',
                'last_name'  => 'ทดสอบ',
                'phone'      => '0800000001',
            ])
            ->assertOk();

        $booking = LikayBooking::where('first_name', 'กานต์')->firstOrFail();

        // Staff พยายามยกเลิก → 403
        $this->withSession($staffSession)
            ->deleteJson(route('likay.cancel', $booking->id))
            ->assertStatus(403);

        // Manager ยกเลิก → สำเร็จ
        $this->withSession($mgrSession)
            ->deleteJson(route('likay.cancel', $booking->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        // ที่นั่งกลับว่าง
        $this->assertDatabaseMissing('likay_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('likay_seats', ['seat_key' => $s1, 'is_booked' => false, 'booking_id' => null]);
        $this->assertDatabaseHas('likay_seats', ['seat_key' => $s2, 'is_booked' => false, 'booking_id' => null]);
    }

    public function test_manager_reset_clears_seats_bookings_and_slips(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('likay-slips/test-slip.png', 'fake image');

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $booking = LikayBooking::create([
            'first_name' => 'สมชาย',
            'last_name' => 'ลิเก',
            'phone' => '0812345678',
            'booker_name' => 'Manager',
            'slip_path' => 'likay-slips/test-slip.png',
            'total_price' => 5000,
        ]);

        [$s1] = $this->freeSeats(1);
        LikaySeat::create([
            'seat_key' => $s1,
            'is_booked' => true,
            'booking_id' => $booking->id,
            'booked_at' => now(),
        ]);

        $this->withSession($this->likaySession($manager))
            ->postJson(route('likay.reset'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('likay_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('likay_seats', [
            'seat_key' => $s1,
            'is_booked' => false,
            'booking_id' => null,
        ]);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('likay-slips/test-slip.png');
    }

    public function test_full_house_then_reject_overflow(): void
    {
        $staff = User::factory()->create([
            'role'      => User::ROLE_LIKAY,
            'is_active' => true,
        ]);

        $session  = $this->likaySession($staff);
        // ดึงที่นั่งทั้งหมดที่ว่างอยู่ (ไม่นับที่จองไปแล้วจาก migration เช่น purple)
        $freeSeats = array_keys(array_filter(LikaySeatMap::seats(), function($zone) {
            return $zone !== 'purple';
        }));
        
        // จองที่นั่งที่เหลือจนหมด
        $batches = array_chunk($freeSeats, 20);
        foreach ($batches as $i => $batch) {
            $this->withSession($session)
                ->postJson(route('likay.book'), [
                    'seat_keys'  => $batch,
                    'first_name' => 'ลูกค้า',
                    'last_name'  => 'กลุ่ม' . ($i + 1),
                    'phone'      => '08' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        }

        // จำนวนที่นั่งจองทั้งหมดรวม purple ต้องเท่ากับ 585
        $this->assertSame(585, LikaySeat::where('is_booked', true)->count());

        // พยายามจองเพิ่ม → 409
        $this->withSession($session)
            ->postJson(route('likay.book'), [
                'seat_keys'  => [$freeSeats[0]],
                'first_name' => 'เกิน',
                'last_name'  => 'ขีด',
                'phone'      => '0800000000',
            ])
            ->assertStatus(409);
    }

    public function test_booking_list_search_matches_seat_key(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_LIKAY, 'is_active' => true]);
        $session = $this->likaySession($staff);
        [$s1, $s2] = $this->freeSeats(2);

        $this->withSession($session)->postJson(route('likay.book'), [
            'seat_keys' => [$s1], 'first_name' => 'มานี', 'last_name' => 'ใจดี', 'phone' => '0811111111',
        ])->assertOk();

        $this->withSession($session)->postJson(route('likay.book'), [
            'seat_keys' => [$s2], 'first_name' => 'ปิติ', 'last_name' => 'รักเรียน', 'phone' => '0822222222',
        ])->assertOk();

        // ค้นด้วยรหัสที่นั่ง → เจอเฉพาะลูกค้าที่ถือที่นั่งนั้น
        $this->withSession($session)
            ->get(route('likay.bookings', ['search' => $s1]))
            ->assertOk()
            ->assertSee('มานี')
            ->assertDontSee('ปิติ');
    }

    /** ที่นั่งว่าง (ไม่ใช่ purple ที่ migration king power จองไว้) */
    private function freeSeats(int $n): array
    {
        $free = array_keys(array_filter(LikaySeatMap::seats(), fn (string $zone) => $zone !== 'purple'));
        return array_slice($free, 0, $n);
    }

    private function likaySession(User $user): array
    {
        return ['likay_user_id' => $user->id];
    }
}
