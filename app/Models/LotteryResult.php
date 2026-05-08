<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

use App\Traits\UnixTimestampSerializable;

class LotteryResult extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $fillable = [
        'draw_date',
        'source_draw_date',
        'source_draw_date_text',
        'is_complete',
        'fetched_at',
        'source_payload',
    ];

    protected function casts(): array
    {
        return [
            'is_complete' => 'boolean',
            'fetched_at' => 'datetime',
            'source_payload' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }


    protected function drawDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $this->asDateOnlyCarbon($value),
            set: fn (mixed $value): ?string => $this->normalizeDateOnly($value),
        );
    }

    protected function sourceDrawDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?Carbon => $this->asDateOnlyCarbon($value),
            set: fn (mixed $value): ?string => $this->normalizeDateOnly($value),
        );
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(LotteryResultPrize::class)->orderBy('position')->orderBy('id');
    }

    private function asDateOnlyCarbon(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    private function normalizeDateOnly(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value, 'UTC')
                ->timezone(config('app.timezone', 'Asia/Bangkok'))
                ->toDateString();
        }

        if (trim((string) $value) === '') {
            return null;
        }

        return Carbon::parse((string) $value)->toDateString();
    }
}
