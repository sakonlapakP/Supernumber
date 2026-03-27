<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EstimateLead extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'birthday',
        'work_type',
        'current_phone',
        'main_phone',
        'email',
        'goal',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function lineNotificationLogs(): MorphMany
    {
        return $this->morphMany(LineNotificationLog::class, 'notifiable');
    }
}
