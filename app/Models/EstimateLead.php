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

    public static function genderLabels(): array
    {
        return [
            'male' => 'ชาย',
            'female' => 'หญิง',
        ];
    }

    public static function workTypeLabels(): array
    {
        return [
            'sales' => 'งานขาย / เจรจา',
            'service' => 'งานบริการ / ดูแลลูกค้า',
            'office' => 'งานออฟฟิศ / บริหาร',
            'online' => 'งานออนไลน์ / คอนเทนต์',
        ];
    }

    public static function goalLabels(): array
    {
        return [
            'work' => 'เน้นการงาน / โอกาสใหม่',
            'money' => 'เน้นการเงิน / ปิดการขาย',
            'love' => 'เน้นความรัก / ความสัมพันธ์',
            'balance' => 'เน้นสมดุลชีวิต',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function getGenderLabelAttribute(): string
    {
        return static::genderLabels()[$this->gender] ?? '-';
    }

    public function getWorkTypeLabelAttribute(): string
    {
        return static::workTypeLabels()[$this->work_type] ?? '-';
    }

    public function getGoalLabelAttribute(): string
    {
        return static::goalLabels()[$this->goal] ?? '-';
    }
}
