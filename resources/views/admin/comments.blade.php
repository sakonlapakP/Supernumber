@extends('layouts.admin')

@section('title', 'Supernumber Admin | คอมเมนต์')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>คอมเมนต์</h1>
      <p class="admin-subtitle">จัดการคอมเมนต์บทความจากหน้าเว็บไซต์</p>
    </div>
    <div class="admin-summary">ทั้งหมด {{ number_format($comments->total()) }} คอมเมนต์</div>
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
            <th>บทความ</th>
            <th>ผู้คอมเมนต์</th>
            <th>เนื้อหา</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($comments as $comment)
            <tr>
              <td>{{ optional($comment->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                @if ($comment->article)
                  <a href="{{ route('articles.show', $comment->article->slug) }}" target="_blank" rel="noopener noreferrer">{{ $comment->article->title }}</a>
                @else
                  -
                @endif
              </td>
              <td>
                <div>{{ $comment->commenter_name }}</div>
              </td>
              <td style="max-width: 380px; white-space: normal; line-height: 1.6;">{{ $comment->content }}</td>
              <td>
                @if ($comment->status === \App\Models\ArticleComment::STATUS_APPROVED)
                  <span class="admin-status-pill admin-status-pill--active">อนุมัติแล้ว</span>
                @elseif ($comment->status === \App\Models\ArticleComment::STATUS_REJECTED)
                  <span class="admin-status-pill" style="background:#fef3f2; color:#b42318; border-color:#fecdca;">ปฏิเสธแล้ว</span>
                @else
                  <span class="admin-status-pill admin-status-pill--hold">รอพิจารณา</span>
                @endif
              </td>
              <td class="admin-action-cell">
                @if ($comment->status !== \App\Models\ArticleComment::STATUS_APPROVED)
                  <form action="{{ route('admin.comments.approve', $comment) }}" method="post" style="display:inline-block;">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact admin-button--secondary">อนุมัติ</button>
                  </form>
                @endif

                @if ($comment->status !== \App\Models\ArticleComment::STATUS_REJECTED)
                  <form action="{{ route('admin.comments.reject', $comment) }}" method="post" style="display:inline-block;">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact" style="background:#b45309;">ปฏิเสธ</button>
                  </form>
                @endif

                <form action="{{ route('admin.comments.delete', $comment) }}" method="post" style="display:inline-block;" onsubmit="return confirm('ยืนยันลบคอมเมนต์นี้?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="admin-button admin-button--compact" style="background:#b42318;">ลบ</button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มีคอมเมนต์</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($comments->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการคอมเมนต์">
        @if ($comments->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $comments->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $comments->currentPage() - 2);
          $endPage = min($comments->lastPage(), $comments->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $comments->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $comments->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($comments->hasMorePages())
          <a href="{{ $comments->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
