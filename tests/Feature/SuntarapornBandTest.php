<?php

namespace Tests\Feature;

use App\Models\SuntarapornBooking;
use App\Models\SuntarapornSeat;
use App\Models\User;
use App\Services\SuntarapornSeatMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuntarapornBandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seating_pages_display_the_rendered_total_seat_count(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->assertSame(585, SuntarapornSeatMap::totalSeats());

        $this->get(route('suntaraporn.public'))
            ->assertOk()
            ->assertSee('id="stat-total">585', false)
            ->assertSee('const TOTAL     = 585;', false);

        $this->withSession($this->suntarapornSession($manager))
            ->get(route('suntaraporn.index'))
            ->assertOk()
            ->assertSee('id="stat-total">585', false)
            ->assertSee('const total     = 585;', false);
    }

    public function test_vip_rows_v_and_w_use_vip_zone_with_zero_price(): void
    {
        $staff = User::factory()->create([
            'name' => 'VIP Staff',
            'role' => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        // SeatMap must classify V/W as 'vip'
        $seats = SuntarapornSeatMap::seats();
        $this->assertSame('vip', $seats['V_1']);
        $this->assertSame('vip', $seats['W_1']);
        $this->assertSame('vip', $seats['V_16']);
        $this->assertSame('vip', $seats['W_18']);

        // Booking V/W seats → server stores total_price = 0 (vip price = 0)
        $this->withSession($this->suntarapornSession($staff))
            ->postJson(route('suntaraporn.book'), [
                'seat_keys' => ['V_1', 'W_1'],
                'first_name' => 'วีไอพี',
                'last_name' => 'ทดสอบ',
                'phone' => '0899999999',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('suntaraporn_bookings', [
            'first_name'  => 'วีไอพี',
            'total_price' => 0,
        ]);
    }

    public function test_band_view_renders_v_w_rows_as_vip_zone(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->suntarapornSession($manager))
            ->get(route('suntaraporn.index'))
            ->assertOk()
            ->assertSee('data-key="W_1" data-zone="vip"', false)
            ->assertSee('data-key="V_1" data-zone="vip"', false)
            ->assertDontSee('data-key="W_1" data-zone="yellow"', false)
            ->assertDontSee('data-key="V_1" data-zone="yellow"', false);
    }

    public function test_booking_uses_server_side_seat_zones_for_total_price(): void
    {
        $staff = User::factory()->create([
            'name' => 'Suntaraporn Staff',
            'role' => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        $this->withSession($this->suntarapornSession($staff))
            ->postJson(route('suntaraporn.book'), [
                'seat_keys' => ['A_1', 'BOXA_1'],
                'zones' => ['yellow', 'yellow'],
                'first_name' => 'สมชาย',
                'last_name' => 'ทดสอบ',
                'phone' => '0812345678',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('suntaraporn_bookings', [
            'first_name' => 'สมชาย',
            'last_name' => 'ทดสอบ',
            'booker_name' => 'Suntaraporn Staff',
            'total_price' => 6500, // purple=3500 + green=3000
        ]);

        $this->assertDatabaseHas('suntaraporn_seats', [
            'seat_key' => 'A_1',
            'is_booked' => true,
        ]);
    }

    public function test_booking_rejects_invalid_seat_keys(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        $this->withSession($this->suntarapornSession($staff))
            ->postJson(route('suntaraporn.book'), [
                'seat_keys' => ['NOT_A_SEAT'],
                'first_name' => 'สมชาย',
                'last_name' => 'ทดสอบ',
                'phone' => '0812345678',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_booking_rejects_already_booked_seats(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        SuntarapornSeat::create([
            'seat_key' => 'A_1',
            'is_booked' => true,
            'booked_at' => now(),
        ]);

        $this->withSession($this->suntarapornSession($staff))
            ->postJson(route('suntaraporn.book'), [
                'seat_keys' => ['A_1'],
                'first_name' => 'สมชาย',
                'last_name' => 'ทดสอบ',
                'phone' => '0812345678',
            ])
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_manager_reset_clears_seats_bookings_and_slips(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('suntaraporn-slips/test-slip.png', 'fake image');

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $booking = SuntarapornBooking::create([
            'first_name' => 'สมชาย',
            'last_name' => 'ทดสอบ',
            'phone' => '0812345678',
            'booker_name' => 'Manager',
            'slip_path' => 'suntaraporn-slips/test-slip.png',
            'total_price' => 5000,
        ]);

        SuntarapornSeat::create([
            'seat_key' => 'A_1',
            'is_booked' => true,
            'booked_at' => now(),
            'booking_id' => $booking->id,
        ]);

        $this->withSession($this->suntarapornSession($manager))
            ->postJson(route('suntaraporn.reset'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('suntaraporn_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('suntaraporn_seats', [
            'seat_key' => 'A_1',
            'is_booked' => false,
            'booking_id' => null,
        ]);
        Storage::disk('public')->assertMissing('suntaraporn-slips/test-slip.png');
    }

    public function test_complete_booking_journey(): void
    {
        $staff = User::factory()->create([
            'name'      => 'Staff Member',
            'role'      => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        $session = $this->suntarapornSession($staff);

        // 1. หน้าหลักโหลดได้
        $this->withSession($session)
            ->get(route('suntaraporn.index'))
            ->assertOk()
            ->assertSee('Staff Member');

        // 2. Broadcast select (จำลองคลิกเลือกที่นั่ง)
        $this->withSession($session)
            ->postJson(route('suntaraporn.select'), ['seat_keys' => ['A_1', 'A_2', 'A_3']])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 3. จองที่นั่ง (purple zone: ฿3,500 × 3 = ฿10,500)
        $this->withSession($session)
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => ['A_1', 'A_2', 'A_3'],
                'first_name' => 'สมหมาย',
                'last_name'  => 'จริงใจ',
                'phone'      => '0812345678',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // 4. ตรวจ DB booking สร้างถูกต้อง
        $booking = SuntarapornBooking::where('first_name', 'สมหมาย')->firstOrFail();
        $this->assertSame(10500, $booking->total_price);
        $this->assertSame('Staff Member', $booking->booker_name);

        // 5. ที่นั่งทั้ง 3 ถูก mark booked และผูกกับ booking เดียวกัน
        foreach (['A_1', 'A_2', 'A_3'] as $key) {
            $this->assertDatabaseHas('suntaraporn_seats', [
                'seat_key'   => $key,
                'is_booked'  => true,
                'booking_id' => $booking->id,
            ]);
        }

        // 6. Booking detail popup ส่งข้อมูลครบถ้วน
        $resp = $this->withSession($session)
            ->getJson(route('suntaraporn.booking-info', 'A_2'))
            ->assertOk()
            ->assertJson([
                'success'     => true,
                'first_name'  => 'สมหมาย',
                'last_name'   => 'จริงใจ',
                'phone'       => '0812345678',
                'total_price' => 10500,
                'booker_name' => 'Staff Member',
            ])
            ->json();

        $this->assertEqualsCanonicalizing(['A_1', 'A_2', 'A_3'], $resp['all_seats']);
        $this->assertNotNull($resp['booked_at']);

        // 7. จองที่นั่งที่จองแล้วซ้ำ → 409
        $this->withSession($session)
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => ['A_1'],
                'first_name' => 'คนอื่น',
                'last_name'  => 'ซ้ำ',
                'phone'      => '0899999999',
            ])
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        // 8. รายการจองแสดงชื่อลูกค้า
        $this->withSession($session)
            ->get(route('suntaraporn.bookings'))
            ->assertOk()
            ->assertSee('สมหมาย');

        // 9. Deselect (จำลองปิด tab หรือ deselect)
        $this->withSession($session)
            ->postJson(route('suntaraporn.deselect'), ['seat_keys' => ['A_1', 'A_2', 'A_3']])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_full_house_then_reject_overflow(): void
    {
        $staff = User::factory()->create([
            'role'      => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        $session  = $this->suntarapornSession($staff);
        $allSeats = array_keys(SuntarapornSeatMap::seats());
        $total    = count($allSeats);

        // จองทีละ 20 ที่นั่งจนหมด
        $batches = array_chunk($allSeats, 20);
        foreach ($batches as $i => $batch) {
            $this->withSession($session)
                ->postJson(route('suntaraporn.book'), [
                    'seat_keys'  => $batch,
                    'first_name' => 'ลูกค้า',
                    'last_name'  => 'กลุ่ม' . ($i + 1),
                    'phone'      => '08' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        }

        // ที่นั่งเต็มทั้งหมด
        $this->assertSame($total, SuntarapornSeat::where('is_booked', true)->count());

        // ยอดรวม booking = จำนวน batch
        $this->assertSame(count($batches), SuntarapornBooking::count());

        // พยายามจองเพิ่ม (seat ที่จองแล้ว) → 409
        $this->withSession($session)
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => [$allSeats[0]],
                'first_name' => 'เกิน',
                'last_name'  => 'ขีด',
                'phone'      => '0800000000',
            ])
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        // Stats: booked count ตรงกับ totalSeats
        $this->assertSame($total, SuntarapornSeatMap::totalSeats());
    }

    public function test_cancel_booking_frees_seats_for_rebook(): void
    {
        $manager = User::factory()->create([
            'name'      => 'Manager',
            'role'      => User::ROLE_MANAGER,
            'is_active' => true,
        ]);
        $staff = User::factory()->create([
            'role'      => User::ROLE_SUNTARAPORN,
            'is_active' => true,
        ]);

        $mgrSession   = $this->suntarapornSession($manager);
        $staffSession = $this->suntarapornSession($staff);

        // Manager จอง B_1, B_2
        $this->withSession($mgrSession)
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => ['B_1', 'B_2'],
                'first_name' => 'ต้นฉบับ',
                'last_name'  => 'ทดสอบ',
                'phone'      => '0800000001',
            ])
            ->assertOk();

        $booking = SuntarapornBooking::where('first_name', 'ต้นฉบับ')->firstOrFail();

        $this->assertDatabaseHas('suntaraporn_seats', ['seat_key' => 'B_1', 'is_booked' => true]);
        $this->assertDatabaseHas('suntaraporn_seats', ['seat_key' => 'B_2', 'is_booked' => true]);

        // Staff พยายามยกเลิก → 403 (manager only)
        $this->withSession($staffSession)
            ->deleteJson(route('suntaraporn.cancel', $booking->id))
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // Manager ยกเลิก → สำเร็จ
        $this->withSession($mgrSession)
            ->deleteJson(route('suntaraporn.cancel', $booking->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        // Booking ถูกลบ, ที่นั่งคืนว่าง
        $this->assertDatabaseMissing('suntaraporn_bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('suntaraporn_seats', ['seat_key' => 'B_1', 'is_booked' => false, 'booking_id' => null]);
        $this->assertDatabaseHas('suntaraporn_seats', ['seat_key' => 'B_2', 'is_booked' => false, 'booking_id' => null]);

        // Staff จองที่นั่งเดิมได้ใหม่
        $this->withSession($staffSession)
            ->postJson(route('suntaraporn.book'), [
                'seat_keys'  => ['B_1', 'B_2'],
                'first_name' => 'จองใหม่',
                'last_name'  => 'หลังยกเลิก',
                'phone'      => '0800000002',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('suntaraporn_seats', ['seat_key' => 'B_1', 'is_booked' => true]);

        // Booking info สะท้อนเจ้าของใหม่
        $this->withSession($staffSession)
            ->getJson(route('suntaraporn.booking-info', 'B_1'))
            ->assertOk()
            ->assertJson(['first_name' => 'จองใหม่']);
    }

    private function suntarapornSession(User $user): array
    {
        return ['suntaraporn_user_id' => $user->id];
    }
}
