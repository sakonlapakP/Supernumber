<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'user_id',
        'tier_level',
        'percentage_applied',
        'calculated_amount',
        'status',
        'period',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'tier_level'         => 'integer',
            'percentage_applied' => 'decimal:2',
            'calculated_amount'  => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'รอตรวจสอบ',
            self::STATUS_APPROVED => 'อนุมัติ',
            self::STATUS_REJECTED => 'ไม่อนุมัติ',
            default               => $this->status,
        };
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->tier_level) {
            1 => 'ขายตรง (25%)',
            2 => 'ชั้น 2 (15%)',
            3 => 'ชั้น 3 (10%)',
            default => "ชั้น {$this->tier_level}",
        };
    }
}
