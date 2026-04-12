@extends('layouts.admin')

@section('title', 'Supernumber Admin | รายการใบเสนอราคา / ใบแจ้งหนี้ทั้งหมด')

@section('content')
  @php
    $documentTypeLabels = [
      'quotation' => 'ใบเสนอราคา',
      'invoice' => 'ใบแจ้งหนี้',
    ];
  @endphp
  <div class="admin-page-head">
    <div>
      <h1>รายการใบเสนอราคา / ใบแจ้งหนี้ทั้งหมด</h1>
      <p class="admin-subtitle">ดูย้อนหลังใบเสนอราคาและใบแจ้งหนี้ทั้งหมดในรูปแบบตาราง พร้อมเปิดดูรายละเอียดหรือพิมพ์/บันทึก PDF ได้ทันที</p>
    </div>
    <div class="admin-page-actions">
      <div class="admin-summary">ทั้งหมด {{ number_format($documents->total()) }} เอกสาร</div>
      <a href="{{ route('admin.sales-documents') }}" class="admin-button admin-button--compact">สร้างใบเสนอราคา / ใบแจ้งหนี้</a>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success" style="margin-bottom: 18px;">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ประเภท / เลขที่</th>
            <th>ลูกค้า</th>
            <th>วันที่เอกสาร</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($documents as $document)
            <tr>
              <td>
                <strong>{{ $documentTypeLabels[$document->document_type] ?? $document->document_type }}</strong>
                <div class="admin-muted" style="margin-top: 6px;">{{ $document->document_number }}</div>
              </td>
              <td>{{ $document->customer_name ?: '-' }}</td>
              <td>{{ $document->document_date?->format('d/m/Y') ?: '-' }}</td>
              <td>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                  <a href="{{ route('admin.saved-sales-documents.show', $document) }}" class="admin-button admin-button--muted admin-button--compact">ดูรายละเอียด</a>
                  <a href="{{ route('admin.saved-sales-documents.download', $document) }}" class="admin-button admin-button--compact" target="_blank" rel="noopener">พิมพ์ / บันทึก PDF</a>
                  @if (session('admin_user_role') === \App\Models\User::ROLE_MANAGER)
                    <form action="{{ route('admin.saved-sales-documents.delete', $document) }}" method="post" onsubmit="return confirm('ยืนยันลบเอกสารนี้?');" style="margin: 0;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="admin-button admin-button--compact" style="background:#b42318;">ลบ</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="admin-muted">ยังไม่มีใบเสนอราคา หรือใบแจ้งหนี้ที่บันทึกไว้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <div style="margin-top: 18px;">
    {{ $documents->links() }}
  </div>
@endsection
