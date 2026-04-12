<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use InvalidArgumentException;

class CustomerOrder extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SOLD = 'sold';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'phone_number_id',
        'ordered_number',
        'service_type',
        'selected_package',
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
            'appointment_date' => 'date',
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
            self::STATUS_SUBMITTED,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_REVIEWING,
            self::STATUS_PAID,
            self::STATUS_COMPLETED,
            self::STATUS_SOLD,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function defaultStatusForServiceType(?string $serviceType): string
    {
        return self::normalizeServiceType($serviceType) === PhoneNumber::SERVICE_TYPE_PREPAID
            ? self::STATUS_PENDING_REVIEW
            : self::STATUS_SUBMITTED;
    }

    public static function resolvePhoneNumberStatus(?string $orderStatus): ?string
    {
        return match (self::normalizeStatus($orderStatus)) {
            self::STATUS_SOLD,
            self::STATUS_COMPLETED => PhoneNumber::STATUS_SOLD,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED => PhoneNumber::STATUS_ACTIVE,
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
        $amount = (int) $this->selected_package;

        if ($amount <= 0) {
            return '-';
        }

        if ($this->is_prepaid) {
            return number_format($amount) . ' บาท';
        }

        return number_format($amount) . ' บาท / เดือน';
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

    public function lineNotificationLogs(): MorphMany
    {
        return $this->morphMany(LineNotificationLog::class, 'notifiable');
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
