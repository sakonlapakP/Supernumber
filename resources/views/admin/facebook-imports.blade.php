@extends('layouts.admin')

@section('title', 'Supernumber Admin | นำเข้าโพสต์ Facebook')

@section('content')
  @php
    $defaultNodeId = (string) config('services.facebook.page_id', '');
  @endphp

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
        <h2 class="admin-feature-card__title">Sync From Facebook</h2>
        <p class="admin-feature-card__hint">ระบบจะวนเรียก `paging.next` อัตโนมัติจนหมดหรือถึง `max_pages`</p>
      </div>
    </div>

    <form action="{{ route('admin.facebook-imports.sync') }}" method="post" class="admin-form">
      @csrf
      <div class="admin-field">
        <label for="node_id">Facebook Node ID (Page ID)</label>
        <input id="node_id" class="admin-input" type="text" name="node_id" value="{{ old('node_id', $defaultNodeId) }}" placeholder="เช่น 123456789012345" />
      </div>

      <div class="admin-field">
        <label for="access_token">Access Token (เว้นว่างเพื่อใช้ค่าจากระบบ)</label>
        <textarea id="access_token" class="admin-input" name="access_token" rows="3" placeholder="ใส่เฉพาะกรณีต้อง override token ชั่วคราว"></textarea>
      </div>

      <div class="admin-form admin-form--inline" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;">
        <div class="admin-field">
          <label for="edge">Edge</label>
          <select id="edge" name="edge" class="admin-select">
            <option value="feed" {{ old('edge', 'feed') === 'feed' ? 'selected' : '' }}>feed</option>
            <option value="posts" {{ old('edge') === 'posts' ? 'selected' : '' }}>posts</option>
          </select>
        </div>

        <div class="admin-field">
          <label for="limit">Limit / page</label>
          <input id="limit" class="admin-input" type="number" name="limit" min="1" max="100" value="{{ old('limit', 100) }}" />
        </div>

        <div class="admin-field">
          <label for="max_pages">Max pages</label>
          <input id="max_pages" class="admin-input" type="number" name="max_pages" min="1" max="5000" value="{{ old('max_pages', 250) }}" />
        </div>

        <div class="admin-field">
          <label for="until">Until</label>
          <input id="until" class="admin-input" type="date" name="until" value="{{ old('until') }}" />
        </div>
      </div>

      <div class="admin-field">
        <label for="since">Since (optional)</label>
        <input id="since" class="admin-input" type="date" name="since" value="{{ old('since') }}" />
      </div>

      <div>
        <button type="submit" class="admin-button">เริ่มดึงข้อมูล Facebook</button>
      </div>
    </form>
  </section>

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">ค้นหารายการที่ดึงแล้ว</h2>
      </div>
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
      <table class="admin-table" style="min-width: 960px;">
        <thead>
          <tr>
            <th>#</th>
            <th>Facebook Post ID</th>
            <th>เวลาโพสต์</th>
            <th>ข้อความ</th>
            <th>ลิงก์</th>
            <th>ซิงก์ล่าสุด</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($posts as $post)
            <tr>
              <td>{{ $post->id }}</td>
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
              <td colspan="6" class="admin-muted">ยังไม่มีข้อมูลที่ดึงจาก Facebook</td>
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
