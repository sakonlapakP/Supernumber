<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerOrder extends Model
{
    protected $fillable = [
        'ordered_number',
        'service_type',
        'selected_package',
        'title_prefix',
        'first_name',
        'last_name',
        'email',
        'current_phone',
        'shipping_address_line',
        'district',
        'amphoe',
        'province',
        'zipcode',
        'appointment_date',
        'appointment_time_slot',
        'payment_slip_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'service_type' => 'string',
            'selected_package' => 'integer',
            'appointment_date' => 'date',
        ];
    }

    public function getIsPostpaidAttribute(): bool
    {
        return $this->service_type !== PhoneNumber::SERVICE_TYPE_PREPAID;
    }

    public function getIsPrepaidAttribute(): bool
    {
        return $this->service_type === PhoneNumber::SERVICE_TYPE_PREPAID;
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return $this->is_prepaid ? 'เติมเงิน' : 'รายเดือน';
    }

    public function getPaymentLabelAttribute(): string
    {
        $amount = (int) $this->selected_package;

        if ($amount <= 0) {
            return '-';
        }

        if ($this->is_prepaid) {
            return number_format($amount) . ' บาท';
        }

        return number_format($amount) . ' บาท / เดือน';
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->title_prefix,
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function getShippingAddressAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->shipping_address_line,
            $this->district,
            $this->amphoe,
            $this->province,
            $this->zipcode,
        ])));
    }

    public function lineNotificationLogs(): MorphMany
    {
        return $this->morphMany(LineNotificationLog::class, 'notifiable');
    }
}
