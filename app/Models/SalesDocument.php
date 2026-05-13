<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

use App\Traits\UnixTimestampSerializable;

class SalesDocument extends Model
{
    use \App\Traits\UnixTimestampSerializable;

    protected $fillable = [
        'document_type',
        'document_number',
        'document_date',
        'due_date',
        'customer_id',
        'customer_name',
        'file_name',
        'pdf_disk',
        'pdf_path',
        'saved_by_user_id',
        'payload',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function billingCustomer(): BelongsTo
    {
        return $this->belongsTo(BillingCustomer::class, 'customer_id');
    }

    public function getFileExistsAttribute(): bool
    {
        $disk = $this->pdf_disk ?: 'local';

        return Storage::disk($disk)->exists((string) $this->pdf_path);
    }
}
