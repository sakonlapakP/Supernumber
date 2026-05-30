<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suntaraporn_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20);
            $table->string('booker_name', 200);
            $table->string('slip_path', 500)->nullable();
            $table->unsignedInteger('total_price')->default(0);
            $table->timestamps();
        });

        Schema::table('suntaraporn_seats', function (Blueprint $table) {
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('suntaraporn_bookings')
                ->nullOnDelete()
                ->after('booked_at');
        });
    }

    public function down(): void
    {
        Schema::table('suntaraporn_seats', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });

        Schema::dropIfExists('suntaraporn_bookings');
    }
};
