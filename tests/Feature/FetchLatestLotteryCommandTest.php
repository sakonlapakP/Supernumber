<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FetchLatestLotteryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_and_saves_lottery_result_and_prizes(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
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

    public function test_it_creates_a_new_published_lottery_article_for_the_draw(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('articles', [
            'slug' => 'thai-government-lottery-202604first',
            'title' => 'ตรวจหวยรัฐบาล งวดประจำวันที่ 1 เมษายน 2569 ผลสลากกินแบ่งรัฐบาล',
            'is_published' => 1,
        ]);

        $article = Article::query()
            ->where('slug', 'thai-government-lottery-202604first')
            ->firstOrFail();

        $this->assertStringContainsString('รางวัลที่ 1: <strong>123456</strong>', (string) $article->content);
        $this->assertNotNull($article->published_at);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('ตรวจหวยรัฐบาล งวดประจำวันที่ 1 เมษายน 2569');
    }

    public function test_it_falls_back_to_svg_cover_files_when_png_rendering_is_unavailable(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 0, 0, 'Asia/Bangkok'));
        Storage::fake('public');

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $originalPath = getenv('PATH');
        putenv('PATH=');

        try {
            $this->artisan('lottery:fetch-latest', ['--force' => true])
                ->assertExitCode(0);
        } finally {
            if ($originalPath === false) {
                putenv('PATH');
            } else {
                putenv('PATH='.$originalPath);
            }
        }

        $article = Article::query()
            ->where('slug', 'thai-government-lottery-202604first')
            ->firstOrFail();

        $this->assertNotNull($article->cover_image_path);
        $this->assertTrue(str_ends_with((string) $article->cover_image_path, '.svg'));
        Storage::disk('public')->assertExists((string) $article->cover_image_path);
    }

    public function test_it_keeps_a_holiday_shifted_second_day_draw_in_the_first_round_article_slug(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 16, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('articles', [
            'slug' => 'thai-government-lottery-202604first',
            'title' => 'ตรวจหวยรัฐบาล งวดประจำวันที่ 2 เมษายน 2569 ผลสลากกินแบ่งรัฐบาล',
            'is_published' => 1,
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

    public function test_it_keeps_polling_through_1620_until_a_draw_is_complete(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 20, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-01',
            'is_complete' => 1,
        ]);
    }

    public function test_it_stops_polling_after_1620(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 25, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest')
            ->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertDatabaseMissing('lottery_results', [
            'draw_date' => '2026-04-01',
        ]);
    }

    public function test_it_retries_automatically_on_the_next_day_when_previous_draw_has_no_data(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 15, 45, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-02',
            'source_draw_date' => '2026-04-02',
            'is_complete' => 1,
        ]);
    }

    public function test_retry_day_without_any_payload_still_tracks_the_previous_draw_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 17, 15, 45, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([], 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-16',
            'is_complete' => 0,
        ]);
        $this->assertDatabaseMissing('lottery_results', [
            'draw_date' => '2026-04-17',
        ]);
    }

    public function test_it_does_not_retry_automatically_on_the_next_day_when_previous_draw_has_partial_data(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 2, 15, 45, 0, 'Asia/Bangkok'));

        $result = LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
            'source_draw_date_text' => '01/04/2569',
            'is_complete' => false,
            'fetched_at' => now(),
            'source_payload' => [],
        ]);

        $result->prizes()->create([
            'position' => 0,
            'prize_name' => 'รางวัลที่ 1',
            'prize_number' => '123456',
        ]);

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($this->completePrizePayload(), 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertDatabaseMissing('lottery_results', [
            'draw_date' => '2026-04-02',
        ]);
    }

    public function test_it_does_not_mark_malformed_prize_sets_as_complete(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 0, 0, 'Asia/Bangkok'));

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'date' => '01/04/2569',
                'data' => [
                    ['name' => 'รางวัลที่ 1', 'number' => '123456'],
                    ['name' => 'เลขหน้า 3 ตัว', 'number' => '111'],
                    ['name' => 'เลขหน้า 3 ตัว', 'number' => '222'],
                    ['name' => 'เลขหน้า 3 ตัว', 'number' => '333'],
                    ['name' => 'เลขท้าย 3 ตัว', 'number' => '444'],
                    ['name' => 'เลขท้าย 2 ตัว', 'number' => '12'],
                ],
            ], 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-01',
            'is_complete' => 0,
        ]);
    }

    public function test_it_does_not_replace_a_more_complete_saved_result_with_partial_retry_data(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 1, 16, 20, 0, 'Asia/Bangkok'));

        $result = LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
            'source_draw_date_text' => '01/04/2569',
            'is_complete' => true,
            'fetched_at' => now(),
            'source_payload' => [],
        ]);

        foreach ($this->completePrizePayload() as $index => $prize) {
            $result->prizes()->create([
                'position' => $index,
                'prize_name' => $prize['name'],
                'prize_number' => $prize['number'],
            ]);
        }

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'date' => '01/04/2569',
                'data' => [
                    ['name' => 'รางวัลที่ 1', 'number' => '654321'],
                    ['name' => 'เลขหน้า 3 ตัว', 'number' => '999'],
                    ['name' => 'เลขท้าย 2 ตัว', 'number' => '98'],
                ],
            ], 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        $result->refresh();

        $this->assertTrue($result->is_complete);
        $this->assertSame(6, $result->prizes()->count());
        $this->assertDatabaseHas('lottery_result_prizes', [
            'lottery_result_id' => $result->id,
            'prize_name' => 'รางวัลที่ 1',
            'prize_number' => '123456',
        ]);
        $this->assertDatabaseMissing('lottery_result_prizes', [
            'lottery_result_id' => $result->id,
            'prize_name' => 'รางวัลที่ 1',
            'prize_number' => '654321',
        ]);
    }

    public function test_article_detail_renders_a_lottery_visual_fallback_when_cover_file_is_missing(): void
    {
        $result = LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
            'source_draw_date_text' => '01/04/2569',
            'is_complete' => true,
            'fetched_at' => Carbon::create(2026, 4, 1, 16, 30, 0, 'Asia/Bangkok'),
            'source_payload' => [],
        ]);

        foreach ($this->completePrizePayload() as $index => $prize) {
            $result->prizes()->create([
                'position' => $index,
                'prize_name' => $prize['name'],
                'prize_number' => $prize['number'],
            ]);
        }

        $article = Article::query()->create([
            'title' => 'สลากกินแบ่งรัฐบาล ประจำวันที่ 1 เมษายน 2569',
            'slug' => 'thai-government-lottery-202604first',
            'excerpt' => 'test excerpt',
            'content' => '<p>test content</p>',
            'cover_image_square_path' => 'article/2026/thai-government-lottery-202604first/missing.png',
            'is_published' => true,
            'published_at' => Carbon::create(2026, 4, 1, 16, 30, 0, 'Asia/Bangkok'),
        ]);

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('article-detail__lottery-fallback', false)
            ->assertSee('123456')
            ->assertSee('เลขหน้า 3 ตัว')
            ->assertSee('เลขท้าย 2 ตัว');
    }

    public function test_article_detail_embeds_browser_fallback_markup_when_cover_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('article/2026/thai-government-lottery-202604first/broken.png', 'not-a-real-image');

        $result = LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
            'source_draw_date_text' => '01/04/2569',
            'is_complete' => true,
            'fetched_at' => Carbon::create(2026, 4, 1, 16, 30, 0, 'Asia/Bangkok'),
            'source_payload' => [],
        ]);

        foreach ($this->completePrizePayload() as $index => $prize) {
            $result->prizes()->create([
                'position' => $index,
                'prize_name' => $prize['name'],
                'prize_number' => $prize['number'],
            ]);
        }

        $article = Article::query()->create([
            'title' => 'สลากกินแบ่งรัฐบาล ประจำวันที่ 1 เมษายน 2569',
            'slug' => 'thai-government-lottery-202604first',
            'excerpt' => 'test excerpt',
            'content' => '<p>test content</p>',
            'cover_image_square_path' => 'article/2026/thai-government-lottery-202604first/broken.png',
            'is_published' => true,
            'published_at' => Carbon::create(2026, 4, 1, 16, 30, 0, 'Asia/Bangkok'),
        ]);

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('data-lottery-cover-media', false)
            ->assertSee('data-lottery-cover-fallback', false)
            ->assertSee('onerror=', false);
    }

    private function completePrizePayload(): array
    {
        return [
            'response' => [
                'date' => '01/04/2569',
                'data' => [
                    'first' => ['number' => ['123456']],
                    'last3f' => ['number' => ['123', '456']],
                    'last3b' => ['number' => ['789', '012']],
                    'last2' => ['number' => ['12']],
                ]
            ]
        ];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
