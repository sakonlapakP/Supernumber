<?php

namespace App\Models;

use App\Traits\UnixTimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SalesDocument extends Model
{
    use UnixTimestampSerializable;

    // Document types
    public const TYPE_QUOTATION = 'quotation';

    public const TYPE_INVOICE = 'invoice';

    // Quotation statuses
    public const STATUS_QUOTATION_DRAFT = 'quotation_draft';

    public const STATUS_QUOTATION_SENT = 'quotation_sent';

    public const STATUS_QUOTATION_ACCEPTED = 'quotation_accepted';

    public const STATUS_QUOTATION_REJECTED = 'quotation_rejected';

    public const STATUS_QUOTATION_EXPIRED = 'quotation_expired';

    public const STATUS_QUOTATION_CANCELLED = 'quotation_cancelled';

    // Invoice statuses
    public const STATUS_INVOICE_DRAFT = 'invoice_draft';

    public const STATUS_INVOICE_ISSUED = 'invoice_issued';

    public const STATUS_INVOICE_PARTIALLY_PAID = 'invoice_partially_paid';

    public const STATUS_INVOICE_PAID = 'invoice_paid';

    public const STATUS_INVOICE_OVERDUE = 'invoice_overdue';

    public const STATUS_INVOICE_VOID = 'invoice_void';

    /**
     * @return list<string>
     */
    public static function quotationStatuses(): array
    {
        return [
            self::STATUS_QUOTATION_DRAFT,
            self::STATUS_QUOTATION_SENT,
            self::STATUS_QUOTATION_ACCEPTED,
            self::STATUS_QUOTATION_REJECTED,
            self::STATUS_QUOTATION_EXPIRED,
            self::STATUS_QUOTATION_CANCELLED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function invoiceStatuses(): array
    {
        return [
            self::STATUS_INVOICE_DRAFT,
            self::STATUS_INVOICE_ISSUED,
            self::STATUS_INVOICE_PARTIALLY_PAID,
            self::STATUS_INVOICE_PAID,
            self::STATUS_INVOICE_OVERDUE,
            self::STATUS_INVOICE_VOID,
        ];
    }

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
        'is_draft',
        'status',
        'source_quotation_id',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'payload' => 'array',
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalesDocument $document): void {
            $document->status = $document->normalizedStatus();

            if (! in_array($document->status, $document->allowedStatuses(), true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unsupported sales document status [%s] for type [%s].',
                    $document->status,
                    $document->document_type
                ));
            }
        });
    }

    public function billingCustomer(): BelongsTo
    {
        return $this->belongsTo(BillingCustomer::class, 'customer_id');
    }

    public function savedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by_user_id');
    }

    public function sourceQuotation(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'source_quotation_id');
    }

    public function convertedInvoice(): HasOne
    {
        return $this->hasOne(SalesDocument::class, 'source_quotation_id')
            ->where('document_type', self::TYPE_INVOICE);
    }

    public function getFileExistsAttribute(): bool
    {
        $disk = $this->pdf_disk ?: 'local';

        return Storage::disk($disk)->exists((string) $this->pdf_path);
    }

    // Type checking helpers
    public function isQuotation(): bool
    {
        return $this->document_type === self::TYPE_QUOTATION;
    }

    public function isInvoice(): bool
    {
        return $this->document_type === self::TYPE_INVOICE;
    }

    // Quotation status helpers
    public function isQuotationDraft(): bool
    {
        return $this->isQuotation() && $this->status === self::STATUS_QUOTATION_DRAFT;
    }

    public function isQuotationAccepted(): bool
    {
        return $this->isQuotation() && $this->status === self::STATUS_QUOTATION_ACCEPTED;
    }

    public function isQuotationSent(): bool
    {
        return $this->isQuotation() && $this->status === self::STATUS_QUOTATION_SENT;
    }

    // Invoice status helpers
    public function isInvoiceDraft(): bool
    {
        return $this->isInvoice() && $this->status === self::STATUS_INVOICE_DRAFT;
    }

    public function isInvoiceIssued(): bool
    {
        return $this->isInvoice() && $this->status === self::STATUS_INVOICE_ISSUED;
    }

    public function isInvoicePaid(): bool
    {
        return $this->isInvoice() && $this->status === self::STATUS_INVOICE_PAID;
    }

    public function isInvoiceEditable(): bool
    {
        return $this->isInvoiceDraft();
    }

    public function isQuotationEditable(): bool
    {
        return $this->isQuotationDraft();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_QUOTATION_DRAFT => 'ร่างใบเสนอราคา',
            self::STATUS_QUOTATION_SENT => 'ส่งใบเสนอราคาแล้ว',
            self::STATUS_QUOTATION_ACCEPTED => 'ลูกค้ายอมรับแล้ว',
            self::STATUS_QUOTATION_REJECTED => 'ถูกปฏิเสธ',
            self::STATUS_QUOTATION_EXPIRED => 'หมดอายุ',
            self::STATUS_QUOTATION_CANCELLED => 'ยกเลิก',
            self::STATUS_INVOICE_DRAFT => 'ร่างใบแจ้งหนี้',
            self::STATUS_INVOICE_ISSUED => 'ออกใบแจ้งหนี้แล้ว',
            self::STATUS_INVOICE_PARTIALLY_PAID => 'ชำระบางส่วน',
            self::STATUS_INVOICE_PAID => 'ชำระแล้ว',
            self::STATUS_INVOICE_OVERDUE => 'ค้างชำระ',
            self::STATUS_INVOICE_VOID => 'ยกเลิกใบแจ้งหนี้',
            default => (string) $this->status,
        };
    }

    /**
     * @return list<string>
     */
    private function allowedStatuses(): array
    {
        return $this->isInvoice()
            ? self::invoiceStatuses()
            : self::quotationStatuses();
    }

    private function normalizedStatus(): string
    {
        $status = trim((string) $this->status);

        if ($status !== '' && $status !== 'draft') {
            return $status;
        }

        return $this->isInvoice()
            ? self::STATUS_INVOICE_DRAFT
            : self::STATUS_QUOTATION_DRAFT;
    }
}
