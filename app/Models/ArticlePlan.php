<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ArticlePlan extends Model
{
    protected $fillable = [
        'publish_date',
        'publish_time',
        'type',
        'topic',
        'is_lottery',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'is_lottery' => 'boolean',
    ];

    /**
     * Check if the plan date has passed (for automatic success status)
     */
    public function isPast(): bool
    {
        return Carbon::parse($this->publish_date)->isPast();
    }
}
