@extends('layouts.admin')

@section('title', 'Supernumber Admin | Preview Facebook Refresh')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Preview การอัปโหลดเข้า Articles</h1>
      <p class="admin-subtitle">ตรวจสอบรายการที่จะถูกสร้าง/อัปเดต และโพสต์ Facebook ที่จะถูกลบก่อนยืนยัน</p>
    </div>
    <div class="admin-summary">
      พบรายการที่เกี่ยวข้อง {{ number_format((int) ($summary['processed'] ?? 0)) }} รายการ
      <br>
      จะลบโพสต์ Facebook {{ number_format((int) ($summary['deleted_imports'] ?? 0)) }} รายการ
    </div>
  </div>

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head" style="align-items: center;">
      <div>
        <h2 class="admin-feature-card__title">รายการที่ระบบจะประมวลผล</h2>
      </div>
      <form action="{{ route('admin.facebook-imports.refresh-articles') }}" method="post" onsubmit="return confirm('ยืนยันอัปโหลดคอนเทนต์ที่จับคู่ได้ไปยัง articles และลบโพสต์ที่ใช้แล้วออกจาก facebook-imports ใช่หรือไม่?');">
        @csrf
        <button type="submit" class="admin-button admin-button--compact" style="background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%); color: #fff; border: none;">
          ยืนยันอัปโหลด
        </button>
      </form>
    </div>

    @if (empty($summary['topics']))
      <div class="admin-alert admin-alert--error">
        ไม่พบรายการที่สามารถจับคู่เพื่อสร้าง/อัปเดต article ได้
      </div>
    @else
      <div class="admin-table-wrap">
        <table class="admin-table" style="min-width: 1100px;">
          <thead>
            <tr>
              <th>หัวข้อ</th>
              <th>Slug</th>
              <th>Title</th>
              <th>Published</th>
              <th>Action</th>
              <th>ลบ import</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($summary['topics'] as $topic)
              <tr>
                <td>{{ $topic['topic'] ?? '-' }}</td>
                <td style="font-family: monospace; font-size: 12px;">{{ $topic['slug'] ?? '-' }}</td>
                <td>
                  <div style="font-weight: 700;">{{ $topic['title'] ?? '-' }}</div>
                  <div class="admin-muted" style="margin-top: 6px;">{{ \Illuminate\Support\Str::limit((string) ($topic['excerpt'] ?? ''), 140) }}</div>
                </td>
                <td>{{ !empty($topic['published_at']) ? \Illuminate\Support\Carbon::parse($topic['published_at'])->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ !empty($topic['created']) ? 'สร้างใหม่' : 'อัปเดต' }}</td>
                <td>{{ number_format((int) ($topic['deleted_imports'] ?? 0)) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div style="margin-top: 18px; display: grid; gap: 8px;">
        @foreach ($summary['topics'] as $topic)
          <div class="admin-card" style="padding: 16px; border: 1px solid #e2e8f0; box-shadow: none;">
            <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: center;">
              <div>
                <div style="font-weight: 700; font-size: 16px;">{{ $topic['topic'] ?? '-' }}</div>
                <div class="admin-muted" style="margin-top: 4px; font-family: monospace;">{{ $topic['slug'] ?? '-' }}</div>
              </div>
              <div class="admin-muted">
                ใช้โพสต์ {{ number_format(count($topic['matched_import_ids'] ?? [])) }} รายการ
              </div>
            </div>
            <div style="margin-top: 10px; line-height: 1.7;">
              <div><strong>Title:</strong> {{ $topic['title'] ?? '-' }}</div>
              <div><strong>Meta:</strong> {{ $topic['meta_description'] ?? '-' }}</div>
              <div><strong>Import IDs:</strong> {{ implode(', ', $topic['matched_import_ids'] ?? []) }}</div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </section>
@endsection
