<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ที่นั่ง "ยังไม่จ่ายตัง" = booking ปกติ (มีชื่อ/ราคาจริง) ที่ติด flag นี้
    // ลูกค้าเห็นเป็น "ขายแล้ว" (is_booked) ปกติ แต่ฝั่งแอดมินเห็นเป็นสีเทาอ่อน + นับเป็นยอดค้างจ่าย
    public function up(): void
    {
        Schema::table('suntaraporn_bookings', function (Blueprint $table) {
            $table->boolean('is_unpaid')->default(false)->after('is_sponsor');
        });

        Schema::table('likay_bookings', function (Blueprint $table) {
            $table->boolean('is_unpaid')->default(false)->after('is_sponsor');
        });
    }

    public function down(): void
    {
        Schema::table('suntaraporn_bookings', function (Blueprint $table) {
            $table->dropColumn('is_unpaid');
        });

        Schema::table('likay_bookings', function (Blueprint $table) {
            $table->dropColumn('is_unpaid');
        });
    }
};
