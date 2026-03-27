<?php

namespace App\Services;

use App\Models\EstimateLead;
use App\Models\LineNotificationLog;
use Illuminate\Support\Carbon;

class LineEstimateLeadNotifier
{
    public function __construct(
        private readonly LineNotifier $lineNotifier,
    ) {
    }

    public function sendSubmitted(EstimateLead $lead): ?LineNotificationLog
    {
        return $this->lineNotifier->queueText(
            eventType: 'estimate_submitted',
            message: $this->buildMessage($lead),
            notifiable: $lead,
            destinationKey: 'estimate',
        );
    }

    private function buildMessage(EstimateLead $lead): string
    {
        $fullName = trim(implode(' ', array_filter([
            $lead->first_name,
            $lead->last_name,
        ]))) ?: '-';

        $genderLabels = [
            'male' => 'ชาย',
            'female' => 'หญิง',
        ];

        $workTypeLabels = [
            'sales' => 'งานขาย / เจรจา',
            'service' => 'งานบริการ / ดูแลลูกค้า',
            'office' => 'งานออฟฟิศ / บริหาร',
            'online' => 'งานออนไลน์ / คอนเทนต์',
        ];

        $goalLabels = [
            'work' => 'เน้นการงาน / โอกาสใหม่',
            'money' => 'เน้นการเงิน / ปิดการขาย',
            'love' => 'เน้นความรัก / ความสัมพันธ์',
            'balance' => 'เน้นสมดุลชีวิต',
        ];

        $submittedAt = $lead->submitted_at ?? $lead->created_at;
        $submittedAtText = $submittedAt instanceof Carbon
            ? $submittedAt->copy()->timezone('Asia/Bangkok')->format('Y-m-d H:i')
            : '-';

        return implode("\n", [
            'มีลูกค้าใหม่กรอกแบบฟอร์มเลือกเบอร์',
            'Lead #' . $lead->id,
            'ชื่อ: ' . $fullName,
            'เบอร์หลัก: ' . ($lead->main_phone ?: '-'),
            'เบอร์ปัจจุบัน: ' . ($lead->current_phone ?: '-'),
            'อีเมล: ' . ($lead->email ?: '-'),
            'เพศ: ' . ($genderLabels[$lead->gender] ?? '-'),
            'วันเกิด: ' . ($lead->birthday?->format('Y-m-d') ?: '-'),
            'ลักษณะงาน: ' . ($workTypeLabels[$lead->work_type] ?? '-'),
            'เป้าหมาย: ' . ($goalLabels[$lead->goal] ?? '-'),
            'ส่งข้อมูลเมื่อ: ' . $submittedAtText,
        ]);
    }
}
