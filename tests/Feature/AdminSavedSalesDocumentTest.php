<?php

namespace Tests\Feature;

use App\Models\SalesDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AdminSavedSalesDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_and_download_sales_document(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-saved-document',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $payload = [
            'document_type' => 'quotation',
            'document_number' => 'QT-260403-001',
            'document_date' => '2026-04-03',
            'due_date' => '2026-04-10',
            'customer_name' => 'บริษัท ทดสอบ จำกัด',
            'payload' => [
                'document_type' => 'quotation',
                'document_number' => 'QT-260403-001',
                'customer_name' => 'บริษัท ทดสอบ จำกัด',
                'items' => [],
            ],
        ];

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.sales-documents.save-download'), $payload);

        $response->assertOk();
        $response->assertJsonPath('file_name', 'Quotation QT-260403-001.pdf');
        $this->assertDatabaseHas('sales_documents', [
            'document_type' => 'quotation',
            'document_number' => 'QT-260403-001',
            'pdf_path' => 'quotation/2026/Quotation QT-260403-001.pdf',
            'status' => SalesDocument::STATUS_QUOTATION_DRAFT,
        ]);
    }

    public function test_admin_can_view_saved_sales_documents_page(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'username' => 'admin-saved-documents-index',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $document = SalesDocument::query()->create([
            'document_type' => 'invoice',
            'document_number' => 'IV-260403-001',
            'document_date' => '2026-04-03',
            'due_date' => '2026-04-10',
            'customer_name' => 'บริษัท เช็กเอกสาร จำกัด',
            'file_name' => 'Invoice IV-260403-001',
            'pdf_disk' => 'local',
            'pdf_path' => 'invoice/2026/Invoice IV-260403-001.pdf',
            'saved_by_user_id' => $admin->id,
            'payload' => [],
        ]);

        Storage::disk('local')->put($document->pdf_path, 'pdf');

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.saved-sales-documents.index'));

        $response->assertOk();
        $response->assertSee('รายการใบเสนอราคา / ใบแจ้งหนี้ทั้งหมด');
        $response->assertSee('สร้างใบเสนอราคา');
        $response->assertSee('สร้างใบแจ้งหนี้');
        $response->assertSee('IV-260403-001');
        $response->assertDontSee('ที่เก็บไฟล์');
        $response->assertDontSee('สถานะไฟล์');
        $response->assertDontSee('ลบ');
    }

    public function test_admin_can_accept_quotation_and_convert_it_to_invoice(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-quotation-conversion',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $quotation = SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-260522-001',
            'document_date' => '2026-05-22',
            'due_date' => '2026-05-29',
            'customer_name' => 'บริษัท แปลงเอกสาร จำกัด',
            'file_name' => 'Quotation QT-260522-001',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-260522-001.pdf',
            'status' => SalesDocument::STATUS_QUOTATION_SENT,
            'payload' => [
                'document_type' => 'quotation',
                'document_number' => 'QT-260522-001',
                'document_date' => '2026-05-22',
                'due_date' => '2026-05-29',
                'customer_name' => 'บริษัท แปลงเอกสาร จำกัด',
                'document' => [
                    'type' => 'quotation',
                    'title_th' => 'ใบเสนอราคา',
                    'title_en' => 'Quotation',
                    'number' => 'QT-260522-001',
                ],
                'items' => [],
            ],
        ]);

        $this
            ->withSession($this->adminSession($admin))
            ->post(route('admin.saved-sales-documents.quotation-status', [$quotation, 'accept']))
            ->assertRedirect();

        $quotation->refresh();
        $this->assertSame(SalesDocument::STATUS_QUOTATION_ACCEPTED, $quotation->status);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->post(route('admin.saved-sales-documents.convert-to-invoice', $quotation));

        $invoice = SalesDocument::query()
            ->where('document_type', SalesDocument::TYPE_INVOICE)
            ->sole();

        $response->assertRedirect(route('admin.saved-sales-documents.show', $invoice));
        $this->assertSame($quotation->id, $invoice->source_quotation_id);
        $this->assertStringStartsWith('IV-', $invoice->document_number);
        $this->assertSame(SalesDocument::STATUS_INVOICE_DRAFT, $invoice->status);
    }

    public function test_saved_sales_documents_page_shows_created_date_and_creator(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'username' => 'admin-creator',
            'name' => 'Admin Creator',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $document = SalesDocument::query()->create([
            'document_type' => 'invoice',
            'document_number' => 'IV-260405-001',
            'document_date' => '2026-04-05',
            'customer_name' => 'บริษัท ทดสอบวันสร้าง จำกัด',
            'file_name' => 'Invoice IV-260405-001',
            'pdf_disk' => 'local',
            'pdf_path' => 'invoice/2026/Invoice IV-260405-001.pdf',
            'saved_by_user_id' => $admin->id,
            'payload' => [],
            'created_at' => now(),
        ]);

        Storage::disk('local')->put($document->pdf_path, 'pdf');

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.saved-sales-documents.index'));

        $response->assertOk();
        $response->assertSee('IV-260405-001');
        $response->assertSee('สร้างเมื่อ / สร้างโดย');
        $response->assertSee($admin->name);
    }

    public function test_manager_can_delete_saved_sales_document(): void
    {
        Storage::fake('local');

        $manager = User::factory()->create([
            'username' => 'manager-saved-documents-delete',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $document = SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-260404-001',
            'document_date' => '2026-04-04',
            'due_date' => '2026-04-11',
            'customer_name' => 'บริษัท ลบเอกสาร จำกัด',
            'file_name' => 'Quotation QT-260404-001',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-260404-001.pdf',
            'saved_by_user_id' => $manager->id,
            'payload' => [],
        ]);

        Storage::disk('local')->put($document->pdf_path, 'pdf');

        $response = $this
            ->withSession($this->adminSession($manager))
            ->delete(route('admin.saved-sales-documents.delete', $document));

        $response->assertRedirect(route('admin.saved-sales-documents.index'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('sales_documents', [
            'id' => $document->id,
        ]);
        Storage::disk('local')->assertMissing($document->pdf_path);
    }

    public function test_admin_hides_instead_of_deleting_sales_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'username' => 'admin-hides-document',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $document = SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-260404-002',
            'document_date' => '2026-04-04',
            'due_date' => '2026-04-11',
            'customer_name' => 'บริษัท ห้ามลบ จำกัด',
            'file_name' => 'Quotation QT-260404-002',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-260404-002.pdf',
            'saved_by_user_id' => $admin->id,
            'payload' => [],
            'is_active' => true,
        ]);

        Storage::disk('local')->put($document->pdf_path, 'pdf');

        $response = $this
            ->withSession($this->adminSession($admin))
            ->delete(route('admin.saved-sales-documents.delete', $document));

        $response->assertRedirect(route('admin.saved-sales-documents.index'));
        $response->assertSessionHas('status_message', 'ซ่อนเอกสารเรียบร้อยแล้ว (แอดมินไม่มีสิทธิ์ลบถาวร)');

        $this->assertDatabaseHas('sales_documents', [
            'id' => $document->id,
            'is_active' => false,
        ]);

        // File should still exist because it was only hidden
        Storage::disk('local')->assertExists($document->pdf_path);
    }

    public function test_admin_can_view_saved_sales_document_preview_route(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-saved-documents-preview',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $document = SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-260403-002',
            'document_date' => '2026-04-03',
            'due_date' => '2026-04-10',
            'customer_name' => 'บริษัท พรีวิว จำกัด',
            'file_name' => 'Quotation QT-260403-002',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-260403-002.pdf',
            'saved_by_user_id' => $admin->id,
            'payload' => [
                'company' => [
                    'name_th' => 'บริษัท ซุปเปอร์นัมเบอร์ จำกัด (สำนักงานใหญ่)',
                    'name_en' => 'SUPERNUMBER CO.,LTD.',
                    'address' => '1418 ถนนพระรามที่ 4 แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110',
                    'tax_id' => '0105557133568',
                ],
                'document' => [
                    'title_th' => 'ใบเสนอราคา',
                    'title_en' => 'Quotation',
                    'date_display' => '03/04/2026',
                ],
                'customer' => [
                    'name' => 'บริษัท พรีวิว จำกัด',
                ],
                'items' => [],
                'totals' => [],
                'payment' => [],
                'signatures' => [],
            ],
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.saved-sales-documents.preview', $document));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('SUPERNUMBER CO.,LTD.');
        $response->assertSee('ใบเสนอราคา');
    }

    public function test_admin_can_open_saved_document_in_editor(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-saved-documents-edit',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $document = SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-260403-003',
            'document_date' => '2026-04-03',
            'due_date' => '2026-04-10',
            'customer_name' => 'บริษัท แก้ไขเอกสาร จำกัด',
            'file_name' => 'Quotation QT-260403-003',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-260403-003.pdf',
            'saved_by_user_id' => $admin->id,
            'payload' => [
                'document_type' => 'quotation',
                'document_number' => 'QT-260403-003',
                'customer_name' => 'บริษัท แก้ไขเอกสาร จำกัด',
                'customer' => [
                    'name' => 'บริษัท แก้ไขเอกสาร จำกัด',
                ],
                'items' => [],
            ],
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents', ['document' => $document->id]));

        $response->assertOk();
        $response->assertSee('QT-260403-003');
        $response->assertSee('prefillPayload');
    }

    public function test_sales_document_pdf_view_renders_without_merge_markers(): void
    {
        $document = SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-260403-001',
            'document_date' => '2026-04-03',
            'due_date' => '2026-04-10',
            'customer_name' => 'บริษัท เช็กวิว จำกัด',
            'file_name' => 'Quotation QT-260403-001',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-260403-001.pdf',
            'payload' => [],
        ]);

        $html = View::make('admin.sales-document-pdf', [
            'document' => $document,
            'payload' => [
                'company' => [
                    'name_th' => 'บริษัท ซุปเปอร์นัมเบอร์ จำกัด (สำนักงานใหญ่)',
                    'name_en' => 'SUPERNUMBER CO.,LTD.',
                    'address' => '1418 ถนนพระรามที่ 4 แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110',
                    'tax_id' => '0105557133568',
                ],
                'document' => [
                    'title_th' => 'ใบเสนอราคา',
                    'title_en' => 'Quotation',
                    'date_display' => '03/04/2026',
                ],
                'customer' => [
                    'name' => 'บริษัท เช็กวิว จำกัด',
                ],
                'items' => [],
                'totals' => [],
                'payment' => [],
                'signatures' => [],
            ],
            'embeddedCss' => '',
            'logoDataUri' => null,
        ])->render();

        $this->assertStringNotContainsString('<<<<<<<', $html);
        $this->assertStringNotContainsString('>>>>>>>', $html);
    }

    public function test_saved_sales_documents_page_displays_separate_tables_without_type_column(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-saved-tables-test',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        SalesDocument::query()->create([
            'document_type' => 'quotation',
            'document_number' => 'QT-111111-111',
            'document_date' => '2026-04-03',
            'due_date' => '2026-04-10',
            'customer_name' => 'บริษัท ทดสอบเสนอราคา จำกัด',
            'file_name' => 'Quotation QT-111111-111',
            'pdf_disk' => 'local',
            'pdf_path' => 'quotation/2026/Quotation QT-111111-111.pdf',
            'saved_by_user_id' => $admin->id,
            'payload' => [],
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.saved-sales-documents.index'));

        $response->assertOk();
        $response->assertSee('รายการใบเสนอราคา (Quotations)');
        $response->assertSee('รายการใบแจ้งหนี้ (Invoices)');
        $response->assertDontSee('<th>ประเภท</th>', false);
        $response->assertDontSee('ประเภท / เลขที่');
        $response->assertSee('จัดการ ▾');
    }

    public function test_editor_route_handles_quotation_type_correctly(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-editor-type-q',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents', ['type' => 'quotation']));

        $response->assertOk();
        // The script should set the document type dynamically:
        $response->assertSee('setDocumentType("quotation")', false);
        // The switch should be hidden:
        $response->assertSee('class="document-type-switch" role="group" aria-label="เลือกประเภทเอกสาร" style="display: none !important;"', false);
    }

    public function test_editor_route_handles_invoice_type_correctly(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-editor-type-i',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents', ['type' => 'invoice']));

        $response->assertOk();
        // The script should set the document type dynamically:
        $response->assertSee('setDocumentType("invoice")', false);
    }

    public function test_dashboard_displays_separate_easy_document_buttons(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-btns',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.saved-sales-documents.index'));

        $response->assertOk();
        // Check for separate Easy Quotation and Easy Invoice buttons
        $response->assertSee('data-easy-docs-open="quotation"', false);
        $response->assertSee('data-easy-docs-open="invoice"', false);
        $response->assertSee('✨ Easy Quotation');
        $response->assertSee('✨ Easy Invoice');
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
