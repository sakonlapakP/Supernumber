@extends('layouts.admin')

@section('title', 'Supernumber Admin | นำเข้าโพสต์ Facebook')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>นำเข้าโพสต์ Facebook</h1>
      <p class="admin-subtitle">ดึงโพสต์ย้อนหลังจาก Graph API แล้วเก็บลงตารางแยกเพื่อคัดกรองก่อนลงบทความจริง</p>
    </div>
    <div class="admin-summary">
      ข้อมูลทั้งหมด {{ number_format((int) ($totals['all'] ?? 0)) }} รายการ
      <br>
      ซิงก์ล่าสุด {{ !empty($totals['latest_sync_at']) ? \Illuminate\Support\Carbon::parse($totals['latest_sync_at'])->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-' }}
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">ค้นหารายการที่ดึงแล้ว</h2>
      </div>
      <a href="{{ route('admin.facebook-imports.refresh-preview') }}" class="admin-button admin-button--compact" style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff; border: none; text-decoration: none;">
        ดูตัวอย่างก่อนอัปโหลด
      </a>
    </div>

    <form action="{{ route('admin.facebook-imports') }}" method="get" class="admin-form admin-form--inline" style="grid-template-columns: minmax(0, 1fr) 180px 180px auto; gap: 12px;">
      <div class="admin-field">
        <label for="q">ค้นหา</label>
        <input id="q" class="admin-input" type="text" name="q" value="{{ $search }}" placeholder="message, story, post id" />
      </div>
      <div class="admin-field">
        <label for="from">วันที่เริ่ม</label>
        <input id="from" class="admin-input" type="date" name="from" value="{{ $fromDate }}" />
      </div>
      <div class="admin-field">
        <label for="to">วันที่สิ้นสุด</label>
        <input id="to" class="admin-input" type="date" name="to" value="{{ $toDate }}" />
      </div>
      <div style="display: flex; align-items: end;">
        <button type="submit" class="admin-button admin-button--compact">ค้นหา</button>
      </div>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table" style="min-width: 1120px;">
        <thead>
          <tr>
            <th>#</th>
            <th>สถานะ</th>
            <th>รูป</th>
            <th>Facebook Post ID</th>
            <th>เวลาโพสต์</th>
            <th>ข้อความ</th>
            <th>ลิงก์</th>
            <th>ซิงก์ล่าสุด</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($posts as $post)
            @php
              $imageUrl = trim((string) ($post->full_picture ?? ''));
              $attachments = is_array($post->attachments_json) ? $post->attachments_json : [];

              if ($imageUrl === '' && isset($attachments['data']) && is_array($attachments['data'])) {
                foreach ($attachments['data'] as $attachmentItem) {
                  if (! is_array($attachmentItem)) {
                    continue;
                  }

                  $candidate = trim((string) data_get($attachmentItem, 'media.image.src', ''));
                  if ($candidate === '') {
                    $candidate = trim((string) data_get($attachmentItem, 'media.source', ''));
                  }
                  if ($candidate === '') {
                    $candidate = trim((string) data_get($attachmentItem, 'url', ''));
                  }

                  if ($candidate !== '') {
                    $imageUrl = $candidate;
                    break;
                  }
                }
              }
            @endphp
            @php
              $statusStyles = [
                'pending'   => 'color:#dc2626;background:#fef2f2',
                'refreshed' => 'color:#d97706;background:#fffbeb',
                'approved'  => 'color:#059669;background:#ecfdf5',
                'published' => 'color:#1d4ed8;background:#eff6ff',
              ];
              $statusLabels = [
                'pending'   => 'รอ',
                'refreshed' => 'เขียนแล้ว',
                'approved'  => 'อนุมัติ',
                'published' => 'เผยแพร่',
              ];
              $postStatus = $post->status ?? 'pending';
            @endphp
            <tr>
              <td>{{ $post->id }}</td>
              <td>
                <a href="{{ route('admin.facebook-imports.review', $post->id) }}"
                   style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                  <span style="padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;{{ $statusStyles[$postStatus] ?? $statusStyles['pending'] }}">
                    {{ $statusLabels[$postStatus] ?? $postStatus }}
                  </span>
                </a>
              </td>
              <td>
                @if ($imageUrl !== '')
                  <a href="{{ $imageUrl }}" target="_blank" rel="noopener">
                    <img
                      src="{{ $imageUrl }}"
                      alt="รูปโพสต์ {{ $post->facebook_post_id }}"
                      loading="lazy"
                      referrerpolicy="no-referrer"
                      style="width: 92px; height: 92px; object-fit: cover; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc;"
                    />
                  </a>
                @else
                  <span class="admin-muted">-</span>
                @endif
              </td>
              <td style="font-family: monospace; font-size: 12px;">{{ $post->facebook_post_id }}</td>
              <td>{{ optional($post->facebook_created_time)->timezone('Asia/Bangkok')->format('d/m/Y H:i') ?: '-' }}</td>
              <td>{{ \Illuminate\Support\Str::limit(trim((string) ($post->message ?: $post->story ?: '-')), 160) }}</td>
              <td>
                @if ($post->permalink_url)
                  <a href="{{ $post->permalink_url }}" target="_blank" rel="noopener" class="admin-button admin-button--muted admin-button--compact">เปิดโพสต์</a>
                @else
                  -
                @endif
              </td>
              <td>{{ optional($post->last_synced_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i') ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="admin-muted">ยังไม่มีข้อมูลที่ดึงจาก Facebook</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($posts->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการโพสต์ Facebook">
        @if ($posts->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $posts->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $posts->currentPage() - 2);
          $endPage = min($posts->lastPage(), $posts->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $posts->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $posts->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($posts->hasMorePages())
          <a href="{{ $posts->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
