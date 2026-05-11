<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticlePlan;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArticlePlanController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
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
        ]);

        if (!$request->has('is_lottery')) {
            $validated['is_lottery'] = false;
        }

        ArticlePlan::create($validated);

        return back()->with('status_message', 'เพิ่มแผนการเผยแพร่เรียบร้อยแล้ว');
    }

    /**
     * Update the specified resource in storage.
     */
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
        ]);

        if (!$request->has('is_lottery')) {
            $validated['is_lottery'] = false;
        }

        $articlePlan->update($validated);

        return back()->with('status_message', 'อัปเดตแผนการเผยแพร่เรียบร้อยแล้ว');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArticlePlan $articlePlan)
    {
        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $articlePlan->delete();

        return back()->with('status_message', 'ลบแผนการเผยแพร่เรียบร้อยแล้ว');
    }
}
