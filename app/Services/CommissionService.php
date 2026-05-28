<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CustomerOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    // Commission rates per tier (applied to net_amount)
    public const TIER_RATES = [
        1 => 25.00,
        2 => 15.00,
        3 => 10.00,
    ];

    public const REFERRAL_DISCOUNT = 100.00;

    /**
     * Entry point: called when an order's status changes to 'completed'.
     * Runs inside an ACID transaction.
     */
    public function handleOrderCompleted(CustomerOrder $order): void
    {
        if (! $order->referral_code_used || ! $order->seller_user_id) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $seller = User::with('parent.parent')
                ->where('id', $order->seller_user_id)
                ->lockForUpdate()
                ->first();

            if (! $seller || ! $seller->isSaleApproved()) {
                return;
            }

            $netAmount = $this->resolveNetAmount($order);

            if ($netAmount <= 0) {
                return;
            }

            $period = Carbon::now()->format('Y-m');

            $this->createCommission($order, $seller, 1, $netAmount, $period);

            if ($seller->parent_id) {
                $tier2 = User::lockForUpdate()->find($seller->parent_id);
                if ($tier2 && $tier2->isSaleApproved()) {
                    $this->createCommission($order, $tier2, 2, $netAmount, $period);
                }

                if ($tier2 && $tier2->parent_id) {
                    $tier3 = User::lockForUpdate()->find($tier2->parent_id);
                    if ($tier3 && $tier3->isSaleApproved()) {
                        $this->createCommission($order, $tier3, 3, $netAmount, $period);
                    }
                }
            }
        });
    }

    /**
     * Monthly batch job: evaluate 3:1 ratio rule and approve/reject pending commissions.
     * Runs at midnight on the last day of each month.
     */
    public function processMonthlyCommissions(string $period): void
    {
        DB::transaction(function () use ($period): void {
            $pendingTier2And3 = Commission::where('period', $period)
                ->whereIn('tier_level', [2, 3])
                ->where('status', Commission::STATUS_PENDING)
                ->with('user')
                ->get();

            if ($pendingTier2And3->isEmpty()) {
                return;
            }

            $userIds = $pendingTier2And3->pluck('user_id')->unique();

            foreach ($userIds as $userId) {
                $user = User::lockForUpdate()->find($userId);
                if (! $user) {
                    continue;
                }

                // Personal direct sales this period (tier 1 only)
                $personalSales = Commission::where('user_id', $userId)
                    ->where('tier_level', 1)
                    ->where('period', $period)
                    ->count();

                // Downline sales (tier 2 + 3) this period
                $downlineSales = Commission::where('user_id', $userId)
                    ->whereIn('tier_level', [2, 3])
                    ->where('period', $period)
                    ->count();

                // Must have at least 1 personal sale per 3 downline sales
                $qualified = $downlineSales === 0
                    || ($personalSales >= ceil($downlineSales / 3));

                $status = $qualified
                    ? Commission::STATUS_APPROVED
                    : Commission::STATUS_REJECTED;

                $reason = $qualified
                    ? null
                    : "ไม่ผ่านเกณฑ์ 3:1 (ขายตรง {$personalSales} / ลูกทีม {$downlineSales})";

                Commission::where('user_id', $userId)
                    ->whereIn('tier_level', [2, 3])
                    ->where('period', $period)
                    ->where('status', Commission::STATUS_PENDING)
                    ->update([
                        'status'           => $status,
                        'rejection_reason' => $reason,
                    ]);

                Log::info("Commission batch [{$period}] user #{$userId}: {$status} (personal={$personalSales}, downline={$downlineSales})");
            }

            // Tier 1 commissions are always approved at month end (no ratio rule)
            Commission::where('period', $period)
                ->where('tier_level', 1)
                ->where('status', Commission::STATUS_PENDING)
                ->update(['status' => Commission::STATUS_APPROVED]);
        });
    }

    /**
     * Calculate net_amount for the order (retail price minus 100 THB discount).
     */
    private function resolveNetAmount(CustomerOrder $order): float
    {
        if ($order->net_amount !== null && $order->net_amount > 0) {
            return (float) $order->net_amount;
        }

        $retail = (float) ($order->sale_price ?? $order->initial_payment_price ?? 0);

        return max(0, $retail - self::REFERRAL_DISCOUNT);
    }

    private function createCommission(
        CustomerOrder $order,
        User $user,
        int $tier,
        float $netAmount,
        string $period
    ): void {
        $rate   = self::TIER_RATES[$tier];
        $amount = round($netAmount * $rate / 100, 2);

        Commission::create([
            'order_id'           => $order->id,
            'user_id'            => $user->id,
            'tier_level'         => $tier,
            'percentage_applied' => $rate,
            'calculated_amount'  => $amount,
            'status'             => Commission::STATUS_PENDING,
            'period'             => $period,
        ]);
    }
}
