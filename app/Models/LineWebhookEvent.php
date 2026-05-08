<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\UnixTimestampSerializable;

class LineWebhookEvent extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'signature_valid' => 'boolean',
            'received_at' => 'datetime',
        ];
    }
}
