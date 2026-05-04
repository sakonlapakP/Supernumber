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
            'owner' => 'เจ้าของธุรกิจ / ผู้ประกอบการ',
            'manager' => 'ผู้บริหาร / หัวหน้างาน',
            'freelance' => 'ฟรีแลนซ์ / อาชีพอิสระ',
            'finance' => 'บัญชี / การเงิน / ธนาคาร',
            'real_estate' => 'อสังหา / นายหน้า',
            'government' => 'งานราชการ / รัฐวิสาหกิจ',
            'health_beauty' => 'แพทย์ / สุขภาพ / ความงาม',
            'technical' => 'ช่าง / วิศวกรรม / เทคนิค',
            'logistics' => 'ขนส่ง / โลจิสติกส์',
            'student' => 'นักเรียน / นักศึกษา',
            'sales' => 'งานขาย / เจรจา',
            'service' => 'งานบริการ / ดูแลลูกค้า',
            'office' => 'งานออฟฟิศ / ธุรการ',
            'online' => 'งานออนไลน์ / คอนเทนต์',
        ];
    }

    public static function goalLabels(): array
    {
        return [
            'work' => 'การงาน',
            'money' => 'การเงิน',
            'love' => 'ความรัก',
            'health' => 'สุขภาพ',
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
