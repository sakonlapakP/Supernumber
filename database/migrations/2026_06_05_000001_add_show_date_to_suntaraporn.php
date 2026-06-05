<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // วันแรกของการแสดง — ใช้ backfill ข้อมูลเดิม (ตรงกับ SuntarapornBandController::SHOW_DATES[0])
    private const DEFAULT_SHOW_DATE = '2026-10-31';

    public function up(): void
    {
        Schema::table('suntaraporn_seats', function (Blueprint $table) {
            // เก้าอี้ตัวเดียวกันต้องจองแยกได้คนละวัน → seat_key ไม่ unique เดี่ยวอีกต่อไป
            $table->dropUnique(['seat_key']);
            $table->date('show_date')->default(self::DEFAULT_SHOW_DATE)->after('seat_key');
            $table->unique(['show_date', 'seat_key']);
        });

        Schema::table('suntaraporn_bookings', function (Blueprint $table) {
            $table->date('show_date')->default(self::DEFAULT_SHOW_DATE)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('suntaraporn_seats', function (Blueprint $table) {
            $table->dropUnique(['show_date', 'seat_key']);
            $table->dropColumn('show_date');
            $table->unique('seat_key');
        });

        Schema::table('suntaraporn_bookings', function (Blueprint $table) {
            $table->dropColumn('show_date');
        });
    }
};
