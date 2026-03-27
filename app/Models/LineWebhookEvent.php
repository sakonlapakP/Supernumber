<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineWebhookEvent extends Model
{
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
