<?php

namespace Database\Seeders;

use App\Models\PhoneNumber;
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

        for ($index = 0; $index < self::PHONE_COUNT; $index++) {
            $raw = 800000000 + $index;
            $phoneNumber = '0' . str_pad((string) $raw, 9, '0', STR_PAD_LEFT);
            $price = $prices[$index % count($prices)];

            $rows[] = [
                'phone_number' => $phoneNumber,
                'display_number' => $this->formatDisplayNumber($phoneNumber),
                'number_sum' => PhoneNumber::calculateNumberSum($phoneNumber),
                'network_code' => 'true_dtac',
                'plan_name' => PhoneNumber::PACKAGE_NAME,
                'price_text' => (string) $price,
                'sale_price' => $price,
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

    private function formatDisplayNumber(string $phoneNumber): string
    {
        return substr($phoneNumber, 0, 3)
            . '-'
            . substr($phoneNumber, 3, 3)
            . '-'
            . substr($phoneNumber, 6, 4);
    }
}
