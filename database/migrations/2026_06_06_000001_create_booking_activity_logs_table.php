<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('system', 20);                 // likay | suntaraporn
            $table->string('action', 20);                 // book | cancel | reset | search
            $table->date('show_date')->nullable();        // suntaraporn รอบการแสดง (likay = null)
            $table->string('actor_name', 100);            // แอดมินที่ทำรายการ
            // ไม่ผูก foreign key เพราะ booking ถูกลบเมื่อ cancel/reset → เก็บเป็น snapshot
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->json('seat_keys')->nullable();        // snapshot ที่นั่ง ณ เวลาที่ทำรายการ
            $table->string('customer_name', 200)->nullable();
            $table->string('phone', 30)->nullable();
            $table->unsignedInteger('total_price')->nullable();
            $table->string('search_query', 200)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['system', 'created_at']);
            $table->index(['system', 'show_date']);
            $table->index(['system', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_activity_logs');
    }
};
