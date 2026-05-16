<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticlePlan;
use App\Models\User;
use Illuminate\Http\Request;

class ArticlePlanController extends Controller
{
    private const VALID_STATUSES = [
        ArticlePlan::STATUS_TODO,
        ArticlePlan::STATUS_IN_PROGRESS,
        ArticlePlan::STATUS_DONE,
        ArticlePlan::STATUS_BLOCKED,
        ArticlePlan::STATUS_CANCELLED,
    ];

    public function store(Request $request)
    {
        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $validated = $request->validate([
            'publish_date' => 'required|date',
            'publish_time' => 'required|string',
            'type' => 'nullable|string',
            'topic' => 'required|string',
            'is_lottery' => 'boolean',
            'status' => 'nullable|string|in:' . implode(',', self::VALID_STATUSES),
            'assigned_to' => 'nullable|integer|exists:users,id',
            'due_date' => 'nullable|date',
            'blocked_reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (! $request->has('is_lottery')) {
            $validated['is_lottery'] = false;
        }

        $validated['status'] ??= ArticlePlan::STATUS_TODO;

        ArticlePlan::create($validated);

        return back()->with('status_message', 'เพิ่มแผนการเผยแพร่เรียบร้อยแล้ว');
    }

    public function update(Request $request, ArticlePlan $articlePlan)
    {
        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $validated = $request->validate([
            'publish_date' => 'required|date',
            'publish_time' => 'required|string',
            'type' => 'nullable|string',
            'topic' => 'required|string',
            'is_lottery' => 'boolean',
            'status' => 'nullable|string|in:' . implode(',', self::VALID_STATUSES),
            'assigned_to' => 'nullable|integer|exists:users,id',
            'due_date' => 'nullable|date',
            'blocked_reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (! $request->has('is_lottery')) {
            $validated['is_lottery'] = false;
        }

        $articlePlan->update($validated);

        return back()->with('status_message', 'อัปเดตแผนการเผยแพร่เรียบร้อยแล้ว');
    }

    public function destroy(ArticlePlan $articlePlan)
    {
        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $articlePlan->delete();

        return back()->with('status_message', 'ลบแผนการเผยแพร่เรียบร้อยแล้ว');
    }
}
