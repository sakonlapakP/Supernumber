# Sales Documents System — ระบบออกใบเสนอราคาและใบแจ้งหนี้

**Last Updated:** May 20, 2026  
**Module:** Quotation & Invoice Management  
**Language:** Thai & English support  

---

## 📋 Overview

ระบบ Sales Documents เป็นระบบจัดการเอกสารขายที่ช่วยให้ Admin สามารถ:
- ✅ สร้างใบเสนอราคา (Quotation) และใบแจ้งหนี้ (Invoice)
- ✅ บันทึกเอกสารลงฐานข้อมูล พร้อมเก็บข้อมูลแบบ JSON
- ✅ ดาวน์โหลดเป็นไฟล์ PDF
- ✅ จัดการเอกสารที่บันทึกไว้ (ดู แก้ลบ)

---

## 🏗️ Architecture

### Components

```
┌─────────────────────────────────────────────────────┐
│  Frontend Layer                                     │
│  - sales-documents.blade.php  (Editor form)         │
│  - sales-documents-index.blade.php (List view)      │
│  - sales-document-pdf.blade.php (PDF template)      │
└────────────────┬────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────┐
│  Route Layer (routes/web.php)                       │
│  - GET /admin/sales-documents                       │
│  - POST /admin/sales-documents/save-download        │
│  - GET /admin/saved-sales-documents                 │
│  - GET /admin/saved-sales-documents/{id}            │
│  - DELETE /admin/saved-sales-documents/{id}         │
└────────────────┬────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────┐
│  Service Layer                                      │
│  - SalesDocumentPdfService                          │
│    ├── saveDocument()                               │
│    ├── renderDocumentHtml()                         │
│    ├── buildFileName()                              │
│    └── buildRelativePdfPath()                       │
└────────────────┬────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────┐
│  Data Layer                                         │
│  - SalesDocument Model                              │
│  - BillingCustomer Model (ลูกค้า)                  │
│  - Database: sales_documents table                  │
│  - Storage: storage/app/private/                    │
└─────────────────────────────────────────────────────┘
```

---

## 🗄️ Database Schema

### Table: `sales_documents`

```sql
CREATE TABLE sales_documents (
    id BIGINT PRIMARY KEY,
    document_type VARCHAR(20),        -- 'quotation' | 'invoice'
    document_number VARCHAR(255),     -- เลขที่เอกสาร (unique per type)
    document_date DATE,               -- วันที่เอกสาร
    due_date DATE NULLABLE,           -- วันครบกำหนด (invoices)
    customer_id BIGINT NULLABLE,      -- FK: billing_customers.id
    customer_name VARCHAR(255),       -- ชื่อลูกค้า
    file_name VARCHAR(255),           -- ชื่อไฟล์ (ถูก sanitize)
    pdf_disk VARCHAR(255),            -- disk name ('local')
    pdf_path VARCHAR(255),            -- relative path ใน storage
    payload JSON,                     -- เอกสารทั้งหมดเป็น JSON
    saved_by_user_id BIGINT NULLABLE, -- ใครบันทึก
    is_active BOOLEAN DEFAULT 1,      -- soft delete flag
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Composite unique constraint
UNIQUE (document_type, document_number)
```

### Payload Structure (JSON)

```json
{
  "items": [
    {
      "description": "สินค้า 1",
      "quantity": 5,
      "unit_price": 100.00,
      "amount": 500.00
    }
  ],
  "company": {
    "name_th": "บริษัท สุปเปอร์นัมเบอร์",
    "name_en": "Supernumber Co., Ltd.",
    "address": "123 ถนนพระราม 4, กรุงเทพ",
    "tax_id": "0123456789012",
    "phone": "02-XXXXXXX",
    "email": "contact@supernumber.com"
  },
  "customer": {
    "contact_name": "นายสมชาย",
    "company_name": "ABC Trading",
    "tax_id": "0987654321098",
    "address": "456 ถนน...",
    "phone": "08-XXXXXXXX",
    "email": "buyer@abc.com"
  },
  "document": {
    "title_th": "ใบเสนอราคา",
    "title_en": "Quotation",
    "number": "QT-260520-001"
  },
  "payment": {
    "term": "Net 30",
    "method": "โอนเงินเข้าบัญชี"
  },
  "totals": {
    "subtotal": 500.00,
    "tax_rate": 0.07,
    "tax_amount": 35.00,
    "total": 535.00
  },
  "signatures": {
    "seller_name": "นาย...",
    "seller_title": "ผู้บริหาร"
  }
}
```

