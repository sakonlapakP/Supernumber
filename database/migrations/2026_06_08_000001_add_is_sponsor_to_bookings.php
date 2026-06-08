<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ที่นั่งที่กันไว้ให้ Sponsor = booking ราคา 0 ที่ติด flag นี้
    // ลูกค้าเห็นเป็น "ขายแล้ว" (is_booked) แต่ฝั่งแอดมินแยกออกว่าเป็น Sponsor
    public function up(): void
    {
        Schema::table('suntaraporn_bookings', function (Blueprint $table) {
            $table->boolean('is_sponsor')->default(false)->after('total_price');
        });

        Schema::table('likay_bookings', function (Blueprint $table) {
            $table->boolean('is_sponsor')->default(false)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('suntaraporn_bookings', function (Blueprint $table) {
            $table->dropColumn('is_sponsor');
        });

        Schema::table('likay_bookings', function (Blueprint $table) {
            $table->dropColumn('is_sponsor');
        });
    }
};
