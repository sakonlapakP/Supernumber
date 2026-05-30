<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suntaraporn_seats', function (Blueprint $table) {
            $table->id();
            $table->string('seat_key', 30)->unique();
            $table->boolean('is_booked')->default(false);
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('suntaraporn_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->string('zone', 20)->unique();
            $table->unsignedInteger('price');
            $table->timestamps();
        });

        DB::table('suntaraporn_zone_prices')->insert([
            ['zone' => 'vvip',   'price' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'vip',    'price' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'box_b',  'price' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'yellow', 'price' => 1500, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'blue',   'price' => 2000, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'pink',   'price' => 2500, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'green',  'price' => 3000, 'created_at' => now(), 'updated_at' => now()],
            ['zone' => 'purple', 'price' => 3500, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('suntaraporn_seats');
        Schema::dropIfExists('suntaraporn_zone_prices');
    }
};
