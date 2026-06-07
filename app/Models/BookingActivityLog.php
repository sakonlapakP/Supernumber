<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingActivityLog extends Model
{
    // ตารางนี้เก็บแค่ created_at ไม่มี updated_at
    public const UPDATED_AT = null;

    protected $table = 'booking_activity_logs';

    protected $fillable = [
        'system', 'action', 'show_date', 'actor_name', 'booking_id',
        'seat_keys', 'customer_name', 'phone', 'total_price', 'search_query',
    ];

    protected $casts = [
        'seat_keys'   => 'array',
        'show_date'   => 'date:Y-m-d',
        'total_price' => 'integer',
    ];

    public const SYSTEM_LIKAY       = 'likay';
    public const SYSTEM_SUNTARAPORN = 'suntaraporn';

    public const ACTION_BOOK   = 'book';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_RESET  = 'reset';
    public const ACTION_SEARCH = 'search';

    /** ประเภท action ทั้งหมด (ใช้ validate filter ในหน้า history) */
    public const ACTIONS = [
        self::ACTION_BOOK,
        self::ACTION_CANCEL,
        self::ACTION_RESET,
        self::ACTION_SEARCH,
    ];

    /** ความยาวสูงสุดของแต่ละคอลัมน์ string (กัน overflow ก่อน insert) */
    private const MAX_LENGTHS = [
        'actor_name'    => 100,
        'customer_name' => 255,
        'phone'         => 30,
        'search_query'  => 255,
    ];

    /**
     * บันทึก log โดยไม่ให้ error กระทบ flow หลัก (ถ้าเขียนไม่ได้ก็ปล่อยผ่าน)
     *
     * @param array<string, mixed> $attributes
     */
    public static function record(array $attributes): void
    {
        // ตัดความยาวกันค่าเกิน column (เช่น ชื่อ+สกุลยาวเต็ม หรือคำค้นยาวมาก)
        foreach (self::MAX_LENGTHS as $field => $max) {
            if (isset($attributes[$field]) && is_string($attributes[$field])) {
                $attributes[$field] = mb_substr($attributes[$field], 0, $max);
            }
        }

        try {
            static::create($attributes);
        } catch (\Throwable) {
            // logging ต้องไม่ทำให้การจอง/ยกเลิกล้มเหลว
        }
    }
}
