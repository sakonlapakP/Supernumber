<?php

namespace Tests\Feature;

use App\Models\User;
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

    private function likaySession(User $user): array
    {
        return ['likay_user_id' => $user->id];
    }
}
