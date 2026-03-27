<?php

namespace Tests\Feature;

use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchLatestLotteryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_and_saves_lottery_result_and_prizes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'date' => '01/04/2026',
                'data' => [
                    ['name' => 'รางวัลที่ 1', 'number' => '123456'],
                    ['name' => 'เลขหน้า 3 ตัว', 'number' => '123'],
                    ['name' => 'เลขหน้า 3 ตัว', 'number' => '456'],
                    ['name' => 'เลขท้าย 3 ตัว', 'number' => '789'],
                    ['name' => 'เลขท้าย 3 ตัว', 'number' => '012'],
                    ['name' => 'เลขท้าย 2 ตัว', 'number' => '12'],
                ],
            ], 200),
        ]);

        $this->artisan('lottery:fetch-latest')
            ->assertExitCode(0);

        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-01 00:00:00',
            'source_draw_date' => '2026-04-01 00:00:00',
            'is_complete' => 1,
        ]);

        $result = LotteryResult::query()->whereDate('draw_date', '2026-04-01')->firstOrFail();

        $this->assertSame(6, $result->prizes()->count());
        $this->assertDatabaseHas('lottery_result_prizes', [
            'lottery_result_id' => $result->id,
            'prize_name' => 'รางวัลที่ 1',
            'prize_number' => '123456',
        ]);
    }

    public function test_it_skips_retry_calls_when_result_is_already_complete(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 10, 0, 'Asia/Bangkok'));

        LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'is_complete' => true,
            'fetched_at' => now(),
        ]);

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([], 200),
        ]);

        $this->artisan('lottery:fetch-latest')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
