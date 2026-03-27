<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryResult extends Model
{
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
            'draw_date' => 'date',
            'source_draw_date' => 'date',
            'is_complete' => 'boolean',
            'fetched_at' => 'datetime',
            'source_payload' => 'array',
        ];
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(LotteryResultPrize::class)->orderBy('position')->orderBy('id');
    }
}
