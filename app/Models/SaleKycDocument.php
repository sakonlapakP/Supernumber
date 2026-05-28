<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleKycDocument extends Model
{
    public const TYPE_NATIONAL_ID = 'national_id';
    public const TYPE_BANK_BOOK   = 'bank_book';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'sale_kyc_documents';

    protected $fillable = [
        'user_id',
        'document_type',
        'file_path',
        'original_name',
        'status',
        'rejection_reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            self::TYPE_NATIONAL_ID => 'สำเนาบัตรประชาชน',
            self::TYPE_BANK_BOOK   => 'สมุดบัญชีธนาคาร',
            default                => $this->document_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'รอตรวจสอบ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_REJECTED => 'ไม่ผ่าน',
            default               => $this->status,
        };
    }
}
