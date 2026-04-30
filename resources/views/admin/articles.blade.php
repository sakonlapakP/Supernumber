@extends('layouts.admin')

@section('content')
    <div class="admin-header">
      <div class="admin-header__title">
        <h1>บทความ</h1>
        <p class="admin-muted">จัดการบทความทั้งหมดแบบลิสต์ พร้อมปุ่มจัดการท้ายแต่ละแถว</p>
      </div>
      <div class="admin-header__actions">
        <form action="{{ route('admin.articles.auto-gen-lottery') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="admin-button" style="background: #1e1b4b; border-color: #1e1b4b;">✨ สร้างบทความหวยอัตโนมัติ</button>
        </form>
        <a href="{{ route('admin.articles.create') }}" class="admin-button">เพิ่มบทความ</a>
      </div>
    </div>

    @if (session('status_message'))
      <div class="admin-alert admin-alert--success">
        {{ session('status_message') }}
      </div>
    @endif

    <div class="admin-card">
      <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
        <input type="text" id="article-search" placeholder="ค้นหาหัวข้อบทความ..." class="admin-input" style="max-width: 300px;">
      </div>
      <table class="admin-table">
        <thead>
          <tr>
            <th>รูปหน้าปก</th>
            <th>หัวข้อ</th>
            <th>สถานะ</th>
            <th>ยอดวิว</th>
            <th>เวลาเผยแพร่</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($articles as $article)
            <tr class="article-row" data-title="{{ strtolower($article->title) }}" data-slug="{{ strtolower($article->slug) }}">
              <td>
                <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                  @if($article->cover_image_path)
                    <img src="{{ Storage::disk('public')->url($article->cover_image_path) }}" style="width: 100%; height: 100%; object-fit: cover;">
                  @else
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">🖼️</div>
                  @endif
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">{{ $article->title }}</div>
                <div class="admin-muted" style="font-size: 12px;">{{ $article->slug }}</div>
              </td>
              <td>
                @if($article->is_published)
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
                              onclick="shareToFb(this, {{ $article->id }}, '{{ $article->cover_image_square_path ? '/storage/' . $article->cover_image_square_path : '' }}', '{{ route('admin.articles.upload-rendered-image', $article) }}')"
                              class="admin-button admin-button--compact" 
                              style="background: #1877F2; color: #fff; border-color: #1877F2;" 
                              title="แชร์ไป Facebook (รูปจัตุรัสพรีเมียม)">FB</button>
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
              <td colspan="6" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบรายการบทความ</td>
            </tr>
          @endforelse
          <tr id="articles-empty-row" style="display: none;">
            <td colspan="6" class="admin-muted" style="text-align: center; padding: 40px;">ไม่พบผลลัพธ์การค้นหา</td>
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

    {{-- Client-side Rendering Bridge --}}
    <canvas id="render-canvas" style="display: none;"></canvas>
    <div id="render-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 99999; color: white; flex-direction: column; align-items: center; justify-content: center; font-family: sans-serif;">
        <div style="border: 4px solid #f3f3f3; border-top: 4px solid #1877F2; border-radius: 50%; width: 50px; height: 50px; animation: spin_render 1s linear infinite; margin-bottom: 20px;"></div>
        <div id="render-status" style="font-size: 18px; font-weight: bold;">กำลังวาดรูปหวยให้สวยงาม...</div>
        <p style="margin-top: 10px; opacity: 0.8;">กำลังใช้ระบบวาดรูปขั้นสูงเพื่อให้ Facebook แสดงผลได้ชัดที่สุด</p>
        <style>@keyframes spin_render { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
    </div>
@endsection

@push('scripts')
  <!-- Load Canvg v3 (UMD) -->
  <script src="https://cdn.jsdelivr.net/npm/canvg@3.0.10/lib/umd.js"></script>
  
  <script>
    (function() {
        const overlay = document.getElementById('render-overlay');
        const status = document.getElementById('render-status');
        const canvas = document.getElementById('render-canvas');
        const ctx = canvas.getContext('2d');

        window.shareToFb = async function(button, articleId, landscapeUrl, uploadUrl) {
            const form = button.closest('form');
            
            if (!landscapeUrl || !landscapeUrl.toLowerCase().includes('.svg')) {
                form.submit();
                return;
            }

            // Universal Canvg Detection
            const canvgObj = window.canvg || window.Canvg;
            if (!canvgObj) {
                alert('ระบบวาดรูปยังไม่พร้อม กรุณารอ 2 วินาทีแล้วลองใหม่ครับ');
                return;
            }

            overlay.style.display = 'flex';
            status.innerText = 'กำลังเตรียมระบบวาดรูป...';

            try {
                const response = await fetch(landscapeUrl);
                if (!response.ok) throw new Error('ไม่สามารถดึงข้อมูลรูปภาพได้');
                let svgText = await response.text();

                status.innerText = 'กำลังวาดรูปจัตุรัสพรีเมียม (1200x1200)...';
                canvas.width = 1200;
                canvas.height = 1200;
                
                ctx.fillStyle = "black";
                ctx.fillRect(0, 0, 1200, 1200);

                // Version-agnostic rendering call
                if (typeof canvgObj === 'function') {
                    await canvgObj(canvas, svgText);
                } else if (canvgObj.Canvg && typeof canvgObj.Canvg.fromString === 'function') {
                    const v = await canvgObj.Canvg.fromString(ctx, svgText);
                    await v.render();
                } else if (typeof canvgObj.fromString === 'function') {
                    const v = await canvgObj.fromString(ctx, svgText);
                    await v.render();
                } else {
                    throw new Error('ไม่พบวิธีเรียกใช้ระบบวาดรูปที่รองรับ');
                }

                const pngData = canvas.toDataURL('image/png', 0.9);
                
                status.innerText = 'กำลังบันทึกรูปจัตุรัสลง Server...';
                const uploadRes = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ image: pngData, type: 'landscape' }) // Still target landscape slot for FB
                });

                const uploadJson = await uploadRes.json();
                if (!uploadJson.success) throw new Error(uploadJson.error || 'Upload failed');

                status.innerText = 'สำเร็จ! กำลังไปที่ Facebook...';
                setTimeout(() => form.submit(), 1000);

            } catch (err) {
                console.error('Render error:', err);
                overlay.style.display = 'none';
                alert('ระบบวาดรูปขัดข้อง: ' + err.message + '\nจะพยายามแชร์แบบปกติให้ครับ');
                form.submit();
            }
        };
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
          if (emptyRow) emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? "" : "none";
        });
      }
    })();
  </script>
@endpush
