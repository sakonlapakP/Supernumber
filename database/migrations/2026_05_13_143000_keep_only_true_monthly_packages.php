<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_packages')) {
            return;
        }

        $packages = DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('name', 'like', PhoneNumber::PACKAGE_NAME . '%')
            ->get();

        $truePackagesByPrice = $packages
            ->where('network_code', PhoneNumber::NETWORK_TRUE)
            ->keyBy('monthly_price');

        foreach ($packages->where('network_code', '!=', PhoneNumber::NETWORK_TRUE) as $package) {
            $truePackage = $truePackagesByPrice->get($package->monthly_price);

            if ($truePackage === null) {
                $truePackageId = DB::table('phone_packages')->insertGetId([
                    'code' => 'TRUE-SV-' . $package->monthly_price,
                    'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
                    'network_code' => PhoneNumber::NETWORK_TRUE,
                    'name' => $package->name,
                    'monthly_price' => $package->monthly_price,
                    'data_quota' => $package->data_quota,
                    'speed_after_quota' => $package->speed_after_quota,
                    'voice_minutes' => $package->voice_minutes,
                    'benefits' => $package->benefits,
                    'conditions' => $package->conditions,
                    'is_active' => $package->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $truePackageId = $truePackage->id;
            }

            DB::table('phone_numbers')
                ->where('package_id', $package->id)
                ->update(['package_id' => $truePackageId]);

            DB::table('customer_orders')
                ->where('package_id', $package->id)
                ->update(['package_id' => $truePackageId]);
        }

        DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('name', 'like', PhoneNumber::PACKAGE_NAME . '%')
            ->where('network_code', '!=', PhoneNumber::NETWORK_TRUE)
            ->delete();
    }

    public function down(): void
    {
        // This migration removes duplicated default packages for non-TRUE networks.
        // Restoring them would reintroduce the incorrect admin list.
    }
};