---

## 🔄 User Flow

### สร้างเอกสารใหม่

```
1. Admin คลิก "/admin/sales-documents"
   ↓
2. ระบบโหลด:
   - รายชื่อลูกค้าจาก BillingCustomer (active only)
   - form template สำหรับกรอกข้อมูล
   ↓
3. Admin กรอก:
   - ประเภท: Quotation / Invoice
   - เลขที่เอกสาร (ต้องไม่ซ้ำ)
   - วันที่เอกสาร
   - เลือกลูกค้า
   - รายการสินค้า (add/remove)
   - ข้อมูลชำระเงิน
   - ลายเซ็นต์
   ↓
4. คลิก "บันทึก PDF"
   ↓
5. POST /admin/sales-documents/save-download
   - Validate input
   - Call SalesDocumentPdfService::saveDocument()
   - บันทึก record ลง DB
   - Return JSON:
     {
       "message": "บันทึก Invoice เรียบร้อยแล้ว...",
       "download_url": "...",
       "show_url": "...",
       "file_name": "Invoice 260520-001.pdf"
     }
   ↓
6. Frontend:
   - เปิดหน้า print preview
   - Auto-trigger print dialog
   - ผู้ใช้กด "บันทึก PDF" ในเบราวเซอร์
```

### ดูเอกสารที่บันทึกไว้

```
1. GET /admin/saved-sales-documents
   ↓
2. ระบบแสดงตาราง:
   - เลขที่เอกสาร
   - ประเภท (Quotation/Invoice)
   - ลูกค้า
   - วันที่บันทึก
   - Actions (ดู แก้ ลบ)
   ↓
3. คลิกแถว → GET /admin/saved-sales-documents/{id}
   ↓
4. ระบบแสดง:
   - Preview รูปหน้าเอกสาร
   - ฟอร์ม editable สำหรับแก้ไข
   - Buttons: ดาวน์โหลด, ปิด, ลบ
```

### ลบเอกสาร

```
1. DELETE /admin/saved-sales-documents/{id}
   ↓
2. ระบบ:
   - ตรวจสอบ permission (manager/admin)
   - ถ้า admin → เซ็ต is_active = false (soft delete)
   - ถ้า manager → ลบจริง
   ↓
3. Redirect ไป saved-sales-documents.index
```

---

## 📁 File Storage

### ระบบจัดเก็บไฟล์

```
storage/app/private/
├── invoice/
│   ├── 2026/
│   │   ├── Invoice 260520-001.pdf
│   │   ├── Invoice 260521-002.pdf
│   │   └── ...
│   ├── 2027/
│   └── ...
├── quotation/
│   ├── 2026/
│   │   ├── Quotation QT-001.pdf
│   │   ├── Quotation QT-002.pdf
│   │   └── ...
│   ├── 2027/
│   └── ...
└── tmp/
    └── sales-documents/  ← temp storage สำหรับ upload
```

### Path Building Logic

