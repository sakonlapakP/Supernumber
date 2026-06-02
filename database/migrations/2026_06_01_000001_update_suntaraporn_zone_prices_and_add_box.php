<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Update existing zone prices
        $updates = [
            'purple' => 5000,
            'green'  => 2500,
            'pink'   => 2000,
            'blue'   => 1500,
            'yellow' => 1000,
        ];

        foreach ($updates as $zone => $price) {
            DB::table('suntaraporn_zone_prices')
                ->where('zone', $zone)
                ->update(['price' => $price, 'updated_at' => $now]);
        }

        // Add box zone if not exists
        if (! DB::table('suntaraporn_zone_prices')->where('zone', 'box')->exists()) {
            DB::table('suntaraporn_zone_prices')->insert([
                'zone'       => 'box',
                'price'      => 1500,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $now = now();

        $reverts = [
            'purple' => 3500,
            'green'  => 3000,
            'pink'   => 2500,
            'blue'   => 2000,
            'yellow' => 1500,
        ];

        foreach ($reverts as $zone => $price) {
            DB::table('suntaraporn_zone_prices')
                ->where('zone', $zone)
                ->update(['price' => $price, 'updated_at' => $now]);
        }

        DB::table('suntaraporn_zone_prices')->where('zone', 'box')->delete();
    }
};
