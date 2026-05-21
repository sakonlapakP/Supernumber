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
    .document-show-full {
      display: flex;
      flex-direction: column;
      height: 100vh;
      background: linear-gradient(180deg, #fbf8f2 0%, #f4ede0 100%);
      padding: 16px;
      gap: 12px;
    }

    .document-show-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: white;
      padding: 12px 16px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      flex-shrink: 0;
    }

    .document-show-toolbar__title {
      font-size: 16px;
      font-weight: 600;
      color: #202939;
      margin: 0;
    }

    .document-show-toolbar__actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .document-show-container {
      flex: 1;
      overflow: hidden;
      border-radius: 14px;
      background: white;
      box-shadow: 0 18px 44px rgba(36, 28, 20, 0.12);
    }

    .document-show-iframe {
      width: 100%;
      height: 100%;
      border: 0;
      border-radius: 14px;
    }

    .admin-button--small {
      padding: 8px 12px;
      font-size: 13px;
      border-radius: 6px;
      white-space: nowrap;
    }
  </style>

  <div class="document-show-full">
    <div class="document-show-toolbar">
      <h1 class="document-show-toolbar__title">{{ $documentTypeLabel }} {{ $document->document_number }}</h1>
      <div class="document-show-toolbar__actions">
        <a href="{{ route('admin.saved-sales-documents.index') }}" class="admin-button admin-button--small admin-button--muted">← กลับ</a>
        <a href="{{ route('admin.sales-documents', ['document' => $document->id]) }}" class="admin-button admin-button--small admin-button--muted">แก้ไข</a>
        <a href="{{ route('admin.saved-sales-documents.download', $document) }}" class="admin-button admin-button--small" target="_blank" rel="noopener">📥 ดาวน์โหลด</a>
      </div>
    </div>

    <div class="document-show-container">
      <iframe
        class="document-show-iframe"
        src="{{ route('admin.saved-sales-documents.preview', $document) }}"
        title="ตัวอย่างเอกสาร {{ $document->document_number }}"
        loading="lazy"
      ></iframe>
    </div>
  </div>
@endsection
