<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticlePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticlePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $planYear = (int) $request->query('plan_year', now()->year);
        $planYear = max(2026, min(2037, $planYear));

        $plans = ArticlePlan::query()
            ->whereYear('publish_date', $planYear)
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get();

        $readyDates = $this->buildReadyDates($plans);

        $plans->each(function (ArticlePlan $plan) use ($readyDates): void {
            $dateStr = optional($plan->publish_date)->toDateString();
            $plan->is_article_ready = isset($readyDates[$dateStr]);
        });

        return response()->json(['data' => $plans]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ArticlePlan>  $plans
     * @return array<string, true>
     */
    private function buildReadyDates(\Illuminate\Support\Collection $plans): array
    {
        $dates = $plans->map(fn ($p) => optional($p->publish_date)->toDateString())->filter()->unique()->values()->all();

        if (empty($dates)) {
            return [];
        }

        $ready = Article::query()
            ->whereIn(DB::raw('DATE(published_at)'), $dates)
            ->whereNotNull('published_at')
            ->selectRaw('DATE(published_at) AS pub_date')
            ->pluck('pub_date')
            ->flip()
            ->all();

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

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $plan = ArticlePlan::create($validated);

        return response()->json(['data' => $plan], 201);
    }

    public function update(Request $request, ArticlePlan $articlePlan): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $articlePlan->update($validated);

        return response()->json(['data' => $articlePlan]);
    }

    public function updateStatus(Request $request, ArticlePlan $articlePlan): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:todo,in_progress,done,blocked,cancelled'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'blocked_reason' => ['sometimes', 'nullable', 'string'],
        ]);

        $articlePlan->update($validated);

        return response()->json(['data' => $articlePlan]);
    }

    public function destroy(ArticlePlan $articlePlan): JsonResponse
    {
        $articlePlan->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'publish_date' => 'required|date',
            'publish_time' => 'required|string|max:10',
            'type' => 'nullable|string|max:255',
            'topic' => 'required|string',
            'is_lottery' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:todo,in_progress,done,blocked,cancelled',
            'notes' => 'sometimes|nullable|string',
            'blocked_reason' => 'sometimes|nullable|string',
        ]);

        if (! $request->has('is_lottery')) {
            $validated['is_lottery'] = false;
        }

        if (! $request->has('status')) {
            $validated['status'] = 'todo';
        }

        return $validated;
    }
}
