<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suntaraporn_zones', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique();
            $table->string('label', 50);
            $table->string('color', 7);
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        // Read existing prices from suntaraporn_zone_prices
        $existingPrices = DB::table('suntaraporn_zone_prices')->pluck('price', 'zone')->all();

        $zones = [
            ['slug' => 'vip',    'label' => 'VIP',    'color' => '#FFEE32', 'sort_order' => 0],
            ['slug' => 'yellow', 'label' => 'เหลือง', 'color' => '#FFEE32', 'sort_order' => 6],
            ['slug' => 'blue',   'label' => 'เขียว',  'color' => '#4CAF50', 'sort_order' => 4],
            ['slug' => 'pink',   'label' => 'ฟ้าอ่อน', 'color' => '#29B6F6', 'sort_order' => 3],
            ['slug' => 'green',  'label' => 'แดง',    'color' => '#EF5350', 'sort_order' => 2],
            ['slug' => 'purple', 'label' => 'ม่วง',   'color' => '#AB47BC', 'sort_order' => 1],
            ['slug' => 'box',    'label' => 'BOX A-F', 'color' => '#4CAF50', 'sort_order' => 5],
        ];

        foreach ($zones as $zone) {
            DB::table('suntaraporn_zones')->insert([
                'slug'       => $zone['slug'],
                'label'      => $zone['label'],
                'color'      => $zone['color'],
                'price'      => $existingPrices[$zone['slug']] ?? 0,
                'sort_order' => $zone['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::dropIfExists('suntaraporn_zone_prices');
    }

    public function down(): void
    {
        // Recreate suntaraporn_zone_prices from zones
        Schema::create('suntaraporn_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->string('zone', 20)->unique();
            $table->unsignedInteger('price');
            $table->timestamps();
        });

        $now = now();
        $zones = DB::table('suntaraporn_zones')->get();
        foreach ($zones as $zone) {
            DB::table('suntaraporn_zone_prices')->insert([
                'zone'       => $zone->slug,
                'price'      => $zone->price,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::dropIfExists('suntaraporn_zones');
    }
};
