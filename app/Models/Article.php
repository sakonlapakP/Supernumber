<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\UnixTimestampSerializable;
use App\Services\ArticleContentSanitizer;

class Article extends Model
{
    use HasFactory;
    use UnixTimestampSerializable;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image_path',
        'cover_image_landscape_path',
        'cover_image_square_path',
        'image_guidelines',
        'meta_description',
        'keywords',
        'lsi_keywords',
        'is_published',
        'is_auto_post',
        'is_line_broadcasted',
        'published_at',
        'notified_at',
        'view_count',
        'author_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_auto_post' => 'boolean',
            'is_line_broadcasted' => 'boolean',
            'image_guidelines' => 'array',
            'published_at' => 'datetime',
            'notified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(function (Builder $inner): void {
                $inner
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now('Asia/Bangkok'));
            });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('status', ArticleComment::STATUS_APPROVED);
    }

    public function plan(): HasOne
    {
        return $this->hasOne(ArticlePlan::class);
    }

    /**
     * Resolve the public URL for the article's cover image.
     * When the article was created via content refresh, uses the plan's publish_date
     * for the storage path year; otherwise falls back to published_at.
     */
    public function getCoverImageUrl(): ?string
    {
        $path = (string) ($this->cover_image_path ?? '');
        if ($path === '') {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public function sanitizedContent(): string
    {
        return app(ArticleContentSanitizer::class)->sanitize((string) $this->content);
    }
}
