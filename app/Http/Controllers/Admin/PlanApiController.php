<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticlePlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanApiController extends Controller
{
    private const VALID_STATUSES = [
        ArticlePlan::STATUS_TODO,
        ArticlePlan::STATUS_IN_PROGRESS,
        ArticlePlan::STATUS_DONE,
        ArticlePlan::STATUS_BLOCKED,
        ArticlePlan::STATUS_CANCELLED,
    ];

    public function forMonth(int $year, int $month): JsonResponse
    {
        if (! session('admin_user_id')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $date = Carbon::createFromDate($year, $month, 1);

        $plans = ArticlePlan::query()
            ->forMonth($date)
            ->with(['article', 'assignedUser'])
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get();

        $readyDates = $this->buildReadyDates($plans);

        return response()->json(['data' => $plans->map(fn (ArticlePlan $p) => $this->formatPlan($p, $readyDates))]);
    }

    public function forWeek(int $year, int $week): JsonResponse
    {
        if (! session('admin_user_id')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $date = Carbon::now()->setISODate($year, $week);

        $plans = ArticlePlan::query()
            ->forWeek($date)
            ->with(['article', 'assignedUser'])
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get();

        $readyDates = $this->buildReadyDates($plans);

        return response()->json(['data' => $plans->map(fn (ArticlePlan $p) => $this->formatPlan($p, $readyDates))]);
    }

    public function upcoming(): JsonResponse
    {
        if (! session('admin_user_id')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $plans = ArticlePlan::query()
            ->upcoming(30)
            ->with(['article', 'assignedUser'])
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get();

        $readyDates = $this->buildReadyDates($plans);

        return response()->json(['data' => $plans->map(fn (ArticlePlan $p) => $this->formatPlan($p, $readyDates))]);
    }

    public function updateStatus(Request $request, ArticlePlan $plan): JsonResponse
    {
        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', self::VALID_STATUSES),
            'blocked_reason' => 'nullable|string',
        ]);

        if ($validated['status'] !== ArticlePlan::STATUS_BLOCKED) {
            $validated['blocked_reason'] = null;
        }

        $plan->update($validated);

        $fresh = $plan->fresh(['article', 'assignedUser']);
        $readyDates = $this->buildReadyDates(collect([$fresh]));

        return response()->json(['data' => $this->formatPlan($fresh, $readyDates)]);
    }

    /**
     * Build a set of publish-date strings that already have a matching published article.
     * Checks both by published_at date AND by lottery slug pattern for lottery plans.
     *
     * @param  Collection<int, ArticlePlan>  $plans
     * @return array<string, true>  keyed by 'Y-m-d'
     */
    private function buildReadyDates(Collection $plans): array
    {
        $dates = $plans->map(fn ($p) => $p->publish_date?->toDateString())->filter()->unique()->values()->all();

        if (empty($dates)) {
            return [];
        }

        // Match by DATE(published_at) — app timezone is Asia/Bangkok so no CONVERT_TZ needed
        $ready = Article::query()
            ->whereIn(DB::raw('DATE(published_at)'), $dates)
            ->whereNotNull('published_at')
            ->selectRaw('DATE(published_at) AS pub_date')
            ->pluck('pub_date')
            ->flip()
            ->all();

        // Also match lottery plans by slug pattern
        $lotteryPlans = $plans->filter(fn ($p) => $p->is_lottery && $p->publish_date !== null);
        if ($lotteryPlans->isNotEmpty()) {
            $slugToDate = $lotteryPlans->mapWithKeys(function (ArticlePlan $plan): array {
                $isRound1 = (int) $plan->publish_date->format('j') <= 15;
                $slug = 'thai-goverment-lottery-' . $plan->publish_date->format('Ym') . ($isRound1 ? 'first' : 'second');
                return [$slug => $plan->publish_date->toDateString()];
            });

            Article::query()
                ->whereIn('slug', $slugToDate->keys())
                ->pluck('slug')
                ->each(function (string $slug) use ($slugToDate, &$ready): void {
                    $ready[$slugToDate[$slug]] = true;
                });
        }

        return $ready;
    }

    private function formatPlan(ArticlePlan $plan, array $readyDates = []): array
    {
        $dateStr = $plan->publish_date?->toDateString();
        $linkedPublished = $plan->article?->is_published ?? false;
        $isArticleReady = $linkedPublished || ($dateStr !== null && isset($readyDates[$dateStr]));

        return [
            'id' => $plan->id,
            'publish_date' => $dateStr,
            'publish_time' => $plan->publish_time,
            'type' => $plan->type,
            'topic' => $plan->topic,
            'is_lottery' => $plan->is_lottery,
            'status' => $plan->status,
            'is_article_ready' => $isArticleReady,
            'due_date' => $plan->due_date?->toDateString(),
            'blocked_reason' => $plan->blocked_reason,
            'notes' => $plan->notes,
            'refresh_status' => $plan->refresh_status,
            'last_refreshed_at' => $plan->last_refreshed_at?->toIso8601String(),
            'assigned_user' => $plan->assignedUser
                ? ['id' => $plan->assignedUser->id, 'name' => $plan->assignedUser->name]
                : null,
            'article' => $plan->article
                ? [
                    'id' => $plan->article->id,
                    'title' => $plan->article->title,
                    'slug' => $plan->article->slug,
                    'is_published' => $plan->article->is_published,
                    'cover_image_url' => $plan->article->getCoverImageUrl(),
                ]
                : null,
        ];
    }
}
