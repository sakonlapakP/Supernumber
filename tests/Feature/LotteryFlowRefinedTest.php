<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\LotteryResult;
use App\Services\LineLotteryNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery\MockInterface;

class LotteryFlowRefinedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Mock external services to prevent real notifications during tests
        $this->mock(LineLotteryNotifier::class, function (MockInterface $mock) {
            $mock->shouldIgnoreMissing();
        });
        
        $this->mock(\App\Services\FacebookPagePoster::class, function (MockInterface $mock) {
            $mock->shouldIgnoreMissing();
        });
    }

    /**
     * Case: Partial Results (ระหว่างหวยทยอยออก)
     * - Article: Draft
     * - Images: None
     * - LINE Admin: None
     */
    public function test_partial_lottery_results_stay_as_draft_without_images_or_notifications(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 30, 0, 'Asia/Bangkok'));

        // Mock LINE Notifier specifically for this test to assert NOT receive
        $this->mock(LineLotteryNotifier::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('notifyAdminArticleReady');
        });

        // Mock API with Partial Data (is_complete: false)
        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'response' => [
                    'date' => '01/05/2569',
                    'data' => [
                        'first' => ['number' => ['123456']],
                    ]
                ]
            ], 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        // Verify Article is Draft
        $this->assertDatabaseHas('articles', [
            'slug' => 'thai-government-lottery-202605first',
            'is_published' => false,
        ]);

        // Verify No Images generated
        $article = Article::where('slug', 'thai-government-lottery-202605first')->first();
        $this->assertNull($article->cover_image_square_path);
        
        $files = Storage::disk('public')->allFiles('articles/2026/thai-government-lottery-202605first');
        $this->assertCount(0, $files);
    }

    /**
     * Case: Just Completed (หวยออกครบ 100%)
     * - Article: Published
     * - Images: Generated
     * - LINE Admin: Sent once
     */
    public function test_completed_lottery_results_publish_article_generate_images_and_notify_admin(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 16, 0, 0, 'Asia/Bangkok'));

        // Mock LINE Notifier specifically for this test
        $this->mock(LineLotteryNotifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('notifyAdminArticleReady')
                ->once()
                ->andReturn(null);
        });

        // Mock API with Complete Data (is_complete: true)
        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'response' => [
                    'date' => '01/05/2569',
                    'data' => [
                        'first' => ['number' => ['123456']],
                        'last3f' => ['number' => ['123', '456']],
                        'last3b' => ['number' => ['789', '012']],
                        'last2' => ['number' => ['12']],
                    ]
                ]
            ], 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        // Verify Article is Published
        $this->assertDatabaseHas('articles', [
            'slug' => 'thai-government-lottery-202605first',
            'is_published' => true,
        ]);

        // Verify Images generated
        $article = Article::where('slug', 'thai-government-lottery-202605first')->first();
        $this->assertNotNull($article->cover_image_square_path);
        Storage::disk('public')->assertExists($article->cover_image_square_path);
    }

    /**
     * Case: Post-Completion Run (รันซ้ำ)
     * - LINE Admin: Not sent again
     */
    public function test_re_running_completed_lottery_does_not_re_send_notifications(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 16, 0, 0, 'Asia/Bangkok'));

        // Create an existing complete result in DB
        $result = LotteryResult::create([
            'draw_date' => '2026-05-01',
            'is_complete' => true,
            'fetched_at' => now(),
        ]);

        // Mock LINE Notifier - should NOT receive any calls
        $this->mock(LineLotteryNotifier::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('notifyAdminArticleReady');
        });

        // Mock API with Complete Data
        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'response' => [
                    'date' => '01/05/2569',
                    'data' => [
                        'first' => ['number' => ['123456']],
                        'last3f' => ['number' => ['123', '456']],
                        'last3b' => ['number' => ['789', '012']],
                        'last2' => ['number' => ['12']],
                    ]
                ]
            ], 200),
        ]);

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);
    }

    /**
     * Case: Duplicate Run File Check
     * - Running multiple times should NOT increase file count
     */
    public function test_running_completed_lottery_multiple_times_replaces_files_instead_of_duplicating(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 16, 0, 0, 'Asia/Bangkok'));

        // Mock API with Complete Data
        $payload = [
            'response' => [
                'date' => '01/05/2569',
                'data' => [
                    'first' => ['number' => ['123456']],
                    'last3f' => ['number' => ['123', '456']],
                    'last3b' => ['number' => ['789', '012']],
                    'last2' => ['number' => ['12']],
                ]
            ]
        ];

        // Run 1st time
        Http::fake(['https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($payload, 200)]);
        $this->artisan('lottery:fetch-latest', ['--force' => true])->assertExitCode(0);
        
        $files1 = Storage::disk('public')->allFiles('articles/2026/thai-government-lottery-202605first');
        $this->assertGreaterThan(0, count($files1));
        $count1 = count($files1);

        // Run 2nd time (simulating a later time)
        Carbon::setTestNow(Carbon::now()->addMinutes(10));
        Http::fake(['https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response($payload, 200)]);
        $this->artisan('lottery:fetch-latest', ['--force' => true])->assertExitCode(0);

        $files2 = Storage::disk('public')->allFiles('articles/2026/thai-government-lottery-202605first');
        $this->assertEquals($count1, count($files2), 'File count should not increase after re-running the command');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
