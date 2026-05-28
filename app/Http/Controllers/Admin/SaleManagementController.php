<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\SaleKycDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SaleManagementController extends Controller
{
    // ─── Sale Agents List ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $sales = User::where('role', User::ROLE_SALE)
            ->when($status !== 'all', fn ($q) => $q->where('sale_status', $status))
            ->with(['parent', 'kycDocuments'])
            ->latest()
            ->paginate(20);

        return view('admin.sales.index', compact('sales', 'status'));
    }

    public function show(User $sale)
    {
        abort_unless($sale->isSale(), 404);
        $sale->load(['kycDocuments', 'parent', 'children']);

        $latestPeriod = now()->format('Y-m');
        $stats = [
            'total_orders'  => $sale->commissions()->where('tier_level', 1)->count(),
            'total_approved'=> $sale->commissions()->where('status', Commission::STATUS_APPROVED)->sum('calculated_amount'),
            'this_month'    => $sale->commissions()->where('period', $latestPeriod)->sum('calculated_amount'),
        ];

        return view('admin.sales.show', compact('sale', 'stats'));
    }

    public function approve(User $sale)
    {
        abort_unless($sale->isSale(), 404);

        $sale->update([
            'sale_status' => User::SALE_STATUS_APPROVED,
            'is_active'   => true,
        ]);

        $sale->kycDocuments()->update(['status' => SaleKycDocument::STATUS_APPROVED]);

        return back()->with('success', "อนุมัติเซลล์ {$sale->name} เรียบร้อย");
    }

    public function reject(Request $request, User $sale)
    {
        abort_unless($sale->isSale(), 404);

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $sale->update([
            'sale_status' => User::SALE_STATUS_REJECTED,
            'is_active'   => false,
        ]);

        $sale->kycDocuments()->update([
            'status'           => SaleKycDocument::STATUS_REJECTED,
            'rejection_reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', "ปฏิเสธเซลล์ {$sale->name} แล้ว");
    }

    public function downloadKyc(SaleKycDocument $doc)
    {
        abort_unless(Storage::disk('local')->exists($doc->file_path), 404);

        return Storage::disk('local')->download(
            $doc->file_path,
            $doc->original_name ?? basename($doc->file_path)
        );
    }

    // ─── Commissions ──────────────────────────────────────────────────────────

    public function commissions(Request $request)
    {
        $period = $request->query('period', now()->format('Y-m'));
        $status = $request->query('status', 'all');

        $commissions = Commission::with(['user', 'order'])
            ->where('period', $period)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30);

        $totals = Commission::where('period', $period)
            ->selectRaw('status, SUM(calculated_amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $months = $this->availableMonths();

        return view('admin.sales.commissions', compact('commissions', 'totals', 'period', 'months', 'status'));
    }

    public function approveCommission(Commission $commission)
    {
        $commission->update(['status' => Commission::STATUS_APPROVED, 'rejection_reason' => null]);

        return back()->with('success', 'อนุมัติ commission แล้ว');
    }

    public function rejectCommission(Request $request, Commission $commission)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        $commission->update([
            'status'           => Commission::STATUS_REJECTED,
            'rejection_reason' => $data['reason'] ?? 'ปฏิเสธโดย Admin',
        ]);

        return back()->with('success', 'ปฏิเสธ commission แล้ว');
    }

    private function availableMonths(): array
    {
        $months = [];
        $start  = now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i <= 11; $i++) {
            $m = $start->copy()->addMonths($i);
            $months[$m->format('Y-m')] = $m->locale('th')->isoFormat('MMMM YYYY');
        }

        return array_reverse($months, true);
    }
}
