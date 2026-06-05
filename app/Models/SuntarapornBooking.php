<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuntarapornBooking extends Model
{
    protected $table    = 'suntaraporn_bookings';
    protected $fillable = [
        'show_date', 'first_name', 'last_name', 'phone',
        'booker_name', 'slip_path', 'total_price',
    ];

    protected $casts = ['show_date' => 'date:Y-m-d'];

    public function seats(): HasMany
    {
        return $this->hasMany(SuntarapornSeat::class, 'booking_id');
    }
}