```php
// app/Services/SalesDocumentPdfService.php

protected function buildFileName($documentType, $documentNumber) {
    $prefix = $documentType === 'invoice' ? 'Invoice' : 'Quotation';
    $sanitized = preg_replace('/[\\/:*?"<>|]+/', '-', $documentNumber);
    return trim("$prefix $sanitized");
}

// Returns: "Invoice 260520-001" หรือ "Quotation QT-001"

protected function buildRelativePdfPath($documentType, $year, $fileName) {
    $dir = $documentType === 'invoice' ? 'invoice' : 'quotation'; // ✅ Fixed typo
    return "$dir/$year/$fileName.pdf";
}

// Returns: "quotation/2026/Quotation QT-001.pdf"
```

---

## 🔌 API Endpoints

### GET /admin/sales-documents

สร้างหรือแก้ไขเอกสาร

**Query Parameters:**
- `document` (optional): ID ของเอกสารที่ต้องการแก้ไข

**Response:**
```
Blade view: sales-documents.blade.php
Variables:
  - customers: Collection<BillingCustomer>
  - prefillPayload: array|null (ข้อมูลเดิมถ้าแก้ไข)
```

---

### POST /admin/sales-documents/save-download

บันทึกเอกสารลงฐานข้อมูล

**Request Body (JSON):**
```json
{
  "document_type": "quotation|invoice",  // required
  "document_number": "QT-260520-001",    // required, unique
  "document_date": "2026-05-20",         // required
  "due_date": "2026-06-20",              // optional
  "customer_id": 5,                      // optional
  "customer_name": "นายสมชาย",           // optional
  "items": [...],
  "company": {...},
  "customer": {...},
  "document": {...},
  "payment": {...},
  "totals": {...},
  "signatures": {...}
}
```

**Response (JSON):**
```json
{
  "message": "บันทึก Quotation เรียบร้อยแล้ว กำลังเปิดหน้าพิมพ์สำหรับบันทึก PDF",
  "download_url": "/admin/saved-sales-documents/123/download",
  "show_url": "/admin/saved-sales-documents/123",
  "file_name": "Quotation QT-260520-001.pdf",
  "pdf_path": "quotation/2026/Quotation QT-260520-001.pdf"
}
```

**Error Response:**
```json
{
  "message": "Unauthorized" // 401
}
// หรือ validation errors
```

---

### GET /admin/saved-sales-documents

แสดงรายการเอกสารที่บันทึก

**Response:**
```
Blade view: admin.sales-documents-index
Variables:
  - documents: Collection<SalesDocument> (เฉพาะ is_active = true)
```

---

### GET /admin/saved-sales-documents/{id}

ดูรายละเอียดและแก้ไขเอกสาร

**Response:**
```
Blade view: admin.sales-document-show
Variables:
  - salesDocument: SalesDocument
  - customers: Collection<BillingCustomer>
```

---

### GET /admin/saved-sales-documents/{id}/preview

ดูตัวอย่าง PDF ก่อนบันทึก

**Query Parameters:**
- `print_preview=1` (auto-detected)

**Response:**
```
HTML page with print preview
- แสดงเอกสารแบบเต็มหน้า
- Toolbar: กลับ, พิมพ์
- Auto-trigger print dialog
```

---

### GET /admin/saved-sales-documents/{id}/download

ดาวน์โหลดไฟล์ PDF

**Response:**
```
Content-Type: application/pdf
Content-Disposition: attachment; filename="Invoice 260520-001.pdf"
[Binary PDF content]
```

---

### DELETE /admin/saved-sales-documents/{id}

ลบเอกสาร

**Authorization:**
- Admin: soft delete (is_active = false)
- Manager: hard delete (ลบจริง)

**Response (Redirect):**
```
→ /admin/saved-sales-documents (ด้วย success message)
```

---

## 🛠️ Service: SalesDocumentPdfService

### Methods

#### `saveDocument(array $data, ?int $savedByUserId): SalesDocument`

บันทึกข้อมูลเอกสารลงฐานข้อมูล

