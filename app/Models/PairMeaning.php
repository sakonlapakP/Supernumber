<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PairMeaning extends Model
{
    protected $fillable = [
        'pair',
        'status',
        'short_meaning',
        'long_meaning',
    ];
}
