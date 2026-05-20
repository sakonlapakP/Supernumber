<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_orders') || ! Schema::hasTable('phone_numbers')) {
            return;
        }

        DB::table('phone_numbers')
            ->join('customer_orders', function ($join): void {
                $join->on('customer_orders.phone_number_id', '=', 'phone_numbers.id')
                    ->orOn('customer_orders.ordered_number', '=', 'phone_numbers.phone_number');
            })
            ->whereIn('customer_orders.status', [
                'processing',
                'submitted',
                'pending_review',
                'reviewing',
                'paid',
            ])
            ->where('phone_numbers.status', 'active')
            ->update(['phone_numbers.status' => 'hold']);
    }

    public function down(): void
    {
        // Data backfill only. Reversing would risk releasing numbers that were deliberately held later.
    }
};
