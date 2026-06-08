<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LikayBooking extends Model
{
    protected $table    = 'likay_bookings';
    protected $fillable = [
        'first_name', 'last_name', 'phone',
        'booker_name', 'slip_path', 'total_price', 'is_sponsor',
    ];

    protected $casts = ['is_sponsor' => 'boolean'];

    public function seats(): HasMany
    {
        return $this->hasMany(LikaySeat::class, 'booking_id');
    }
}
