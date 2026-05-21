<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticlePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json(['data' => $plans]);
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

