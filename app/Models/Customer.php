<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'company_name',
        'first_name',
        'last_name',
        'tax_id',
        'address',
        'email',
        'phone',
        'payment_term',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        $companyName = trim((string) $this->company_name);

        if ($companyName !== '') {
            return $companyName;
        }

        return $this->contact_name;
    }

    public function getContactNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }
}
