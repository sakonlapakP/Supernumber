<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_activity_logs', function (Blueprint $table) {
            // customer_name: first_name(100) + ' ' + last_name(100) = สูงสุด 201 → 200 ไม่พอ
            $table->string('customer_name', 255)->nullable()->change();
            // search_query: ช่องค้นหาไม่จำกัดความยาว → กัน overflow
            $table->string('search_query', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('booking_activity_logs', function (Blueprint $table) {
            $table->string('customer_name', 200)->nullable()->change();
            $table->string('search_query', 200)->nullable()->change();
        });
    }
};
