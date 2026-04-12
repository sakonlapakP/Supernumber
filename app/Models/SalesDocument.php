<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SalesDocument extends Model
{
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
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'payload' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFileExistsAttribute(): bool
    {
        $disk = $this->pdf_disk ?: 'local';

        return Storage::disk($disk)->exists((string) $this->pdf_path);
    }
}
