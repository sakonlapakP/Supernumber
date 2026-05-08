<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\UnixTimestampSerializable;

class PairMeaning extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $fillable = [
        'pair',
        'status',
        'short_meaning',
        'long_meaning',
    ];
}
