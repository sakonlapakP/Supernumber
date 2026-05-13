<?php

namespace App\Models;

use App\Traits\UnixTimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSubmission extends Model
{
    use UnixTimestampSerializable;

    public const FORM_EVALUATE = 'evaluate';
    public const FORM_ESTIMATE = 'estimate';
    public const FORM_CONTACT = 'contact';

    protected $fillable = [
        'customer_id',
        'form_type',
        'name',
        'phone',
        'email',
        'payload',
        'consent_dev',
        'consent_marketing',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'consent_dev' => 'boolean',
            'consent_marketing' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function billingCustomer(): BelongsTo
    {
        return $this->belongsTo(BillingCustomer::class, 'customer_id');
    }

    public static function formTypeLabels(): array
    {
        return [
            self::FORM_EVALUATE => 'วิเคราะห์เบอร์',
            self::FORM_ESTIMATE => 'วิเคราะห์เบอร์ที่เหมาะกับคุณ',
            self::FORM_CONTACT => 'ติดต่อ',
        ];
    }

    public function getFormTypeLabelAttribute(): string
    {
        return static::formTypeLabels()[$this->form_type] ?? $this->form_type;
    }
}
