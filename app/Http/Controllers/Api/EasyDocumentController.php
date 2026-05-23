<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingCustomer;
use App\Models\SalesDocument;
use App\Services\EasyDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EasyDocumentController extends Controller
{
    public function __construct(
        private EasyDocumentService $easyDocumentService
    ) {}

    public function listCustomers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = BillingCustomer::query()
            ->where('is_active', true)
            ->orderByRaw('LOWER(COALESCE(company_name, first_name, last_name, ""))');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('company_name', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->limit(100)->get()->map(fn (BillingCustomer $customer): array => [
            'id' => $customer->id,
            'display_name' => $customer->display_name,
            'company_name' => $customer->company_name,
            'contact_name' => $customer->contact_name,
            'tax_id' => $customer->tax_id,
            'address' => $customer->address,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'payment_term' => $customer->payment_term,
        ]);

        return response()->json(['customers' => $customers]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $companyName = trim((string) ($data['company_name'] ?? ''));
        $contactName = trim((string) ($data['contact_name'] ?? ''));

        if ($companyName === '' && $contactName === '') {
            return response()->json([
                'message' => 'กรุณาระบุชื่อบริษัทหรือชื่อผู้ติดต่ออย่างน้อยหนึ่งค่า',
            ], 422);
        }

        [$firstName, $lastName] = $this->splitContactName($contactName);

        $customer = BillingCustomer::create([
            'company_name' => $companyName !== '' ? $companyName : null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tax_id' => trim((string) ($data['tax_id'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'is_active' => true,
        ]);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'display_name' => $customer->display_name,
                'company_name' => $customer->company_name,
                'contact_name' => $customer->contact_name,
                'tax_id' => $customer->tax_id,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'payment_term' => $customer->payment_term,
            ],
        ], 201);
    }

    public function searchQuotations(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $query = SalesDocument::query()
            ->where('document_type', SalesDocument::TYPE_QUOTATION)
            ->whereIn('status', [
                SalesDocument::STATUS_QUOTATION_DRAFT,
                SalesDocument::STATUS_QUOTATION_SENT,
                SalesDocument::STATUS_QUOTATION_ACCEPTED,
            ])
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('document_number', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }

        $quotations = $query->limit(50)->get()->map(fn (SalesDocument $quotation): array => [
            'id' => $quotation->id,
            'document_number' => $quotation->document_number,
            'customer_name' => $quotation->customer_name,
            'document_date' => $quotation->document_date?->format('Y-m-d'),
            'status' => $quotation->status,
            'status_label' => $quotation->status_label,
            'grand_total' => data_get($quotation->payload, 'totals.grand_total'),
            'grand_total_display' => data_get($quotation->payload, 'totals.grand_total_display'),
        ]);

        return response()->json(['quotations' => $quotations]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customerId' => ['required', 'integer'],
            'documentType' => ['nullable', 'in:quotation,invoice'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.originalPrice' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'taxMethod' => ['required', 'in:customer-pays,we-pay'],
            'paymentMethod' => ['required', 'in:bank,qr,cash'],
            'paymentCondition' => ['required', 'in:full,installment,specific-date'],
            'paymentDetail' => ['nullable', 'string'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:255'],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $this->easyDocumentService->create($data, Auth::id());
            $document = $result['document'];

            return response()->json([
                'success' => true,
                'message' => 'สร้างเอกสารสำเร็จ',
                'document' => [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'document_number' => $document->document_number,
                    'status' => $document->status,
                    'status_label' => $document->status_label,
                    'is_draft' => $document->is_draft,
                ],
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitContactName(string $contactName): array
    {
        $trimmed = trim($contactName);

        if ($trimmed === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+/', $trimmed, 2);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }
}