```php
$service = app(SalesDocumentPdfService::class);
$document = $service->saveDocument([
    'document_type' => 'quotation',
    'document_number' => 'QT-001',
    'document_date' => '2026-05-20',
    'due_date' => '2026-06-20',
    'customer_id' => 5,
    'customer_name' => 'ABC Trading',
    'items' => [...],
    'company' => [...],
    'customer' => [...],
    'document' => [...],
    'payment' => [...],
    'totals' => [...],
    'signatures' => [...]
], auth()->id());

// Returns: SalesDocument instance
```

**Logic:**
1. Validate document_number (ต้องไม่ว่าง)
2. สร้าง file name: "Quotation QT-001"
3. สร้าง relative path: "quotation/2026/Quotation QT-001.pdf"
4. ค้นหาหรือสร้าง SalesDocument record
5. บันทึกลง DB พร้อม payload JSON
6. Return fresh instance

**Throws:**
- `RuntimeException` ถ้า document_number ว่าง

---

#### `renderDocumentHtml(SalesDocument $document, array $options): string`

สร้าง HTML สำหรับแสดงผล/พิมพ์

```php
$html = $service->renderDocumentHtml($document, [
    'showPrintToolbar' => true,
    'autoPrint' => true,
    'printButtonLabel' => 'บันทึก PDF'
]);
```

**Options:**
- `showPrintToolbar`: แสดง/ซ่อน toolbar
- `autoPrint`: auto-trigger print dialog
- `printButtonLabel`: label ของ print button

---

## 🎨 Frontend

### Views Structure

#### `resources/views/sales-documents.blade.php`

หน้าหลักสำหรับสร้าง/แก้ไขเอกสาร

**Features:**
- WYSIWYG editor
- Real-time preview
- Line items management
- Customer selector
- Signature inputs

**Data Flow:**
```
PHP (backend) → data_get() สำหรับ JSON payload
Blade → Alpine.js สำหรับ form state
Alpine → fetch() ส่ง POST /sales-documents/save-download
Response → open print preview
```

---

#### `resources/views/admin/sales-document-pdf.blade.php`

Template สำหรับแสดง PDF

**Sections:**
1. Document header (company info, document type)
2. Document details (number, date, due date)
3. Customer info
4. Line items table
5. Totals section
6. Terms & conditions
7. Signature area

**CSS:**
- `css/supernumber.css` (global)
- `css/sales-document-pdf.css` (print-specific)

---

#### `resources/views/admin/sales-documents-index.blade.php`

Listing page สำหรับเอกสารที่บันทึก

**Table Columns:**
- Checkbox (select multiple)
- Document Number
- Type (Quotation/Invoice)
- Customer
- Date
- Created By
- Actions (View, Edit, Download, Delete)

---

## 🔐 Permission Control

### Route Protection

```php
// ต้องเป็น Admin/Manager เท่านั้น
Route::middleware(['auth', 'admin'])->group(function() {
    // all sales-documents routes
});
```

### Delete Logic

```php
if ($admin->role === 'admin') {
    // soft delete
    $document->update(['is_active' => false]);
} else if ($admin->role === 'manager') {
    // hard delete
    $document->forceDelete();
}
```

---

## 🐛 Known Issues & Fixes

### Issue: Quotation PDF Path Typo ❌ FIXED

**Before:**
```php
$directory = 'qoutation';  // ❌ wrong spelling
```

**After:**
```php
$directory = 'quotation';  // ✅ correct
```

**Impact:**
- Quotation PDFs were stored in wrong directory
- Invoice PDFs were working correctly
- Fix ensures consistent file organization

---

## 📝 Testing

### Test Files

```
Tests/Feature/AdminSalesDocumentWorkspaceTest.php
├── test_admin_can_view_sales_document_workspace
├── test_manager_can_view_sales_document_workspace
└── test_guest_is_redirected_to_admin_login

Tests/Feature/AdminSavedSalesDocumentTest.php
├── test_admin_can_save_and_download_sales_document
├── test_admin_can_view_saved_sales_documents_page
├── test_admin_can_view_saved_sales_document_preview_route
├── test_sales_document_pdf_view_renders_without_merge_markers
├── test_admin_can_open_saved_document_in_editor
├── test_manager_can_delete_saved_sales_document
├── test_admin_cannot_delete_saved_sales_document
└── test_admin_hides_instead_of_deleting_sales_document
```

