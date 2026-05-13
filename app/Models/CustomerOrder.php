<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

use App\Traits\UnixTimestampSerializable;

class CustomerOrder extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Deprecated statuses (kept for mapping old records)
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_PAID = 'paid';
    public const STATUS_SOLD = 'sold';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'phone_number_id',
        'ordered_number',
        'service_type',
        'selected_package',
        'package_id',
        'package_code',
        'package_name',
        'monthly_price',
        'initial_payment_price',
        'title_prefix',
        'first_name',
        'last_name',
        'email',
        'current_phone',
        'shipping_address_line',
        'district',
        'amphoe',
        'province',
        'zipcode',
        'appointment_date',
        'appointment_time_slot',
        'payment_slip_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'phone_number_id' => 'integer',
            'service_type' => 'string',
            'selected_package' => 'integer',
            'package_id' => 'integer',
            'monthly_price' => 'integer',
            'initial_payment_price' => 'integer',
            'appointment_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }


    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            $order->service_type = self::normalizeServiceType($order->service_type);

            $normalizedStatus = self::normalizeStatus($order->status);
            $order->status = $normalizedStatus !== ''
                ? $normalizedStatus
                : self::defaultStatusForServiceType($order->service_type);

            if (! in_array($order->status, self::statusOptions(), true)) {
                throw new InvalidArgumentException(sprintf('Unsupported customer order status [%s].', $order->status));
            }
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PROCESSING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function statusLabelOptions(): array
    {
        return [
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_COMPLETED => 'สำเร็จ',
            self::STATUS_CANCELLED => 'ยกเลิก',
            // Mapping for old statuses
            self::STATUS_SUBMITTED => 'กำลังดำเนินการ (ใหม่)',
            self::STATUS_PENDING_REVIEW => 'กำลังดำเนินการ (รอตรวจ)',
            self::STATUS_REVIEWING => 'กำลังดำเนินการ (ตรวจอยู่)',
            self::STATUS_PAID => 'กำลังดำเนินการ (จ่ายแล้ว)',
            self::STATUS_SOLD => 'สำเร็จ (ขายแล้ว)',
            self::STATUS_REJECTED => 'ยกเลิก (ปฏิเสธ)',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabelOptions()[$this->status] ?? $this->status;
    }

    public static function defaultStatusForServiceType(?string $serviceType): string
    {
        return self::STATUS_PROCESSING;
    }

    public static function resolvePhoneNumberStatus(?string $orderStatus): ?string
    {
        return match (self::normalizeStatus($orderStatus)) {
            self::STATUS_COMPLETED,
            self::STATUS_SOLD => PhoneNumber::STATUS_SOLD,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED => PhoneNumber::STATUS_ACTIVE,
            self::STATUS_PROCESSING,
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_REVIEWING,
            self::STATUS_PAID => PhoneNumber::STATUS_HOLD,
            default => null,
        };
    }

    public function getIsPostpaidAttribute(): bool
    {
        return $this->service_type !== PhoneNumber::SERVICE_TYPE_PREPAID;
    }

    public function getIsPrepaidAttribute(): bool
    {
        return $this->service_type === PhoneNumber::SERVICE_TYPE_PREPAID;
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return $this->is_prepaid ? 'เติมเงิน' : 'รายเดือน';
    }

    public function getPaymentLabelAttribute(): string
    {
        $amount = $this->initial_payment_amount;

        if ($amount === null || $amount <= 0) {
            return '-';
        }

        return number_format($amount) . ' บาท';
    }

    public function getInitialPaymentAmountAttribute(): ?int
    {
        $amount = $this->initial_payment_price ?? $this->selected_package;

        return is_numeric($amount) && (int) $amount > 0 ? (int) $amount : null;
    }

    public function getMonthlyPaymentLabelAttribute(): string
    {
        $amount = $this->monthly_price ?? $this->package?->monthly_price;

        return is_numeric($amount) && (int) $amount > 0
            ? number_format((int) $amount) . ' บาท / เดือน'
            : '-';
    }

    public function getPackageLabelAttribute(): string
    {
        $name = trim((string) ($this->package_name ?? $this->package?->name ?? ''));
        $price = $this->monthly_price ?? $this->package?->monthly_price;

        if ($name !== '' && is_numeric($price) && (int) $price > 0) {
            if (preg_match('/(?:^|\s)' . preg_quote((string) (int) $price, '/') . '$/u', $name) === 1) {
                return $name;
            }

            return $name . ' ' . (int) $price;
        }

        if ($name !== '') {
            return $name;
        }

        return is_numeric($price) && (int) $price > 0 ? (string) (int) $price : '-';
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->title_prefix,
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function getShippingAddressAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->shipping_address_line,
            $this->district,
            $this->amphoe,
            $this->province,
            $this->zipcode,
        ])));
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(PhonePackage::class, 'package_id');
    }

    public function lineNotificationLogs(): MorphMany
    {
        return $this->morphMany(LineNotificationLog::class, 'notifiable');
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerOrderActivityLog::class);
    }

    private static function normalizeServiceType(?string $serviceType): string
    {
        return trim(strtolower((string) $serviceType)) === PhoneNumber::SERVICE_TYPE_PREPAID
            ? PhoneNumber::SERVICE_TYPE_PREPAID
            : PhoneNumber::SERVICE_TYPE_POSTPAID;
    }

    private static function normalizeStatus(?string $status): string
    {
        return strtolower(trim((string) $status));
    }
}
