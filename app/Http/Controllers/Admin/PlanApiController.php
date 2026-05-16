<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticlePlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->get()
            ->map(fn (ArticlePlan $p) => $this->formatPlan($p));

        return response()->json(['data' => $plans]);
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
            ->get()
            ->map(fn (ArticlePlan $p) => $this->formatPlan($p));

        return response()->json(['data' => $plans]);
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
            ->get()
            ->map(fn (ArticlePlan $p) => $this->formatPlan($p));

        return response()->json(['data' => $plans]);
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

        return response()->json(['data' => $this->formatPlan($plan->fresh(['article', 'assignedUser']))]);
    }

    private function formatPlan(ArticlePlan $plan): array
    {
        return [
            'id' => $plan->id,
            'publish_date' => $plan->publish_date?->toDateString(),
            'publish_time' => $plan->publish_time,
            'type' => $plan->type,
            'topic' => $plan->topic,
            'is_lottery' => $plan->is_lottery,
            'status' => $plan->status,
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
