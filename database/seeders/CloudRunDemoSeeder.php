<?php

namespace Database\Seeders;

use App\Models\PhoneNumber;
use App\Models\PhonePackage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CloudRunDemoSeeder extends Seeder
{
    private const PHONE_COUNT = 500;

    public function run(): void
    {
        $this->seedManagerAccount();
        $this->seedPhoneNumbers();
    }

    private function seedManagerAccount(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'manager'],
            [
                'name' => 'Manager',
                'email' => 'manager@supernumber.local',
                'password' => Hash::make('manager'),
                'role' => 'manager',
                'is_active' => true,
            ]
        );
    }

    private function seedPhoneNumbers(): void
    {
        $rows = [];
        $now = now();
        $prices = [
            PhoneNumber::PACKAGE_PRICE_BASIC,
            PhoneNumber::PACKAGE_PRICE_STANDARD,
            PhoneNumber::PACKAGE_PRICE_PREMIUM,
        ];
        $packages = PhonePackage::query()
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->where('network_code', PhoneNumber::NETWORK_TRUE_DTAC)
            ->whereIn('monthly_price', $prices)
            ->get()
            ->keyBy('monthly_price');

        for ($index = 0; $index < self::PHONE_COUNT; $index++) {
            $raw = 800000000 + $index;
            $phoneNumber = '0' . str_pad((string) $raw, 9, '0', STR_PAD_LEFT);
            $price = $prices[$index % count($prices)];
            $package = $packages->get($price);

            $rows[] = [
                'phone_number' => $phoneNumber,
                'number_sum' => PhoneNumber::calculateNumberSum($phoneNumber),
                'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
                'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
                'plan_name' => $package?->name ?? PhoneNumber::PACKAGE_NAME,
                'sale_price' => $price,
                'initial_payment_price' => $price,
                'package_id' => $package?->id,
                'status' => PhoneNumber::STATUS_ACTIVE,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows): void {
            if (Schema::hasTable('phone_number_status_logs')) {
                DB::table('phone_number_status_logs')->delete();
            }

            PhoneNumber::query()->delete();
            PhoneNumber::query()->insert($rows);
        });
    }


}
