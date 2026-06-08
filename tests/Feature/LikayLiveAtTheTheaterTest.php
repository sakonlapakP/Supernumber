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
