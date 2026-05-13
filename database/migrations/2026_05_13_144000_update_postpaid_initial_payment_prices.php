<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_numbers') || ! Schema::hasTable('phone_packages')) {
            return;
        }

        foreach (PhoneNumber::POSTPAID_INITIAL_PAYMENT_BY_MONTHLY_PRICE as $monthlyPrice => $initialPaymentPrice) {
            $packageIds = DB::table('phone_packages')
                ->where('monthly_price', $monthlyPrice)
                ->pluck('id');

            DB::table('phone_numbers')
                ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->whereIn('package_id', $packageIds)
                ->update([
                    'initial_payment_price' => $initialPaymentPrice,
                    'sale_price' => $monthlyPrice,
                ]);

            DB::table('customer_orders')
                ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->whereIn('package_id', $packageIds)
                ->update([
                    'selected_package' => $initialPaymentPrice,
                    'initial_payment_price' => $initialPaymentPrice,
                    'monthly_price' => $monthlyPrice,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_numbers') || ! Schema::hasTable('phone_packages')) {
            return;
        }

        foreach (array_keys(PhoneNumber::POSTPAID_INITIAL_PAYMENT_BY_MONTHLY_PRICE) as $monthlyPrice) {
            $packageIds = DB::table('phone_packages')
                ->where('monthly_price', $monthlyPrice)
                ->pluck('id');

            DB::table('phone_numbers')
                ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->whereIn('package_id', $packageIds)
                ->update([
                    'initial_payment_price' => $monthlyPrice,
                    'sale_price' => $monthlyPrice,
                ]);

            DB::table('customer_orders')
                ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->whereIn('package_id', $packageIds)
                ->update([
                    'selected_package' => $monthlyPrice,
                    'initial_payment_price' => $monthlyPrice,
                    'monthly_price' => $monthlyPrice,
                ]);
        }
    }
};
