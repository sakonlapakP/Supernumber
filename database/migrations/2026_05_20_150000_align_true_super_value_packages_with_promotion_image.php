<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VOICE_MINUTES_BY_MONTHLY_PRICE = [
        1499 => '1,000 นาที',
        1699 => '1,200 นาที',
        1999 => '1,600 นาที',
        2199 => '1,900 นาที',
    ];

    private const RETIRED_MONTHLY_PRICES = [
        2499,
        2699,
        2999,
        3499,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('phone_packages')) {
            return;
        }

        foreach (self::VOICE_MINUTES_BY_MONTHLY_PRICE as $monthlyPrice => $voiceMinutes) {
            DB::table('phone_packages')
                ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->where('network_code', PhoneNumber::NETWORK_TRUE)
                ->where('monthly_price', $monthlyPrice)
                ->update([
                    'voice_minutes' => $voiceMinutes,
                    'updated_at' => now(),
                ]);
        }

        DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('network_code', PhoneNumber::NETWORK_TRUE)
            ->whereIn('monthly_price', self::RETIRED_MONTHLY_PRICES)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('phone_packages')) {
            return;
        }

        $previousVoiceMinutes = [
            1499 => '900 นาที',
            1699 => '1,100 นาที',
            1999 => '1,500 นาที',
            2199 => '1,800 นาที',
        ];

        foreach ($previousVoiceMinutes as $monthlyPrice => $voiceMinutes) {
            DB::table('phone_packages')
                ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->where('network_code', PhoneNumber::NETWORK_TRUE)
                ->where('monthly_price', $monthlyPrice)
                ->update([
                    'voice_minutes' => $voiceMinutes,
                    'updated_at' => now(),
                ]);
        }

        DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('network_code', PhoneNumber::NETWORK_TRUE)
            ->whereIn('monthly_price', self::RETIRED_MONTHLY_PRICES)
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
