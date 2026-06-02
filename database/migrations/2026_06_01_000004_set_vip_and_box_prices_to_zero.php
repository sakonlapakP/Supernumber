<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $prices = [
            'vvip'   => 0,
            'vip'    => 0,
            'box_b'  => 0,
            'box'    => 0,
        ];

        foreach ($prices as $zone => $price) {
            DB::table('suntaraporn_zone_prices')
                ->where('zone', $zone)
                ->update(['price' => $price, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $now = now();

        $prices = [
            'vvip'   => 88000,
            'vip'    => 88000,
            'box_b'  => 88000,
            'box'    => 88000,
        ];

        foreach ($prices as $zone => $price) {
            DB::table('suntaraporn_zone_prices')
                ->where('zone', $zone)
                ->update(['price' => $price, 'updated_at' => $now]);
        }
    }
};
