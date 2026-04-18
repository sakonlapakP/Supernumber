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
    public const PACKAGE_PRICE_STANDARD = 1199;
    public const PACKAGE_PRICE_PREMIUM = 1499;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HOLD = 'hold';
    public const STATUS_SOLD = 'sold';
    public const TOPIC_ICON_MAP = [
        'การสื่อสาร' => '💬',
        'ความรัก/เสน่ห์' => '💖',
        'การงาน/ความก้าวหน้า' => '💼',
        'การเงิน/โชคลาภ' => '💰',
        'ภาวะผู้นำ/อำนาจ' => '👑',
        'ความคิดสร้างสรรค์/ไอเดีย' => '💡',
        'สติปัญญา/การเรียนรู้' => '🧠',
        'สุขภาพ/ความเครียด' => '🌿',
        'ศาสตร์เร้นลับ/ลางสังหรณ์' => '✨',
    ];

    protected $fillable = [
        'phone_number',
        'display_number',
        'number_sum',
        'service_type',
        'network_code',
        'plan_name',
        'price_text',
        'sale_price',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $phoneNumber): void {
            $serviceType = self::normalizeServiceType($phoneNumber->service_type);
            $phoneNumber->service_type = $serviceType ?? self::SERVICE_TYPE_POSTPAID;

            $status = strtolower(trim((string) $phoneNumber->status));
            $phoneNumber->status = in_array($status, self::adminStatusOptions(), true)
                ? $status
                : self::STATUS_ACTIVE;

            if ($phoneNumber->number_sum === null || $phoneNumber->isDirty('phone_number')) {
                $phoneNumber->number_sum = self::calculateNumberSum($phoneNumber->phone_number);
            }
        });
    }

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

    public function getInitialPaymentLabelAttribute(): string
    {
        $price = self::packagePrice($this->sale_price);

        if ($price === null || $this->is_prepaid) {
            return '-';
        }

        return number_format($price) . ' บาท เดือนละ ' . number_format($price) . '/เดือน';
    }

    public function getInitialPaymentHtmlAttribute(): string
    {
        $price = self::packagePrice($this->sale_price);

        if ($price === null || $this->is_prepaid) {
            return '-';
        }

        $priceText = number_format($price);

        return $priceText . ' บาท <strong class="card-meta-price-strong">เดือนละ ' . $priceText . '/เดือน</strong>';
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

    public function getSupportedTopicIconsAttribute(): array
    {
        return self::buildSupportedTopicIcons($this->phone_number);
    }

    public static function buildSupportedTopicIcons(?string $phoneNumber): array
    {
        $digits = self::digitsOnly($phoneNumber);
        if (strlen($digits) < 7) {
            return [];
        }

        $lastSeven = substr($digits, -7);
        $pairs = [];
        for ($index = 0; $index < 6; $index++) {
            $pairs[] = substr($lastSeven, $index, 2);
        }

        $lastPairIndex = count($pairs) - 1;
        $weightedPairs = [];
        foreach ($pairs as $index => $pair) {
            $weightedPairs[] = [
                'variants' => self::buildPairVariants($pair),
                'weight' => $index === $lastPairIndex ? 2 : 1,
            ];
        }

        $supportedTopics = [];
        foreach (self::topicPairMap() as $topic => $topicPairs) {
            $goodWeight = 0.0;
            $conditionalWeight = 0.0;
            $badWeight = 0.0;

            foreach ($weightedPairs as $weightedPair) {
                $variants = $weightedPair['variants'];
                $weight = $weightedPair['weight'];

                if (count(array_intersect($variants, $topicPairs['good'])) > 0) {
                    $goodWeight += $weight;
                    continue;
                }

                if (count(array_intersect($variants, $topicPairs['conditional'])) > 0) {
                    $conditionalWeight += $weight;
                    continue;
                }

                if (count(array_intersect($variants, $topicPairs['bad'])) > 0) {
                    $badWeight += $weight;
                }
            }

            if (($goodWeight + ($conditionalWeight * 0.5)) <= $badWeight) {
                continue;
            }

            $supportedTopics[] = [
                'topic' => $topic,
                'icon' => self::TOPIC_ICON_MAP[$topic] ?? '•',
            ];
        }

        return $supportedTopics;
    }

    protected static function buildPairVariants(string $pair): array
    {
        $chars = str_split($pair);
        sort($chars);
        $normalized = implode('', $chars);

        return array_values(array_unique([$pair, strrev($pair), $normalized]));
    }

    protected static function topicPairMap(): array
    {
        return [
            'การสื่อสาร' => [
                'good' => ['14', '22', '23', '24', '32', '41', '42', '44', '45', '46', '49', '54', '64'],
                'bad' => ['03', '04', '05', '06', '12', '13', '18', '21', '27', '30', '31', '34', '40', '43', '48', '50', '57', '60', '72', '75', '81', '84'],
                'conditional' => ['33', '47', '74'],
            ],
            'ความรัก/เสน่ห์' => [
                'good' => ['22', '23', '24', '26', '28', '29', '32', '35', '36', '41', '42', '44', '46', '62', '63', '64', '66', '69'],
                'bad' => ['00', '02', '06', '08', '12', '20', '21', '25', '27', '34', '37', '38', '43', '52', '57', '58', '60', '67', '68', '72', '73', '75', '76', '80', '83', '85', '86', '88'],
                'conditional' => ['33'],
            ],
            'การงาน/ความก้าวหน้า' => [
                'good' => ['14', '15', '16', '19', '23', '24', '26', '28', '29', '32', '35', '36', '39', '41', '42', '44', '45', '46', '49', '51', '53', '54', '55', '56', '59', '61', '62', '63', '64', '65'],
                'bad' => ['00', '01', '02', '03', '04', '07', '08', '09', '10', '11', '12', '13', '17', '18', '20', '21', '25', '27', '30', '31', '34', '37', '38', '40', '43', '48', '52', '57', '58', '67', '68', '70', '71', '72', '73', '75', '76', '77', '80', '81', '83', '84', '85', '86', '88', '90'],
                'conditional' => ['33', '47', '74'],
            ],
            'การเงิน/โชคลาภ' => [
                'good' => ['28', '78', '82', '87'],
                'bad' => ['01', '02', '04', '06', '10', '12', '18', '20', '21', '25', '27', '34', '37', '40', '43', '52', '58', '60', '67', '68', '72', '73', '76', '81', '85', '86', '88'],
                'conditional' => ['47', '74'],
            ],
            'ภาวะผู้นำ/อำนาจ' => [
                'good' => ['35', '53', '39', '93', '28', '82', '78', '87', '89', '98'],
                'bad' => ['08', '80', '88'],
                'conditional' => ['47', '74'],
            ],
            'ความคิดสร้างสรรค์/ไอเดีย' => [
                'good' => ['19', '29', '69', '91', '92', '96'],
                'bad' => [],
                'conditional' => [],
            ],
            'สติปัญญา/การเรียนรู้' => [
                'good' => ['14', '15', '41', '44', '45', '49', '51', '54', '55'],
                'bad' => [],
                'conditional' => [],
            ],
            'สุขภาพ/ความเครียด' => [
                'good' => ['15', '24', '29', '42', '45', '51', '54', '59', '69', '92', '95', '99'],
                'bad' => ['00', '01', '02', '03', '04', '05', '06', '07', '09', '10', '11', '12', '13', '17', '20', '21', '25', '27', '30', '31', '34', '37', '40', '43', '48', '50', '52', '57', '58', '60', '70', '71', '72', '73', '75', '77', '84', '85', '90'],
                'conditional' => ['47', '74'],
            ],
            'ศาสตร์เร้นลับ/ลางสังหรณ์' => [
                'good' => ['49', '59', '79', '89', '94', '95', '97', '98', '99'],
                'bad' => ['00', '09', '90'],
                'conditional' => [],
            ],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, min: ?int, max: ?int}>
     */
    public static function prepaidPriceRanges(): array
    {
        return [
            ['value' => 'prepaid_under_5000', 'label' => 'น้อยกว่า 5,000', 'min' => null, 'max' => 5000],
            ['value' => 'prepaid_5000_10000', 'label' => '5,000 - 10,000', 'min' => 5000, 'max' => 10000],
            ['value' => 'prepaid_10000_20000', 'label' => '10,000 - 20,000', 'min' => 10000, 'max' => 20000],
            ['value' => 'prepaid_20000_30000', 'label' => '20,000 - 30,000', 'min' => 20000, 'max' => 30000],
            ['value' => 'prepaid_30000_50000', 'label' => '30,000 - 50,000', 'min' => 30000, 'max' => 50000],
            ['value' => 'prepaid_50000_100000', 'label' => '50,000 - 100,000', 'min' => 50000, 'max' => 100001],
            ['value' => 'prepaid_over_100000', 'label' => 'มากกว่า 100,000', 'min' => 100000, 'max' => null],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function prepaidPriceRangeOptions(): array
    {
        return array_map(
            static fn (array $range): array => [
                'value' => $range['value'],
                'label' => $range['label'],
            ],
            self::prepaidPriceRanges()
        );
    }

    /**
     * @return array{value: string, label: string, min: ?int, max: ?int}|null
     */
    public static function resolvePrepaidPriceRange(?string $value): ?array
    {
        $value = trim((string) $value);

        foreach (self::prepaidPriceRanges() as $range) {
            if ($range['value'] === $value) {
                return $range;
            }
        }

        return null;
    }

    public static function serviceTypeOptions(): array
    {
        return [
            self::SERVICE_TYPE_POSTPAID,
            self::SERVICE_TYPE_PREPAID,
        ];
    }

    public static function calculateNumberSum(mixed $phoneNumber): ?int
    {
        $digits = self::digitsOnly($phoneNumber);

        if ($digits === '') {
            return null;
        }

        return array_sum(array_map(
            static fn (string $digit): int => (int) $digit,
            str_split($digits)
        ));
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

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
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
