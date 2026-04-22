@extends('layouts.admin')

@section('title', 'Supernumber Admin | บทความ')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>บทความ</h1>
      <p class="admin-subtitle">จัดการบทความทั้งหมดแบบลิสต์ พร้อมปุ่มจัดการด้านท้ายแต่ละแถว</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.articles.create') }}" class="admin-button">เพิ่มบทความ</a>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-table-card">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title">รายการบทความ</h2>
        <p class="admin-feature-card__hint">ลิสต์บทความทั้งหมดพร้อมปุ่มจัดการด้านท้ายแถว</p>
      </div>
      <div class="admin-search-shell" style="min-width: 280px;">
        <input type="text" id="article-search" class="admin-input" placeholder="🔍 ค้นหาหัวข้อบทความ..." />
      </div>
    </div>

    <div class="admin-table-wrap admin-table-wrap--articles">
      <table class="admin-table admin-table--articles">
        <thead>
          <tr>
            <th>หัวข้อ</th>
            <th>สถานะ</th>
            <th>เวลาเผยแพร่</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody id="articles-table-body">
          @forelse ($articles as $article)
            <tr class="article-row" data-title="{{ strtolower($article->title) }}" data-slug="{{ strtolower($article->slug) }}">
              <td>
                <span class="admin-article-title" title="{{ $article->title }}">{{ $article->title }}</span>
              </td>
              <td>
                @if ($article->is_published)
                  <span class="admin-status-pill admin-status-pill--active">เผยแพร่แล้ว</span>
                @else
                  <span class="admin-status-pill admin-status-pill--hold">ฉบับร่าง</span>
                @endif
              </td>
              <td class="admin-muted">
                {{ $article->published_at ? $article->published_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-' }}
              </td>
              <td class="admin-action-cell">
                <div class="admin-action-group">
                  <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" class="admin-button admin-button--muted admin-button--compact" title="ดูตัวอย่าง">ดู</a>
                  <a href="{{ route('admin.articles.edit', $article) }}" class="admin-button admin-button--muted admin-button--compact">แก้ไข</a>
                  <form action="{{ route('admin.articles.delete', $article) }}" method="post" onsubmit="return confirm('ยืนยันลบบทความ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-button admin-button--muted admin-button--compact" style="color: var(--admin-danger);">ลบ</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบรายการบทความ</td>
            </tr>
          @endforelse
          <tr id="articles-empty-row" style="display: none;">
            <td colspan="4" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบผลลัพธ์การค้นหา</td>
          </tr>
        </tbody>
      </table>
    </div>

    @if ($articles->hasPages())
      <nav class="admin-pagination">
        @if ($articles->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $articles->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $articles->currentPage() - 2);
          $endPage = min($articles->lastPage(), $articles->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $articles->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $articles->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($articles->hasMorePages())
          <a href="{{ $articles->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      const searchInput = document.getElementById("article-search");
      const rows = Array.from(document.querySelectorAll(".article-row"));
      const emptyRow = document.getElementById("articles-empty-row");

      if (searchInput) {
        searchInput.addEventListener("input", () => {
          const query = searchInput.value.toLowerCase().trim();
          let visibleCount = 0;

          rows.forEach(row => {
            const title = row.dataset.title;
            const slug = row.dataset.slug;
            const match = title.includes(query) || slug.includes(query);
            row.style.display = match ? "" : "none";
            if (match) visibleCount++;
          });

          if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? "" : "none";
          }
        });
      }
    })();
  </script>
@endpush
