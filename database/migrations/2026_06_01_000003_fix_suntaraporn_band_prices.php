<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $prices = [
            'yellow' => 1500,
            'blue'   => 2000,
            'pink'   => 2500,
            'green'  => 3000,
            'purple' => 3500,
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

    public function down(): void
    {
        $now = now();

        $prices = [
            'yellow' => 1000,
            'blue'   => 1500,
            'pink'   => 2000,
            'green'  => 2500,
            'purple' => 5000,
            'vvip'   => 8000,
            'vip'    => 8000,
            'box_b'  => 8000,
            'box'    => 1500,
        ];

        foreach ($prices as $zone => $price) {
            DB::table('suntaraporn_zone_prices')
                ->where('zone', $zone)
                ->update(['price' => $price, 'updated_at' => $now]);
        }
    }
};
