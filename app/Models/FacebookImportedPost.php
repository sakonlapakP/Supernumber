<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookImportedPost extends Model
{
    protected $fillable = [
        'facebook_post_id',
        'source_node_id',
        'source_node_type',
        'message',
        'story',
        'permalink_url',
        'full_picture',
        'attachments_json',
        'raw_json',
        'facebook_created_time',
        'imported_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments_json' => 'array',
            'raw_json' => 'array',
            'facebook_created_time' => 'datetime',
            'imported_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
