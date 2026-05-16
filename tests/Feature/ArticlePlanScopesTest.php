<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticlePlan;
use App\Models\FacebookImportedPost;
use App\Models\User;
use App\Services\FacebookContentRefreshService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePlanScopesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function plan(array $attrs = []): ArticlePlan
    {
        return ArticlePlan::query()->create(array_merge([
            'publish_date' => now()->addDays(5)->format('Y-m-d'),
            'publish_time' => '09:00',
            'type' => 'Evergreen',
            'topic' => 'test topic',
            'is_lottery' => false,
            'status' => 'todo',
        ], $attrs));
    }

    private function article(array $attrs = []): Article
    {
        static $counter = 0;
        $counter++;

        return Article::query()->create(array_merge([
            'title' => "Test Article {$counter}",
            'slug' => "test-article-{$counter}",
            'excerpt' => 'excerpt',
            'content' => '<p>content</p>',
            'is_published' => true,
            'is_auto_post' => false,
            'is_line_broadcasted' => false,
            'published_at' => now(),
        ], $attrs));
    }

    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function test_upcoming_scope_returns_plans_within_date_window(): void
    {
        Carbon::setTestNow('2026-06-01');

        $included = $this->plan(['publish_date' => '2026-06-15']);
        $past = $this->plan(['publish_date' => '2025-12-31']);
        $tooFar = $this->plan(['publish_date' => '2026-09-01']);

        $results = ArticlePlan::query()->upcoming(30)->pluck('id');

        $this->assertTrue($results->contains($included->id));
        $this->assertFalse($results->contains($past->id));
        $this->assertFalse($results->contains($tooFar->id));

        Carbon::setTestNow();
    }

    public function test_overdue_scope_excludes_done_and_cancelled(): void
    {
        Carbon::setTestNow('2026-06-01');

        $overdue = $this->plan(['publish_date' => '2026-05-01', 'status' => 'todo']);
        $done = $this->plan(['publish_date' => '2026-05-01', 'status' => 'done']);
        $cancelled = $this->plan(['publish_date' => '2026-05-01', 'status' => 'cancelled']);

        $results = ArticlePlan::query()->overdue()->pluck('id');

        $this->assertTrue($results->contains($overdue->id));
        $this->assertFalse($results->contains($done->id));
        $this->assertFalse($results->contains($cancelled->id));

        Carbon::setTestNow();
    }

    public function test_for_month_scope_returns_only_that_months_plans(): void
    {
        $june = $this->plan(['publish_date' => '2026-06-15']);
        $may = $this->plan(['publish_date' => '2026-05-31']);
        $july = $this->plan(['publish_date' => '2026-07-01']);

        $results = ArticlePlan::query()->forMonth(Carbon::parse('2026-06-01'))->pluck('id');

        $this->assertTrue($results->contains($june->id));
        $this->assertFalse($results->contains($may->id));
        $this->assertFalse($results->contains($july->id));
    }

    public function test_for_week_scope_returns_only_that_weeks_plans(): void
    {
        // ISO week 24 of 2026: Mon 8 June – Sun 14 June
        $inWeek = $this->plan(['publish_date' => '2026-06-10']);
        $before = $this->plan(['publish_date' => '2026-06-07']);
        $after = $this->plan(['publish_date' => '2026-06-15']);

        $date = Carbon::now()->setISODate(2026, 24);
        $results = ArticlePlan::query()->forWeek($date)->pluck('id');

        $this->assertTrue($results->contains($inWeek->id));
        $this->assertFalse($results->contains($before->id));
        $this->assertFalse($results->contains($after->id));
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function test_article_plan_belongs_to_article(): void
    {
        $article = $this->article();
        $plan = $this->plan(['article_id' => $article->id]);

        $this->assertTrue($plan->article->is($article));
    }

    public function test_article_has_one_plan(): void
    {
        $article = $this->article();
        $plan = $this->plan(['article_id' => $article->id]);

        $this->assertTrue($article->plan->is($plan));
    }

    public function test_article_plan_belongs_to_assigned_user(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan(['assigned_to' => $user->id]);

        $this->assertTrue($plan->assignedUser->is($user));
    }

    // ─── getCoverImageUrl ─────────────────────────────────────────────────────

    public function test_get_cover_image_url_returns_null_when_no_cover(): void
    {
        $article = $this->article(['cover_image_path' => null]);

        $this->assertNull($article->getCoverImageUrl());
    }

    public function test_get_cover_image_url_returns_asset_url(): void
    {
        $article = $this->article(['cover_image_path' => 'articles/2026/test/cover.jpg']);

        $url = $article->getCoverImageUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('articles/2026/test/cover.jpg', $url);
    }

    // ─── Date range queries (no year-based breaks) ────────────────────────────

    public function test_for_month_scope_works_across_year_boundary(): void
    {
        $dec = $this->plan(['publish_date' => '2025-12-15']);
        $jan = $this->plan(['publish_date' => '2026-01-15']);

        $decResults = ArticlePlan::query()->forMonth(Carbon::parse('2025-12-01'))->pluck('id');
        $janResults = ArticlePlan::query()->forMonth(Carbon::parse('2026-01-01'))->pluck('id');

        $this->assertTrue($decResults->contains($dec->id));
        $this->assertFalse($decResults->contains($jan->id));
        $this->assertTrue($janResults->contains($jan->id));
        $this->assertFalse($janResults->contains($dec->id));
    }

    // ─── Content Refresh updates article_id on plans ─────────────────────────

    public function test_refresh_sets_article_id_and_refresh_status_on_plans(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);

        $plan = ArticlePlan::query()->create([
            'publish_date' => '2026-01-01',
            'publish_time' => '09:00',
            'type' => 'หวย/สำคัญ',
            'topic' => 'วันขึ้นปีใหม่: เปิดดวง',
            'is_lottery' => true,
        ]);

        FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_article_id_test',
            'source_node_type' => 'page',
            'message' => 'สวัสดีปีใหม่ 2566 เลขมงคล',
            'facebook_created_time' => '2023-01-01 09:00:00',
            'last_synced_at' => now(),
        ]);

        app(FacebookContentRefreshService::class)->refresh($manager->id);

        $plan->refresh();
        $article = Article::query()->where('slug', 'new-year-lucky-numbers')->first();

        $this->assertNotNull($article);
        $this->assertSame($article->id, $plan->article_id);
        $this->assertSame('created', $plan->refresh_status);
        $this->assertNotNull($plan->last_refreshed_at);
    }

    public function test_refresh_sets_refresh_status_refreshed_on_second_run(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);

        ArticlePlan::query()->create([
            'publish_date' => '2026-01-01',
            'publish_time' => '09:00',
            'type' => 'หวย/สำคัญ',
            'topic' => 'วันขึ้นปีใหม่: เปิดดวง',
            'is_lottery' => true,
        ]);

        // First run — creates the article
        FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_refresh_run1',
            'source_node_type' => 'page',
            'message' => 'สวัสดีปีใหม่ 2566 รอบแรก',
            'facebook_created_time' => '2023-01-01 09:00:00',
            'last_synced_at' => now(),
        ]);
        app(FacebookContentRefreshService::class)->refresh($manager->id);

        // Second run — updates the existing article
        FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_refresh_run2',
            'source_node_type' => 'page',
            'message' => 'สวัสดีปีใหม่ 2567 รอบสอง',
            'facebook_created_time' => '2024-01-01 09:00:00',
            'last_synced_at' => now(),
        ]);
        app(FacebookContentRefreshService::class)->refresh($manager->id);

        $plan = ArticlePlan::query()->first();
        $this->assertSame('refreshed', $plan->refresh_status);
    }

    // ─── PlanApiController ────────────────────────────────────────────────────

    public function test_api_month_returns_plans_for_given_month(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);

        $this->plan(['publish_date' => '2026-06-15']);
        $this->plan(['publish_date' => '2026-05-31']); // not June

        $this->withSession($this->adminSession($manager))
            ->getJson(route('admin.api.plans.month', ['year' => 2026, 'month' => 6]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_api_upcoming_returns_plans_within_30_days(): void
    {
        Carbon::setTestNow('2026-06-01');

        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);

        $this->plan(['publish_date' => '2026-06-10']);
        $this->plan(['publish_date' => '2026-09-01']); // outside window

        $this->withSession($this->adminSession($manager))
            ->getJson(route('admin.api.plans.upcoming'))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Carbon::setTestNow();
    }

    public function test_api_update_status_changes_plan_status(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);
        $plan = $this->plan(['status' => 'todo']);

        $this->withSession($this->adminSession($manager))
            ->patchJson(route('admin.api.plans.update-status', $plan), ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');

        $this->assertDatabaseHas('article_plans', ['id' => $plan->id, 'status' => 'done']);
    }

    public function test_api_update_status_clears_blocked_reason_when_unblocked(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER, 'is_active' => true]);
        $plan = $this->plan(['status' => 'blocked', 'blocked_reason' => 'รอข้อมูล']);

        $this->withSession($this->adminSession($manager))
            ->patchJson(route('admin.api.plans.update-status', $plan), ['status' => 'done'])
            ->assertOk();

        $this->assertDatabaseHas('article_plans', ['id' => $plan->id, 'blocked_reason' => null]);
    }
}
