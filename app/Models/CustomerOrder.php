<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    protected $fillable = [
        'ordered_number',
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
            'selected_package' => 'integer',
            'appointment_date' => 'date',
        ];
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
}

