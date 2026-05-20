<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_numbers') || ! Schema::hasTable('phone_number_status_logs')) {
            return;
        }

        $latestLogIds = DB::table('phone_number_status_logs')
            ->selectRaw('MAX(id) as id')
            ->groupBy('phone_number_id');

        DB::table('phone_numbers')
            ->join('phone_number_status_logs', 'phone_number_status_logs.phone_number_id', '=', 'phone_numbers.id')
            ->joinSub($latestLogIds, 'latest_logs', function ($join): void {
                $join->on('latest_logs.id', '=', 'phone_number_status_logs.id');
            })
            ->where('phone_number_status_logs.to_status', 'hold')
            ->where('phone_numbers.status', 'active')
            ->update(['phone_numbers.status' => 'hold']);
    }

    public function down(): void
    {
        // Data reconciliation only. Reversing could release numbers that should remain held.
    }
};
