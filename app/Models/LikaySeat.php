<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LikaySeat extends Model
{
    protected $table    = 'likay_seats';
    protected $fillable = ['seat_key', 'is_booked', 'booked_at', 'booking_id'];
    protected $casts    = ['is_booked' => 'boolean', 'booked_at' => 'datetime'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(LikayBooking::class, 'booking_id');
    }
}
