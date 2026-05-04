@extends('layouts.admin')

@section('content')
    <div class="admin-page-head">
      <div>
        <h1>บทความ</h1>
        <p class="admin-subtitle">จัดการบทความทั้งหมดแบบลิสต์ พร้อมปุ่มจัดการท้ายแต่ละแถว</p>
      </div>
      <div class="admin-page-actions">
        <form action="{{ route('admin.articles.auto-gen-lottery') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="admin-button" style="background: #1e1b4b; border-color: #1e1b4b;">✨ สร้างบทความหวยอัตโนมัติ</button>
        </form>
        @if(in_array(session('admin_user_role'), [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER]))
            <button type="button" class="admin-button" style="background: #7c3aed; border-color: #7c3aed;" onclick="openImportModal()">📥 เพิ่ม JSON</button>
        @endif
        <a href="{{ route('admin.articles.create') }}" class="admin-button">เพิ่มบทความ</a>
      </div>
    </div>

    @if (session('status_message'))
      <div class="admin-alert admin-alert--success">
        {{ session('status_message') }}
      </div>
    @endif

    @if(!config('services.line.group_id') && !config('services.line.groups.lottery'))
      <div style="background: #fff4f4; border: 1px solid #ffcccc; color: #cc0000; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
        <span>⚠️ ยังไม่ได้ตั้งค่า LINE Group ID! ปุ่มส่ง LINE จะใช้งานไม่ได้จนกว่าจะตั้งค่าในเมนู <a href="{{ route('admin.line-settings') }}" style="color: #1877F2; text-decoration: underline;">ตั้งค่า LINE</a></span>
      </div>
    @endif

<style>
  @media (max-width: 767px) {
    .admin-table-wrap {
      overflow: visible;
    }
    .admin-table {
      min-width: 100% !important;
      background: transparent;
      border: none;
    }
    .admin-table thead {
      display: none;
    }
    .admin-table tbody {
      display: grid;
      gap: 16px;
      padding: 0;
    }
    .admin-table tr {
      display: block;
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      padding: 16px;
      border: 1px solid #e2e8f0;
    }
    .admin-table td {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #f1f5f9;
      text-align: right;
    }
    .admin-table td:first-child {
      justify-content: center;
      border-bottom: none;
      padding-top: 0;
    }
    .admin-table td:nth-child(2) {
      display: block;
      text-align: center;
      border-bottom: 2px solid #f1f5f9;
      padding-bottom: 15px;
      margin-bottom: 5px;
    }
    .admin-table td:nth-child(2) div:first-child {
      font-size: 16px;
    }
    .admin-table td::before {
      content: attr(data-label);
      font-weight: 700;
      color: #94a3b8;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      text-align: left;
    }
    .admin-table td:first-child::before,
    .admin-table td:nth-child(2)::before {
      display: none;
    }
    .admin-table td:last-child {
      border-bottom: none;
      display: block;
      padding-bottom: 0;
      text-align: left;
    }
    .admin-table td:last-child::before {
      display: block;
      margin-bottom: 12px;
    }
    .admin-action-group {
      display: grid !important;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px;
      width: 100%;
    }
    .admin-action-group > *,
    .admin-action-group form,
    .admin-action-group .admin-button {
      width: 100% !important;
      margin: 0 !important;
    }
    .admin-action-group .admin-button {
      height: 44px;
    }
  }
