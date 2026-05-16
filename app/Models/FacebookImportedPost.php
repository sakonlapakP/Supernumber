<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookImportedPost extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_REFRESHED = 'refreshed';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';

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
        'status',
        'article_id',
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

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function resolveImageUrl(): string
    {
        $url = trim((string) ($this->full_picture ?? ''));
        if ($url !== '') {
            return $url;
        }

        $attachments = is_array($this->attachments_json) ? $this->attachments_json : [];
        foreach ($attachments['data'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $candidate = trim((string) data_get($item, 'media.image.src', ''));
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'media.source', ''));
            }
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}
