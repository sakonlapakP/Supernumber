<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\UnixTimestampSerializable;

class LotteryResultPrize extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $fillable = [
        'lottery_result_id',
        'position',
        'prize_name',
        'prize_number',
    ];

    public function result(): BelongsTo
    {
        return $this->belongsTo(LotteryResult::class, 'lottery_result_id');
    }
}
