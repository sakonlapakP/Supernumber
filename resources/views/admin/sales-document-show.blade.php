@extends('layouts.admin')

@section('title', 'Supernumber Admin | รายละเอียดเอกสาร')

@section('content')
  @php
    $documentTypeLabel = match ($document->document_type) {
      'invoice' => 'ใบแจ้งหนี้',
      default => 'ใบเสนอราคา',
    };
  @endphp
  <style>
    .saved-document-detail-layout {
      display: block;
    }

    .saved-document-preview-card {
      padding: 14px;
      overflow: hidden;
    }

    .saved-document-preview-card__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }

    .saved-document-preview-card__title {
      margin: 0;
      font-size: 22px;
      line-height: 1.2;
      color: #202939;
    }

    .saved-document-preview-card__hint {
      margin: 4px 0 0;
      color: #667085;
      font-size: 13px;
    }

    .saved-document-preview-shell {
      border: 1px solid rgba(60, 49, 40, 0.1);
      border-radius: 18px;
      background: linear-gradient(180deg, #fbf8f2 0%, #f4ede0 100%);
      padding: 22px;
      min-height: 1120px;
      box-sizing: border-box;
    }

    .saved-document-preview-frame {
      width: 100%;
      min-height: 1080px;
      border: 0;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 18px 44px rgba(36, 28, 20, 0.12);
    }

    .saved-document-json {
      margin: 0;
      white-space: pre-wrap;
      word-break: break-word;
      background: #faf7f0;
      border: 1px solid rgba(60, 49, 40, .08);
      border-radius: 16px;
      padding: 16px;
      max-height: 340px;
      overflow: auto;
      font-size: 12px;
      line-height: 1.5;
    }

    @media (max-width: 1200px) {
      .saved-document-preview-shell {
        min-height: 740px;
      }

      .saved-document-preview-frame {
        min-height: 700px;
      }
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>{{ $documentTypeLabel }} {{ $document->document_number }}</h1>
      <p class="admin-subtitle">ตรวจสอบข้อมูลเอกสารที่บันทึกไว้ พร้อมดูหน้าตาเอกสารแบบเดียวกับไฟล์พิมพ์/PDF ทางด้านขวา</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.saved-sales-documents.index') }}" class="admin-button admin-button--compact admin-button--muted">กลับไปหน้ารายการเอกสารทั้งหมด</a>
      <a href="{{ route('admin.sales-documents', ['document' => $document->id]) }}" class="admin-button admin-button--compact admin-button--muted">แก้ไขเอกสาร</a>
      <a href="{{ route('admin.saved-sales-documents.download', $document) }}" class="admin-button admin-button--compact" target="_blank" rel="noopener">พิมพ์ / บันทึก PDF</a>
    </div>
  </div>

  <div class="saved-document-detail-layout">
    <section class="admin-card admin-feature-card saved-document-preview-card">
      <div class="saved-document-preview-card__head">
        <div>
          <h2 class="saved-document-preview-card__title">ตัวอย่างเอกสาร</h2>
          <p class="saved-document-preview-card__hint">แสดงผลแบบเดียวกับหน้าเอกสารสำหรับพิมพ์ เพื่อเช็กรูปแบบเอกสารได้ทันที</p>
        </div>
      </div>

      <div class="saved-document-preview-shell">
        <iframe
          class="saved-document-preview-frame"
          src="{{ route('admin.saved-sales-documents.preview', $document) }}"
          title="ตัวอย่างเอกสาร {{ $document->document_number }}"
          loading="lazy"
        ></iframe>
      </div>
    </section>
  </div>
@endsection