### Running Tests

```bash
# ทั้งหมด
php artisan test

# เฉพาะ Sales Documents
php artisan test --filter "SalesDocument"

# เฉพาะไฟล์เดียว
php artisan test tests/Feature/AdminSavedSalesDocumentTest.php
```

---

## 🚀 Usage Example

### Create Quotation Programmatically

```php
// In controller or command
use App\Services\SalesDocumentPdfService;

$service = app(SalesDocumentPdfService::class);

$document = $service->saveDocument([
    'document_type' => 'quotation',
    'document_number' => 'QT-' . now('Asia/Bangkok')->format('ymd') . '-001',
    'document_date' => now('Asia/Bangkok')->format('Y-m-d'),
    'due_date' => now('Asia/Bangkok')->addDays(7)->format('Y-m-d'),
    'customer_id' => 1,
    'customer_name' => 'นายสมชาย',
    'items' => [
        [
            'description' => 'เบอร์โทรศัพท์',
            'quantity' => 1,
            'unit_price' => 5000,
            'amount' => 5000
        ]
    ],
    'company' => [
        'name_th' => 'บริษัท สุปเปอร์นัมเบอร์',
        'address' => '123 ถนน...'
    ],
    'customer' => [
        'contact_name' => 'นายสมชาย',
        'company_name' => 'ABC Trading'
    ],
    'document' => [
        'title_th' => 'ใบเสนอราคา',
        'title_en' => 'Quotation'
    ],
    'payment' => [
        'term' => 'Net 30'
    ],
    'totals' => [
        'subtotal' => 5000,
        'tax_rate' => 0.07,
        'tax_amount' => 350,
        'total' => 5350
    ],
    'signatures' => []
], auth()->id());

echo "Saved: " . $document->file_name;
// Output: Saved: Quotation QT-260520-001
```

---

## 📞 Support & Troubleshooting

### Quotation Files Not Found?

**Check:**
1. File path in database vs. actual storage
2. Disk configuration in `config/filesystems.php`
3. Storage symbolic link: `php artisan storage:link`

```bash
# Check if symlink exists
ls -la public/storage

# Fix symlink
php artisan storage:link
```

### Cannot Save Document?

**Verify:**
1. Customer exists and is active
2. Document number is unique per type
3. Admin/Manager is logged in
4. Storage directory has write permissions

```bash
# Check permissions
ls -la storage/app/private/
chmod 755 storage/app/private/
```

### PDF Not Rendering Correctly?

**Check:**
1. CSS files are loading (`css/sales-document-pdf.css`)
2. Browser zoom is 100%
3. Margins & page size correct in print settings

---

## 📚 Related Files

- `app/Models/SalesDocument.php` — Model definition
- `app/Services/SalesDocumentPdfService.php` — Service logic
- `routes/web.php` (lines 1696-1844) — Route definitions
- `resources/views/sales-documents.blade.php` — Editor view
- `resources/views/admin/sales-document-pdf.blade.php` — PDF template
- `config/filesystems.php` — Disk configuration

---

## ✅ Checklist for Admin

- [ ] ตรวจสอบว่า BillingCustomer (ลูกค้า) มีข้อมูลถูกต้อง
- [ ] ทดสอบสร้าง Quotation และ Invoice
- [ ] ตรวจสอบไฟล์เก็บในโฟลเดอร์ที่ถูกต้อง
- [ ] ทดสอบดาวน์โหลด PDF
- [ ] ตรวจสอบ permission (Admin vs Manager)
- [ ] ลองแก้ไขเอกสารที่บันทึกไว้
- [ ] ทดสอบการลบเอกสาร

