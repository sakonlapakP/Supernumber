<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ArticlePlan extends Model
{
    use HasFactory;
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_CANCELLED = 'cancelled';

    public const REFRESH_STATUS_DRAFT = 'draft';
    public const REFRESH_STATUS_CREATED = 'created';
    public const REFRESH_STATUS_REFRESHED = 'refreshed';
    public const REFRESH_STATUS_DONE = 'done';

    protected $fillable = [
        'publish_date',
        'publish_time',
        'type',
        'topic',
        'is_lottery',
        'status',
        'assigned_to',
        'due_date',
        'blocked_reason',
        'notes',
        'article_id',
        'last_refreshed_at',
        'refresh_status',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'due_date' => 'date',
        'is_lottery' => 'boolean',
        'last_refreshed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('publish_date', [
            Carbon::today(),
            Carbon::today()->addDays($days),
        ]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('publish_date', '<', Carbon::today())
            ->whereNotIn('status', [self::STATUS_DONE, self::STATUS_CANCELLED]);
    }

    public function scopeForMonth(Builder $query, Carbon $date): Builder
    {
        return $query->whereBetween('publish_date', [
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth(),
        ]);
    }

    public function scopeForWeek(Builder $query, Carbon $date): Builder
    {
        return $query->whereBetween('publish_date', [
            $date->copy()->startOfWeek(),
            $date->copy()->endOfWeek(),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isPast(): bool
    {
        return Carbon::parse($this->publish_date)->isPast();
    }
}
