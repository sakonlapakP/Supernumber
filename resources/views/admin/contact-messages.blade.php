@extends('layouts.admin')

@section('title', 'Supernumber Admin | Contact Messages')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Contact Messages</h1>
      <p class="admin-subtitle">รายการข้อความที่ลูกค้าส่งมาจากหน้า Contact Us</p>
    </div>
    <div class="admin-summary">ทั้งหมด {{ number_format($messages->total()) }} รายการ</div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เวลา</th>
            <th>ชื่อ</th>
            <th>เบอร์ติดต่อ</th>
            <th>ข้อความ</th>
            <th>IP</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($messages as $message)
            <tr>
              <td>{{ optional($message->submitted_at ?? $message->created_at)->timezone('Asia/Bangkok')->format('Y-m-d H:i') }}</td>
              <td>{{ $message->name }}</td>
              <td>
                <a href="tel:{{ $message->phone }}">{{ $message->phone }}</a>
              </td>
              <td style="max-width: 420px; white-space: normal; line-height: 1.6;">{{ $message->message }}</td>
              <td>{{ $message->ip_address ?: '-' }}</td>
              <td class="admin-action-cell">
                <form action="{{ route('admin.contact-messages.delete', $message) }}" method="post" style="display:inline-block;" onsubmit="return confirm('ยืนยันลบข้อความนี้?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="admin-button admin-button--compact" style="background:#b42318;">ลบ</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มีข้อความจากหน้า Contact Us</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($messages->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการข้อความติดต่อ">
        @if ($messages->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $messages->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $messages->currentPage() - 2);
          $endPage = min($messages->lastPage(), $messages->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $messages->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $messages->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($messages->hasMorePages())
          <a href="{{ $messages->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
