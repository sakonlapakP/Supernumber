<?php

namespace Tests\Feature;

use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lottery_checker_validations(): void
    {
        $response = $this->postJson(route('lottery.check'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['number', 'lottery_result_id']);

        $response = $this->postJson(route('lottery.check'), [
            'number' => '12345',
            'lottery_result_id' => 9999
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['number', 'lottery_result_id']);

        $response = $this->postJson(route('lottery.check'), [
            'number' => '12345a',
            'lottery_result_id' => 9999
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['number', 'lottery_result_id']);
    }

    public function test_lottery_checker_winning_scenarios(): void
    {
        $result = LotteryResult::create([
            'draw_date' => '2026-06-01',
            'is_complete' => true,
        ]);

        $result->prizes()->create([
            'position' => 0,
            'prize_name' => 'รางวัลที่ 1',
            'prize_number' => '173770',
        ]);

        $result->prizes()->create([
            'position' => 1,
            'prize_name' => 'เลขหน้า 3 ตัว',
            'prize_number' => '415',
        ]);

        $result->prizes()->create([
            'position' => 2,
            'prize_name' => 'เลขหน้า 3 ตัว',
            'prize_number' => '848',
        ]);

        $result->prizes()->create([
            'position' => 3,
            'prize_name' => 'เลขท้าย 3 ตัว',
            'prize_number' => '410',
        ]);

        $result->prizes()->create([
            'position' => 4,
            'prize_name' => 'เลขท้าย 3 ตัว',
            'prize_number' => '938',
        ]);

        $result->prizes()->create([
            'position' => 5,
            'prize_name' => 'เลขท้าย 2 ตัว',
            'prize_number' => '95',
        ]);

        // 1. Test 1st prize win
        $response = $this->postJson(route('lottery.check'), [
            'number' => '173770',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', true);
        $response->assertJsonFragment(['name' => 'รางวัลที่ 1']);

        // 2. Test near 1st prize win
        $response = $this->postJson(route('lottery.check'), [
            'number' => '173769',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', true);
        $response->assertJsonFragment(['name' => 'รางวัลข้างเคียงรางวัลที่ 1']);

        // 3. Test front 3-digit win
        $response = $this->postJson(route('lottery.check'), [
            'number' => '415999',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', true);
        $response->assertJsonFragment(['name' => 'รางวัลเลขหน้า 3 ตัว']);

        // 4. Test back 3-digit win
        $response = $this->postJson(route('lottery.check'), [
            'number' => '111410',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', true);
        $response->assertJsonFragment(['name' => 'รางวัลเลขท้าย 3 ตัว']);

        // 5. Test back 2-digit win
        $response = $this->postJson(route('lottery.check'), [
            'number' => '111195',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', true);
        $response->assertJsonFragment(['name' => 'รางวัลเลขท้าย 2 ตัว']);

        // 6. Test multiple wins (e.g. front 3-digit AND back 2-digit)
        $response = $this->postJson(route('lottery.check'), [
            'number' => '415195',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', true);
        $this->assertCount(2, $response->json('results'));

        // 7. Test no win
        $response = $this->postJson(route('lottery.check'), [
            'number' => '123456',
            'lottery_result_id' => $result->id
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('won', false);
        $this->assertCount(0, $response->json('results'));
    }
}
