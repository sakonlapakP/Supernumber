<?php

namespace App\Models;

use App\Traits\UnixTimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhonePackage extends Model
{
    use UnixTimestampSerializable;

    protected $fillable = [
        'code',
        'service_type',
        'network_code',
        'name',
        'monthly_price',
        'data_quota',
        'speed_after_quota',
        'voice_minutes',
        'benefits',
        'conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class, 'package_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'package_id');
    }

    public function getNetworkLabelAttribute(): string
    {
        return PhoneNumber::networkLabel($this->network_code);
    }

    public function getMonthlyPriceLabelAttribute(): string
    {
        $price = (int) $this->monthly_price;

        return $price > 0 ? number_format($price) . ' บาท / เดือน' : '-';
    }

    public function getConditionsListAttribute(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->conditions) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
