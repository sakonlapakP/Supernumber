<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LikayBooking;
use App\Models\LikaySeat;
use App\Services\LikaySeatMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikayLiveInTheaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_seating_pages_display_the_rendered_total_seat_count(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->assertSame(586, LikaySeatMap::totalSeats());

        $this->get(route('likay.public'))
            ->assertOk()
            ->assertSee('id="stat-total">586', false)
            ->assertSee('const TOTAL     = 586;', false)
            ->assertSee('data-key="L_32"', false);

        $this->withSession($this->likaySession($manager))
            ->get(route('likay.index'))
            ->assertOk()
            ->assertSee('id="stat-total">586', false)
            ->assertSee('const total     = 586;', false)
            ->assertSee('data-key="L_32"', false);
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

    private function likaySession(User $user): array
    {
        return ['likay_user_id' => $user->id];
    }
}
