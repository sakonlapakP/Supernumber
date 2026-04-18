<?php

namespace App\Services;

use App\Models\SalesDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use RuntimeException;

class SalesDocumentPdfService
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function renderDocumentHtml(SalesDocument $document, array $options = []): string
    {
        return View::make('admin.sales-document-pdf', $this->buildViewData($document, $options))->render();
    }

    public function saveDocument(array $data, ?int $savedByUserId = null): SalesDocument
    {
        $documentType = $data['document_type'] === 'invoice' ? 'invoice' : 'quotation';
        $documentNumber = trim((string) ($data['document_number'] ?? ''));

        if ($documentNumber === '') {
            throw new RuntimeException('ไม่พบเลขที่เอกสารสำหรับบันทึก PDF');
        }

        $documentDate = $this->resolveDateValue($data['document_date'] ?? null);
        $dueDate = $this->resolveDateValue($data['due_date'] ?? null);
        $year = $documentDate?->format('Y') ?? now('Asia/Bangkok')->format('Y');
        $fileName = $this->buildFileName($documentType, $documentNumber);
        $relativePdfPath = $this->buildRelativePdfPath($documentType, $year, $fileName);

        $document = SalesDocument::query()->firstOrNew([
            'document_type' => $documentType,
            'document_number' => $documentNumber,
        ]);

        $document->fill([
            'document_date' => $documentDate?->toDateString(),
            'due_date' => $dueDate?->toDateString(),
            'customer_id' => $data['customer_id'] ?? null,
            'customer_name' => $this->trimNullable($data['customer_name'] ?? null),
            'file_name' => $fileName,
            'pdf_disk' => 'local',
            'pdf_path' => $relativePdfPath,
            'saved_by_user_id' => $savedByUserId,
            'payload' => $data,
        ]);
        $document->save();

        return $document->fresh();
    }

    protected function buildFileName(string $documentType, string $documentNumber): string
    {
        $prefix = $documentType === 'invoice' ? 'Invoice' : 'Quotation';
        $sanitizedNumber = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '-', $documentNumber) ?? $documentNumber);

        return trim($prefix . ' ' . $sanitizedNumber);
    }

    protected function buildRelativePdfPath(string $documentType, string $year, string $fileName): string
    {
        $directory = $documentType === 'invoice' ? 'invoice' : 'qoutation';

        return $directory . '/' . $year . '/' . $fileName . '.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildViewData(SalesDocument $document, array $options = []): array
    {
        return [
            'document' => $document,
            'payload' => $document->payload ?? [],
            'logoUrl' => asset('images/supernumber-document-logo.png'),
            'showPrintToolbar' => (bool) ($options['showPrintToolbar'] ?? false),
            'autoPrint' => (bool) ($options['autoPrint'] ?? false),
            'printButtonLabel' => trim((string) ($options['printButtonLabel'] ?? 'บันทึก PDF')),
        ];
    }

    protected function resolveDateValue(mixed $value): ?Carbon
    {
        $trimmed = trim((string) ($value ?? ''));

        if ($trimmed === '') {
            return null;
        }

        return Carbon::parse($trimmed, 'Asia/Bangkok');
    }

    protected function trimNullable(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed !== '' ? $trimmed : null;
    }
}
