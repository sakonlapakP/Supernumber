@extends('layouts.admin')

@section('title', 'Supernumber Admin | บทความ')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>บทความ</h1>
      <p class="admin-subtitle">จัดการบทความทั้งหมดแบบลิสต์ พร้อมปุ่มจัดการด้านท้ายแต่ละแถว</p>
    </div>
    <div class="admin-page-actions" style="display: flex; gap: 10px;">
      <a href="{{ route('admin.articles.auto-gen-lottery') }}" class="admin-button" style="background-color: #6366f1; border-color: #6366f1;">🎰 สร้างบทความหวยอัตโนมัติ</a>
      <a href="{{ route('admin.articles.create') }}" class="admin-button">เพิ่มบทความ</a>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
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
            <th style="width: 80px;">รูปปก</th>
          <th>หัวข้อ</th>
            <th>สถานะ</th>
            <th>ยอดวิว</th>
            <th>เวลาเผยแพร่</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody id="articles-table-body">
          @forelse ($articles as $article)
            <tr class="article-row" data-title="{{ strtolower($article->title) }}" data-slug="{{ strtolower($article->slug) }}">
            <td style="padding: 12px;">
              @php
                $adminThumb = $article->cover_image_path ?: ($article->cover_image_square_path ?: $article->cover_image_landscape_path);
              @endphp
              @if ($adminThumb)
                <img src="{{ asset('storage/' . $adminThumb) }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; display: block;" alt="" />
              @else
                <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🖼️</div>
              @endif
            </td>
            <td>
              <div style="font-weight: 500; color: #1e293b;">{{ $article->title }}</div>
              <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">{{ $article->slug }}</div>
            </td>
              <td>
                @if ($article->is_published && $article->published_at && $article->published_at->isFuture())
                  <span class="admin-status-pill admin-status-pill--hold" style="background-color: #fef08a; color: #854d0e;">ตั้งเวลาล่วงหน้า</span>
                @elseif ($article->is_published)
                  <span class="admin-status-pill admin-status-pill--active">เผยแพร่แล้ว</span>
                @else
                  <span class="admin-status-pill admin-status-pill--hold">ฉบับร่าง</span>
                @endif
              </td>
              <td>
                <span style="font-weight: 500; color: #475569;">👁️ {{ number_format($article->view_count) }}</span>
              </td>
              <td class="admin-muted">
                {{ $article->published_at ? $article->published_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-' }}
              </td>
              <td class="admin-action-cell">
                <div class="admin-action-group">
                  <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" class="admin-button admin-button--muted admin-button--compact" title="ดูตัวอย่าง">ดู</a>
                  <a href="{{ route('admin.articles.edit', $article) }}" class="admin-button admin-button--muted admin-button--compact">แก้ไข</a>
                  @if($article->is_published)
                    <form action="{{ route('admin.articles.share-fb', $article) }}" method="post" style="display: inline;">
                      @csrf
                      <button type="button" 
                              onclick="shareToFb(this, {{ $article->id }}, '{{ $article->cover_image_landscape_path ? Storage::disk('public')->url($article->cover_image_landscape_path) : '' }}', '{{ route('admin.articles.upload-rendered-image', $article) }}')"
                              class="admin-button admin-button--compact" 
                              style="background: #1877F2; color: #fff; border-color: #1877F2;" 
                              title="แชร์ไป Facebook (วาดรูปสวยอัตโนมัติ)">FB</button>
                    </form>
                  @endif
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
              <td colspan="5" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบรายการบทความ</td>
            </tr>
          @endforelse
          <tr id="articles-empty-row" style="display: none;">
            <td colspan="5" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบผลลัพธ์การค้นหา</td>
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
  </script>

  {{-- Client-side Rendering Bridge --}}
  <canvas id="render-canvas" style="display: none;"></canvas>
  <div id="render-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 99999; color: white; flex-direction: column; align-items: center; justify-content: center; font-family: sans-serif;">
      <div style="border: 4px solid #f3f3f3; border-top: 4px solid #1877F2; border-radius: 50%; width: 50px; height: 50px; animation: spin_render 1s linear infinite; margin-bottom: 20px;"></div>
      <div id="render-status" style="font-size: 18px; font-weight: bold;">กำลังวาดรูปหวยให้สวยงาม...</div>
      <p style="margin-top: 10px; opacity: 0.8;">กรุณารอครู่เดียว ระบบกำลังใช้เบราว์เซอร์ของคุณวาดรูปให้ชัดเป๊ะ</p>
      <style>@keyframes spin_render { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
  </div>

  <script>
    (function() {
        const overlay = document.getElementById('render-overlay');
        const status = document.getElementById('render-status');
        const canvas = document.getElementById('render-canvas');
        const ctx = canvas.getContext('2d');

        window.shareToFb = async function(button, articleId, landscapePath, uploadUrl) {
            const form = button.closest('form');
            
            // Only render if it's an SVG
            if (!landscapePath || !landscapePath.toLowerCase().endsWith('.svg')) {
                form.submit();
                return;
            }

            overlay.style.display = 'flex';
            status.innerText = 'กำลังดึงข้อมูล SVG...';

            try {
                // 1. Fetch SVG content
                const response = await fetch(landscapePath);
                if (!response.ok) throw new Error('ไม่สามารถดึงไฟล์รูปภาพต้นฉบับได้');
                let svgText = await response.text();

                // 2. Render to PNG (Landscape 1200x630 for FB)
                status.innerText = 'กำลังวาดรูปความละเอียดสูง (1200x630)...';
                const pngData = await renderSvgToPng(svgText, 1200, 630);
                
                // 3. Upload PNG back to Server
                status.innerText = 'กำลังส่งรูปกลับไปที่ Server...';
                const uploadRes = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        image: pngData,
                        type: 'landscape'
                    })
                });

                const uploadJson = await uploadRes.json();
                if (!uploadJson.success) throw new Error(uploadJson.error || 'Upload failed');

                status.innerText = 'วาดรูปสำเร็จ! กำลังส่งไป Facebook...';
                setTimeout(() => form.submit(), 800);

            } catch (err) {
                console.error('Render error:', err);
                alert('การวาดรูปอัตโนมัติขัดข้อง: ' + err.message + '\nระบบจะแชร์แบบปกติให้ครับ');
                form.submit();
            }
        };

        function renderSvgToPng(svgText, width, height) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                // Ensure SVG has proper dimensions for rendering
                const parser = new DOMParser();
                const doc = parser.parseFromString(svgText, 'image/svg+xml');
                const svgEl = doc.querySelector('svg');
                svgEl.setAttribute('width', width);
                svgEl.setAttribute('height', height);
                const updatedSvg = new XMLSerializer().serializeToString(doc);

                const svgBlob = new Blob([updatedSvg], {type: 'image/svg+xml;charset=utf-8'});
                const url = URL.createObjectURL(svgBlob);
                
                img.onload = function() {
                    canvas.width = width;
                    canvas.height = height;
                    ctx.clearRect(0, 0, width, height);
                    
                    // Fill white background just in case
                    ctx.fillStyle = "white";
                    ctx.fillRect(0, 0, width, height);
                    
                    ctx.drawImage(img, 0, 0, width, height);
                    URL.revokeObjectURL(url);
                    resolve(canvas.toDataURL('image/png', 1.0));
                };
                
                img.onerror = function(e) {
                    URL.revokeObjectURL(url);
                    reject(new Error('เบราว์เซอร์ไม่สามารถประมวลผล SVG ได้'));
                };
                
                img.src = url;
            });
        }
    })();
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
