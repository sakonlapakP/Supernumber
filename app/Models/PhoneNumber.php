<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhoneNumber extends Model
{
    public const PHONE_NUMBER_LENGTH = 10;
    public const PREFIX_LENGTH = 3;
    public const SERVICE_TYPE_POSTPAID = 'postpaid';
    public const SERVICE_TYPE_PREPAID = 'prepaid';
    public const PACKAGE_NAME = 'True Super Value';
    public const PACKAGE_PRICE_BASIC = 699;
    public const PACKAGE_PRICE_STANDARD = 1099;
    public const PACKAGE_PRICE_PREMIUM = 1499;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HOLD = 'hold';
    public const STATUS_SOLD = 'sold';

    protected $fillable = [
        'phone_number',
        'display_number',
        'service_type',
        'network_code',
        'plan_name',
        'price_text',
        'sale_price',
        'status',
    ];

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->whereNotNull('sale_price')
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeMatchingPattern(Builder $query, ?string $pattern): Builder
    {
        if ($pattern === null) {
            return $query;
        }

        return $query->where('phone_number', 'like', $pattern);
    }

    public function scopeOfServiceType(Builder $query, ?string $serviceType): Builder
    {
        $serviceType = self::normalizeServiceType($serviceType);

        if ($serviceType === null) {
            return $query;
        }

        return $query->where('service_type', $serviceType);
    }

    public static function buildSearchPattern(array $filters): ?string
    {
        $positions = array_fill(1, self::PHONE_NUMBER_LENGTH, '_');
        $prefix = self::digitsOnly($filters['prefix'] ?? '');

        foreach (str_split(substr($prefix, 0, self::PREFIX_LENGTH)) as $index => $digit) {
            $positions[$index + 1] = $digit;
        }

        for ($position = 1; $position <= self::PHONE_NUMBER_LENGTH; $position++) {
            $digit = self::firstDigit(
                $filters["p{$position}"]
                    ?? $filters["d{$position}"]
                    ?? $filters["position_{$position}"]
                    ?? ''
            );

            if ($digit !== '') {
                $positions[$position] = $digit;
            }
        }

        $pattern = implode('', $positions);

        if ($pattern === str_repeat('_', self::PHONE_NUMBER_LENGTH)) {
            return null;
        }

        return $pattern;
    }

    public function getPackageLabelAttribute(): string
    {
        return self::buildPackageLabel($this->plan_name, $this->sale_price);
    }

    public function getOfferLabelAttribute(): string
    {
        if ($this->is_prepaid) {
            $price = self::packagePrice($this->sale_price);

            return $price !== null ? number_format($price) . ' บาท' : 'เบอร์เติมเงิน';
        }

        return $this->package_label;
    }

    public function getPaymentLabelAttribute(): string
    {
        $price = self::packagePrice($this->sale_price);

        if ($price === null) {
            return '-';
        }

        if ($this->is_prepaid) {
            return number_format($price) . ' บาท';
        }

        return number_format($price) . ' บาท / เดือน';
    }

    public function getNormalizedPackagePriceAttribute(): ?int
    {
        if (! $this->is_postpaid) {
            return null;
        }

        return self::normalizePackagePrice($this->sale_price);
    }

    public function getIsPostpaidAttribute(): bool
    {
        return self::normalizeServiceType($this->service_type) !== self::SERVICE_TYPE_PREPAID;
    }

    public function getIsPrepaidAttribute(): bool
    {
        return self::normalizeServiceType($this->service_type) === self::SERVICE_TYPE_PREPAID;
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return $this->is_prepaid ? 'เติมเงิน' : 'รายเดือน';
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->normalized_package_price) {
            self::PACKAGE_PRICE_PREMIUM => 'Platinum',
            self::PACKAGE_PRICE_STANDARD => 'Gold',
            self::PACKAGE_PRICE_BASIC => 'Bronze',
            default => '-',
        };
    }

    public function getTierClassAttribute(): string
    {
        return match ($this->normalized_package_price) {
            self::PACKAGE_PRICE_PREMIUM => 'card-tier--platinum',
            self::PACKAGE_PRICE_STANDARD => 'card-tier--gold',
            self::PACKAGE_PRICE_BASIC => 'card-tier--silver',
            default => 'card-tier--silver',
        };
    }

    public static function buildPackageLabel(?string $planName, mixed $salePrice): string
    {
        $planName = trim((string) $planName);
        $price = self::packagePrice($salePrice);

        if ($planName !== '' && $price !== null) {
            return $planName . ' ' . $price;
        }

        if ($planName !== '') {
            return $planName;
        }

        if ($price !== null) {
            return (string) $price;
        }

        return '-';
    }

    public static function parsePackageLabel(?string $label): array
    {
        $label = trim((string) $label);

        if ($label === '') {
            return [null, null];
        }

        if (preg_match('/^\d+$/', $label) === 1) {
            return [null, self::packagePrice($label)];
        }

        if (preg_match('/^(.*)\s+(\d+)$/u', $label, $matches) === 1) {
            $planName = trim($matches[1]);

            return [$planName !== '' ? $planName : null, self::packagePrice($matches[2])];
        }

        return [$label, null];
    }

    public static function packageLabelsForQuery(Builder $query): array
    {
        return (clone $query)
            ->select('plan_name', 'sale_price')
            ->distinct()
            ->orderBy('sale_price')
            ->orderBy('plan_name')
            ->get()
            ->map(fn (self $phoneNumber) => self::buildPackageLabel($phoneNumber->plan_name, $phoneNumber->sale_price))
            ->reject(fn (string $label) => $label === '-')
            ->values()
            ->all();
    }

    public static function adminStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_HOLD,
            self::STATUS_SOLD,
        ];
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PhoneNumberStatusLog::class);
    }

    protected static function firstDigit(mixed $value): string
    {
        return substr(self::digitsOnly($value), 0, 1);
    }

    protected static function digitsOnly(mixed $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits ?? '';
    }

    protected static function normalizePackagePrice(mixed $salePrice): ?int
    {
        if (! is_numeric($salePrice)) {
            return null;
        }

        $price = (int) $salePrice;

        if ($price >= self::PACKAGE_PRICE_PREMIUM) {
            return self::PACKAGE_PRICE_PREMIUM;
        }

        if ($price >= self::PACKAGE_PRICE_STANDARD) {
            return self::PACKAGE_PRICE_STANDARD;
        }

        return self::PACKAGE_PRICE_BASIC;
    }

    protected static function packagePrice(mixed $salePrice): ?int
    {
        if (! is_numeric($salePrice)) {
            return null;
        }

        $price = (int) $salePrice;

        return $price > 0 ? $price : null;
    }

    protected static function normalizeServiceType(?string $serviceType): ?string
    {
        $serviceType = strtolower(trim((string) $serviceType));

        return match ($serviceType) {
            self::SERVICE_TYPE_POSTPAID,
            self::SERVICE_TYPE_PREPAID => $serviceType,
            default => null,
        };
    }
}
