<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\UnixTimestampSerializable;

class ContactMessage extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $fillable = [
        'name',
        'phone',
        'message',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }
}
