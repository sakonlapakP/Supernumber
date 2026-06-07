<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ที่นั่งที่ is_booked=true แต่ booking_id=null (ถูก mark ก่อนระบบ booking ถูกสร้าง)
    // ปล่อยว่างเพื่อให้จองใหม่แบบมี booking record ได้ถูกต้อง
    public function up(): void
    {
        DB::table('suntaraporn_seats')
            ->where('is_booked', true)
            ->whereNull('booking_id')
            ->update([
                'is_booked'  => false,
                'booked_at'  => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // ไม่สามารถย้อนกลับได้เพราะไม่มีข้อมูลการจองเดิม
    }
};
