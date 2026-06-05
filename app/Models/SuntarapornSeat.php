<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuntarapornSeat extends Model
{
    protected $table    = 'suntaraporn_seats';
    protected $fillable = ['seat_key', 'show_date', 'is_booked', 'booked_at', 'booking_id'];
    protected $casts    = ['is_booked' => 'boolean', 'booked_at' => 'datetime', 'show_date' => 'date:Y-m-d'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(SuntarapornBooking::class, 'booking_id');
    }
}
