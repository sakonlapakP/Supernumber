<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RETIRED_MONTHLY_PRICES = [
        2499,
        2699,
        2999,
        3499,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('phone_numbers') || ! Schema::hasTable('phone_packages')) {
            return;
        }

        $retiredPackageIds = DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('network_code', PhoneNumber::NETWORK_TRUE)
            ->whereIn('monthly_price', self::RETIRED_MONTHLY_PRICES)
            ->pluck('id');

        DB::table('phone_numbers')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('status', PhoneNumber::STATUS_ACTIVE)
            ->whereIn('package_id', $retiredPackageIds)
            ->update([
                'status' => PhoneNumber::STATUS_UNACTIVE,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally not reactivating numbers because availability may have
        // changed after this migration runs.
    }
};
