<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\UnixTimestampSerializable;

class PhoneNumberStatusLog extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $fillable = [
        'phone_number_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
    ];

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