</style>

    <div class="admin-card">
      <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <input type="text" id="article-search" placeholder="ค้นหาหัวข้อบทความ..." class="admin-input" style="max-width: 300px;">
        <div class="admin-muted" style="font-size: 13px; font-weight: 600;">ทั้งหมด {{ number_format($articles->total()) }} รายการ</div>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
        <thead>
          <tr>
            <th>รูปหน้าปก</th>
            <th>หัวข้อ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($articles as $article)
            @php
              $isLotteryArticle = preg_match('/^thai-govern?ment-lottery-(\d{4})(\d{2})(first|second)$/', (string)$article->slug, $matches) === 1;
              $lotteryIsComplete = true;
              if ($isLotteryArticle) {
                  $year = $matches[1]; $month = $matches[2]; $round = $matches[3];
                  $lotteryResult = \App\Models\LotteryResult::whereYear('draw_date', $year)->whereMonth('draw_date', $month)->get()->first(function($r) use ($round) {
                      $d = $r->source_draw_date ?? $r->draw_date;
                      return $round === 'first' ? (int)$d->format('j') <= 15 : (int)$d->format('j') > 15;
                  });
                  $lotteryIsComplete = $lotteryResult ? $lotteryResult->is_complete : false;
              }
            @endphp
            <tr class="article-row" data-title="{{ strtolower($article->title) }}" data-slug="{{ strtolower($article->slug) }}">
              <td data-label="รูปหน้าปก">
                <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                  @php
                    $thumbPath = $article->cover_image_path ?: ($article->cover_image_square_path ?: $article->cover_image_landscape_path);
                  @endphp
                  @if($thumbPath)
                    <img src="{{ Storage::disk('public')->url($thumbPath) }}" style="width: 100%; height: 100%; object-fit: cover;">
                  @else
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">🖼️</div>
                  @endif
                </div>
              </td>
              <td data-label="หัวข้อ">
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">{{ $article->title }}</div>
                <div class="admin-muted" style="font-size: 12px;">{{ $article->slug }}</div>
              </td>
              <td data-label="สถานะ">
                @if($article->is_published)
                  <span class="admin-status-pill admin-status-pill--active">เผยแพร่แล้ว</span>
                @else
                  <span class="admin-status-pill admin-status-pill--hold">ฉบับร่าง</span>
                @endif
                
                @if($isLotteryArticle && !$lotteryIsComplete)
                  <div style="margin-top: 4px;">
                    <span class="admin-status-pill admin-status-pill--hold" style="font-size: 10px; background: #fff7ed; color: #c2410c; border-color: #fdba74;">⏳ รอผลครบ 100%</span>
                  </div>
                @endif

                <div class="admin-muted" style="font-size: 12px; margin-top: 6px;">
                  {{ $article->published_at ? $article->published_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') : '-' }}
                </div>
              </td>
              <td data-label="จัดการ" class="admin-action-cell">
                <div class="admin-action-group">
                  <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" class="admin-button admin-button--muted admin-button--compact" title="ดูตัวอย่าง">ดู</a>
                  <a href="{{ route('admin.articles.edit', $article) }}" class="admin-button admin-button--muted admin-button--compact">แก้ไข</a>
                  @if($article->is_published && $isLotteryArticle)
                    <form id="share-social-form-{{ $article->id }}" action="{{ route('admin.articles.share-social', $article) }}" method="POST" style="display: inline;">
                      @csrf
                      <input type="hidden" name="manual_image_url" id="share-social-image-{{ $article->id }}">
                      <button type="button" 
                              id="btn-share-social-{{ $article->id }}"
                              onclick="renderAndShareSocial(this, '{{ $article->id }}', '{{ $article->cover_image_square_path }}', '{{ route('admin.articles.upload-rendered-image', $article) }}', '{{ route('admin.articles.report-render-error', $article) }}', {{ $lotteryIsComplete ? 1 : 0 }})"
                              class="admin-button admin-button--compact" 
                              style="background: #1877F2; color: #fff; border-color: #1877F2; {{ !$lotteryIsComplete ? 'opacity: 0.6; cursor: not-allowed;' : '' }}" 
                              title="{{ !$lotteryIsComplete ? 'รอให้หวยออกครบ 100% ก่อนถึงจะแชร์ได้' : 'แปลงรูปเป็น PNG แล้วแชร์ไป Facebook Page' }}">แปลงรูปและแชร์</button>
                    </form>
                  @elseif($article->is_published)
                    <form action="{{ route('admin.articles.share-social', $article) }}" method="POST" style="display: inline;" onsubmit="return confirm('ยืนยันแชร์บทความนี้ไปที่ Facebook Page?')">
                      @csrf
                      <input type="hidden" name="manual_image_url" value="{{ $article->cover_image_landscape_path ?: ($article->cover_image_path ?: $article->cover_image_square_path) }}">
                      <button type="submit"
                              class="admin-button admin-button--compact"
                              style="background: #1877F2; color: #fff; border-color: #1877F2;"
                              title="แชร์ไป Facebook Page">แชร์</button>
                    </form>
                  @endif
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
        <p style="margin-top: 10px; opacity: 0.8;">กำลังใช้ระบบวาดรูปขั้นสูงเพื่อให้แสดงผลได้ชัดที่สุด</p>
        <style>
          @keyframes spin_render { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
          
          @font-face {
              font-family: 'KanitCustom';
              src: url('/fonts/Kanit-700.ttf') format('truetype');
              font-weight: 700;
          }
          .font-loader { font-family: 'KanitCustom'; position: absolute; visibility: hidden; opacity: 0; }
      </style>
      <div class="font-loader">Force Load Kanit</div>
    </div>
    </div>

    {{-- Import JSON Modal --}}
    <div id="import-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
        <div style="background: white; width: 100%; max-width: 600px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: #1e293b;">นำเข้าบทความด้วย JSON</h3>
                <button type="button" onclick="closeImportModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
            </div>
            <form action="{{ route('admin.articles.import-json') }}" method="POST">
                @csrf
                <div style="padding: 20px;">
                    <p style="margin-top: 0; margin-bottom: 15px; color: #64748b; font-size: 14px;">วางข้อมูล JSON ของบทความที่นี่ (สามารถใส่เป็นชิ้นเดียว หรือเป็น Array ของหลายบทความได้)</p>
                    <textarea name="json_data" class="admin-input" style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 15px;" placeholder='[
  {
    "title": "หัวข้อบทความ",
    "content": "<p>เนื้อหาบทความ</p>",
    "is_published": true
  }
]' required></textarea>
                </div>
                <div style="padding: 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeImportModal()" class="admin-button admin-button--muted">ยกเลิก</button>
                    <button type="submit" class="admin-button" style="background: #7c3aed; border-color: #7c3aed;">ยืนยันการนำเข้า</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/canvg@3.0.10/lib/umd.js"></script>
  
    <script>
    (function() {
        // --- ส่วนควบคุมการแสดงผลและพื้นที่วาดรูป ---
        const overlay = document.getElementById('render-overlay'); // หน้าจอสีดำตอนกำลังวาดรูป
        const status = document.getElementById('render-status');   // ข้อความสถานะการวาด
        const canvas = document.getElementById('render-canvas');   // พื้นที่วาดรูป (ซ่อนไว้)
        const ctx = canvas.getContext('2d');

        /**
         * ฟังก์ชันกลางสำหรับวาดรูปพรีเมียมและอัปโหลดขึ้น Server
         * คืนค่าเป็นข้อมูลรูปภาพที่อัปโหลดเสร็จแล้ว
         */
        async function renderAndUploadPremiumImage(imagePath, uploadUrl, loadingText, reportUrl, type = 'square') {
            if (!imagePath) {
                return null;
            }

            // 1. เตรียม URL รูปวาดผ่าน Proxy
            const svgPath = imagePath.replace(/\.(png|jpg|jpeg|webp)$/i, '.svg');
            const svgUrl = `{{ route('admin.articles.get-svg-proxy') }}?path=${encodeURIComponent(svgPath)}`;
            
            if (!svgUrl || !svgUrl.toLowerCase().includes('.svg')) {
                return null; // ถ้าไม่มีไฟล์รูปวาด ให้ระบบเดิมทำงานต่อ
            }

            const canvgObj = window.canvg || window.Canvg;
            if (!canvgObj) {
                alert('ระบบวาดรูปยังไม่พร้อม กรุณารอ 2 วินาทีแล้วลองใหม่ครับ');
                return null;
            }

            overlay.style.display = 'flex';
            status.innerText = loadingText;

            try {
                // 2. ดึงและแปลงฟอนต์
                const fontRes = await fetch('/fonts/Kanit-700.ttf');
                const fontBlob = await fontRes.blob();
                const fontBase64 = await new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(fontBlob);
                });

                // 3. ดึงโค้ดรูปวาด
                const response = await fetch(svgUrl);
                if (!response.ok) throw new Error('ไม่สามารถดึงข้อมูลรูปภาพจาก Server ได้ (SVG Not Found)');
                let svgText = await response.text();

                // 4. ฉีดฟอนต์ (รองรับทั้งชื่อ Kanit และ KanitCustom)
                const fontStyle = `<style>@font-face { font-family: 'Kanit'; src: url("${fontBase64}"); font-weight: 700; } @font-face { font-family: 'KanitCustom'; src: url("${fontBase64}"); font-weight: 700; }</style>`;
                svgText = svgText.replace('<defs>', `<defs>${fontStyle}`);

                canvas.width = 1200;
                canvas.height = 1200;
                ctx.fillStyle = "black";
                ctx.fillRect(0, 0, 1200, 1200);

                // 5. เริ่มการวาดรูป
                if (typeof canvgObj === 'function') {
                    await canvgObj(canvas, svgText);
                } else if (canvgObj.Canvg && typeof canvgObj.Canvg.fromString === 'function') {
                    const v = await canvgObj.Canvg.fromString(ctx, svgText);
                    await v.render();
                } else {
                    const v = await canvgObj.fromString(ctx, svgText);
                    await v.render();
                }

                // 6. แปลงเป็น PNG และอัปโหลด
                const pngData = canvas.toDataURL('image/png', 0.9);
                status.innerText = 'กำลังบันทึกรูปพรีเมียม...';
                
                const uploadRes = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ image: pngData, type })
                });

                const uploadJson = await uploadRes.json();
                if (!uploadJson.success) throw new Error(uploadJson.error || 'Upload failed');

                return uploadJson;

            } catch (err) {
                handleRenderError(err, overlay, reportUrl);
                return null;
            }
        }

        /**
         * ฟังก์ชันแปลงรูปและแชร์ไป Facebook Page
         */
        window.renderAndShareSocial = async function(button, articleId, imagePath, uploadUrl, reportUrl, isComplete = 1) {
            // ตรวจสอบกรณีเป็นหวยแต่ยังออกไม่ครบ
            if (isComplete === 0) {
                alert('⚠️ หวยงวดนี้ยังออกไม่ครบ 100% กรุณารอให้ระบบอัปเดตผลรางวัลให้ครบถ้วนก่อนจึงจะแชร์ได้ครับ');
                return;
            }

            const form = document.getElementById('share-social-form-' + articleId);
            const imageInput = document.getElementById('share-social-image-' + articleId);

            if (!confirm('ยืนยันแปลงรูปเป็น PNG และแชร์ไปที่ Facebook Page?')) {
                return;
            }

            button.disabled = true;
            const originalText = button.innerText;
            button.innerText = 'กำลังทำงาน...';

            try {
                if (imagePath && !imagePath.toLowerCase().endsWith('.svg')) {
                    imageInput.value = imagePath;
                    form.submit();
                    return;
                }

                const result = await renderAndUploadPremiumImage(imagePath, uploadUrl, 'กำลังแปลงรูปและเตรียมแชร์ Facebook...', reportUrl, 'square');

                if (result) {
                    imageInput.value = result.path;
                    status.innerText = 'สำเร็จ! กำลังแชร์ไปที่ Facebook...';
                    setTimeout(() => form.submit(), 1000);
                } else {
                    console.log('Premium rendering failed, aborting social share.');
                    button.disabled = false;
                    button.innerText = originalText;
                }
            } catch (err) {
                console.error('Share social error:', err);
                button.disabled = false;
                button.innerText = originalText;
                alert('แชร์ไม่สำเร็จ: ' + (err.message || err));
            }
        };

        /**
         * ฟังก์ชันแชร์ไป Facebook (คงไว้สำหรับลิงก์เก่าหรือ auto flow เดิม)
         */
        window.shareToFb = async function(button, articleId, imagePath, uploadUrl, reportUrl) {
            const form = document.getElementById('share-fb-form-' + articleId);
            const imageInput = document.getElementById('share-fb-image-' + articleId);

            if (!form || !imageInput) {
                return window.renderAndShareSocial(button, articleId, imagePath, uploadUrl, reportUrl);
            }
            
            // ตรวจสอบว่าเป็นบทความทั่วไปหรือไม่ (ถ้าไม่ใช่ .svg แสดงว่าเป็นรูปปกติ)
            if (imagePath && !imagePath.toLowerCase().endsWith('.svg')) {
                console.log('Standard article detected, sharing directly without rendering.');
                imageInput.value = imagePath;
                form.submit();
                return;
            }

            const result = await renderAndUploadPremiumImage(imagePath, uploadUrl, 'กำลังเตรียมรูปภาพสำหรับ Facebook...', reportUrl, 'square');
            
            if (result) {
                imageInput.value = result.path;
                status.innerText = 'สำเร็จ! กำลังไปที่ Facebook...';
                setTimeout(() => form.submit(), 1000);
            } else {
                // สำหรับ Facebook หากพรีเมียมขัดข้อง เราจะไม่แชร์ต่อตามความต้องการของ USER
                console.log('Premium rendering failed, aborting FB share per user request.');
            }
        };

        /**
         * ฟังก์ชันแชร์ไป LINE (เรียกใช้ตัวกลาง)
         */
        window.shareToLine = async function(button, articleId, imagePath, uploadUrl, reportUrl, prefix = 'share') {
            const form = document.getElementById(prefix + '-line-form-' + articleId);
            const imageInput = document.getElementById(prefix + '-line-image-' + articleId);

            if (!form || !imageInput) {
                return window.renderAndShareSocial(button, articleId, imagePath, uploadUrl, reportUrl);
            }
            
            // For broadcast, we show confirmation before rendering
            if (prefix === 'broadcast') {
                if (!confirm('ยืนยันการ Broadcast ส่งหาผู้ติดตามทุกคน? หากส่งแล้วจะไม่สามารถดึงข้อความกลับได้')) {
                    return;
                }
            }

            // ตรวจสอบว่าเป็นบทความทั่วไปหรือไม่
            if (imagePath && !imagePath.toLowerCase().endsWith('.svg')) {
                console.log('Standard article detected, sharing to LINE directly.');
                imageInput.value = imagePath;
                form.submit();
                return;
            }
            
            const result = await renderAndUploadPremiumImage(imagePath, uploadUrl, 'กำลังเตรียมรูปภาพสำหรับ LINE...', reportUrl, 'square');
            
            if (result) {
                imageInput.value = result.path;
                status.innerText = 'สำเร็จ! กำลังส่งเข้า LINE...';
                setTimeout(() => form.submit(), 1000);
            } else {
                form.submit(); // สำหรับ LINE ให้ส่งแบบธรรมดาแทนเพื่อความต่อเนื่อง
            }
        };

        /**
         * ฟังก์ชันจัดการเมื่อการวาดรูปขัดข้อง
         */
        function handleRenderError(err, overlay, reportUrl) {
            console.error('Render error:', err);
            overlay.style.display = 'none';
            alert('การวาดรูปขัดข้อง ระบบจะส่งข้อมูลแบบปกติให้แทนครับ');
            
            if (reportUrl) {
                fetch(reportUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ error: err.message, stack: err.stack })
                });
            }
        }

        /**
         * ระบบ Auto-Trigger: ตรวจสอบคำสั่งจาก URL เพื่อกดปุ่มแชร์อัตโนมัติ
         * ตัวอย่างลิงก์: .../admin/articles?auto_share=fb&article_id=123
         */
        window.addEventListener('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const autoShare = urlParams.get('auto_share'); // 'fb' หรือ 'line'
            const articleId = urlParams.get('article_id');

            if (autoShare && articleId) {
                const buttonId = `btn-share-social-${articleId}`;
                const button = document.getElementById(buttonId);
                
                if (button) {
                    console.log(`Auto-triggering ${autoShare} share for article ${articleId}`);
                    // หน่วงเวลาเล็กน้อยเพื่อให้ระบบ Canvg พร้อมทำงาน
                    setTimeout(() => button.click(), 1500);
                }
            }
        });
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

    function openImportModal() {
        document.getElementById('import-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeImportModal() {
        document.getElementById('import-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
  </script>
@endpush
