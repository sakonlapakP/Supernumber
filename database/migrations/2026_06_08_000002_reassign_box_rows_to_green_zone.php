<?php

use App\Services\LikaySeatMap;
use App\Services\SuntarapornSeatMap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // เก้าอี้ Box แสดงสีเขียว แต่ยังผูกกับ zone พิเศษ "box" (สีเขียว ราคา ฿0 ฝั่งสุนทราภรณ์)
    // → ย้ายไปผูกโซนเขียว/เขียว จริง (สีเดิม #4CAF50) เพื่อให้ "ราคาตรงกับสี"
    //   สุนทราภรณ์: BOXB/C/F  ฿0   → ฿2,000 (โซนเขียว)
    //   ลิเก:       BOXA–F     ฿1,500 → ฿1,500 (โซนเขียว — ราคาเท่าเดิม, แค่ย้ายออกจาก zone box)
    // A/D (แดง) และ E (ฟ้าอ่อน) ของสุนทราภรณ์ผูกโซนสีถูกอยู่แล้ว ไม่แตะ

    public function up(): void
    {
        $now = now();

        // ── Suntaraporn: ย้าย Box เขียวที่ยังเป็น zone box → โซนเขียว (slug blue, ฿2,000) ──
        $this->moveBoxRows(
            zonesTable: 'suntaraporn_zones',
            rowZonesTable: 'suntaraporn_row_zones',
            targetSlug: 'blue',
            now: $now,
        );

        // ── Likay: ย้าย Box ทุกตัวจาก zone box → โซนเขียว (slug blue, ฿1,500) ──
        $this->moveBoxRows(
            zonesTable: 'likay_zones',
            rowZonesTable: 'likay_row_zones',
            targetSlug: 'blue',
            now: $now,
        );

        SuntarapornSeatMap::flushCache();
        LikaySeatMap::flushCache();
    }

    public function down(): void
    {
        $now = now();

        // คืน Box ที่ย้ายมา กลับไป zone box
        $this->revertBoxRows('suntaraporn_zones', 'suntaraporn_row_zones', $now);
        $this->revertBoxRows('likay_zones', 'likay_row_zones', $now);

        SuntarapornSeatMap::flushCache();
        LikaySeatMap::flushCache();
    }

    /** ย้าย row Box ที่ยังผูก zone "box" → โซนสีจริง (target slug) */
    private function moveBoxRows(string $zonesTable, string $rowZonesTable, string $targetSlug, $now): void
    {
        $boxZoneId    = DB::table($zonesTable)->where('slug', 'box')->value('id');
        $targetZoneId = DB::table($zonesTable)->where('slug', $targetSlug)->value('id');

        if (! $boxZoneId || ! $targetZoneId) {
            return; // ไม่มีโซนที่ต้องใช้ — ข้ามอย่างปลอดภัย
        }

        DB::table($rowZonesTable)
            ->where('row_key', 'like', 'BOX%')
            ->where('zone_id', $boxZoneId)
            ->update(['zone_id' => $targetZoneId, 'updated_at' => $now]);
    }

    /** คืน row Box ที่ตอนนี้อยู่โซนเขียว กลับไป zone "box" */
    private function revertBoxRows(string $zonesTable, string $rowZonesTable, $now): void
    {
        $boxZoneId    = DB::table($zonesTable)->where('slug', 'box')->value('id');
        $targetZoneId = DB::table($zonesTable)->where('slug', 'blue')->value('id');

        if (! $boxZoneId || ! $targetZoneId) {
            return;
        }

        DB::table($rowZonesTable)
            ->where('row_key', 'like', 'BOX%')
            ->where('zone_id', $targetZoneId)
            ->update(['zone_id' => $boxZoneId, 'updated_at' => $now]);
    }
};
