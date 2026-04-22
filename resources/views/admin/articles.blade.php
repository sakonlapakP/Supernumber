@extends('layouts.admin')

@section('title', 'Supernumber Admin | บทความ')

@section('content')
  <style>
    .admin-drop-zone {
      position: relative;
      border: 2px dashed #d8e0ec;
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      background: #f8fbff;
      transition: all 0.2s ease;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
      min-height: 140px;
    }
    .admin-drop-zone:hover, .admin-drop-zone.is-dragover {
      border-color: #1d4f9f;
      background: #f0f7ff;
    }
    .admin-drop-zone__icon {
      font-size: 24px;
      color: #94a3b8;
    }
    .admin-drop-zone__text {
      font-size: 14px;
      color: #64748b;
    }
    .admin-drop-zone__input {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    .admin-preview-box {
      margin-top: 12px;
      display: none;
      position: relative;
    }
    .admin-preview-img {
      max-width: 240px;
      border-radius: 10px;
      border: 1px solid #d8e0ec;
      display: block;
    }
    .admin-preview-info {
      font-size: 12px;
      color: #94a3b8;
      margin-top: 6px;
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>บทความ</h1>
      <p class="admin-subtitle">จัดการบทความทั้งหมดแบบลิสต์ พร้อมปุ่มจัดการด้านท้ายแต่ละแถว</p>
    </div>
    <div class="admin-page-actions">
      @if(session('admin_user_role') === \App\Models\User::ROLE_MANAGER)
        <div style="display: flex; gap: 8px; margin-right: 15px; padding-right: 15px; border-right: 1px solid var(--admin-border);">
          <a href="/admin/utils/storage-link" class="admin-button admin-button--compact" style="background: var(--admin-warning); color: #000;" onclick="return confirm('ยืนยันหน้าการเชื่อมต่อรูปภาพใช่หรือไม่?')">🔧 ซ่อมรูปแตก</a>
          <a href="/admin/utils/migrate" class="admin-button admin-button--compact" style="background: var(--admin-danger); color: #fff;" onclick="return confirm('⚠️ คำเตือน: คุณกำลังจะอัปเดตโครงสร้างฐานข้อมูล ยืนยันใช่หรือไม่?')">🚀 อัปเกรดฐานข้อมูล</a>
        </div>
      @endif
      
      <div class="admin-kpi">
        <div class="admin-kpi__label">บทความที่แสดงอยู่</div>
        <div class="admin-kpi__value"><span id="article-visible-count">{{ number_format($articles->count()) }}</span></div>
        <div class="admin-summary">จาก {{ number_format($articles->total()) }} บทความ</div>
      </div>
      <button type="button" id="article-add-toggle" class="admin-button admin-button--compact" style="margin-left: 10px;">เพิ่มบทความ</button>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">
      <ul style="margin: 0; padding-left: 18px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <div class="admin-panel-stack">

    <section id="article-add-panel" class="admin-card admin-feature-card" hidden>
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">เพิ่มบทความใหม่</h2>
          <p class="admin-feature-card__hint">บทความที่เผยแพร่แล้วจะขึ้นหน้า /articles อัตโนมัติ</p>
        </div>
      </div>

      <div class="admin-field" style="gap: 10px; margin-bottom: 12px;">
        <p class="admin-subtitle" style="margin: 0;">
          วิธีใช้แท็กจากรูปแบบข้อความ: วางตามตัวอย่างนี้ในเนื้อหาบทความ ระบบจะจัดสไตล์อัตโนมัติ
        </p>
        <pre style="margin:0; white-space: pre-wrap; word-break: break-word; padding: 14px; border-radius: 12px; border:1px solid #d8e0ec; background:#f8fbff; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 13px; line-height: 1.6;">✅ 14 41 มีเหตุผล ไหวพริบปัญญาดี เจรจาเก่ง หาเงินเก่ง
✅ 15 51 สติปัญญาโดดเด่น มีเมตตา มองโลกในแง่ดี ชีวิตการเงินดี
✅ 45 54 ถ่ายทอดความรู้ได้ดี มีความรอบรู้ วางแผนการเงินดี
✅ 56 65 มีความคิดสร้างสรรค์ จัดการปัญหาได้ดี ฉลาดทำมาหากิน

📲 เบอร์ที่ supernumber แนะนำ:
0648924651 0644536245 0644549241
0647924656 0647924651 0644549245
0649246516 0649246561 0644516151</pre>
        <p class="admin-subtitle" style="margin: 0;">
          tag เบอร์: ถ้าอยู่ในระบบและสถานะเป็น <strong>active</strong> จะกดได้, ถ้าไม่พบหรือสถานะอื่นจะเป็นสีเทาและกดไม่ได้
        </p>
      </div>

      <form action="{{ route('admin.articles.store') }}" method="post" enctype="multipart/form-data" class="admin-form">
        @csrf
        <div class="admin-field">
          <label for="title">หัวข้อบทความ</label>
          <input type="text" id="title" name="title" class="admin-input" value="{{ old('title') }}" required />
        </div>

        <div class="admin-field">
          <label>คำอธิบายเมตา (Meta Description)</label>
          <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description') }}" placeholder="คำโปรยสำหรับ Google (ไม่เกิน 500 ตัวอักษร)" />
        </div>

        <div class="admin-field">
          <label>5 Keywords หลัก (SEO)</label>
          <input type="text" name="keywords" class="admin-input" value="{{ old('keywords') }}" placeholder="เช่น: เบอร์มงคล, เสริมดวง, ประวัติจิ้มมี่" />
          <p class="admin-subtitle" style="margin: 0;">เน้นคำสำคัญที่เป็นหัวใจของบทความ</p>
        </div>

        <div class="admin-field">
          <label>10 Keywords รอง (LSI Keywords)</label>
          <input type="text" name="lsi_keywords" class="admin-input" value="{{ old('lsi_keywords') }}" placeholder="คำใกล้เคียง เช่น: เลขศาสตร์, เปลี่ยนเบอร์มือถือ, คัดเบอร์พิเศษ" />
          <p class="admin-subtitle" style="margin: 0;">ช่วยให้ Google ค้นพบง่ายขึ้นจากระยะค้นหาที่กว้างขึ้น</p>
        </div>

        <div class="admin-field">
          <label for="slug">Slug (ถ้าไม่ใส่ระบบจะสร้างให้)</label>
          <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug') }}" placeholder="seo-for-lucky-number" />
        </div>

        <div class="admin-field">
          <label for="excerpt">คำเกริ่นสั้น</label>
          <textarea id="excerpt" name="excerpt" class="admin-input" style="min-height: 90px; padding-top: 12px;">{{ old('excerpt') }}</textarea>
        </div>

        <div class="admin-field">
          <label for="content">เนื้อหาบทความ</label>
          <div class="admin-rte" data-rte-shell>
            <div class="admin-rte__toolbar">
              <button type="button" class="admin-rte__btn" data-rte-cmd="bold">B</button>
              <button type="button" class="admin-rte__btn" data-rte-cmd="italic">I</button>
              <button type="button" class="admin-rte__btn" data-rte-cmd="underline">U</button>
              <button type="button" class="admin-rte__btn" data-rte-cmd="formatBlock" data-rte-value="h2">H2</button>
              <button type="button" class="admin-rte__btn" data-rte-cmd="insertUnorderedList">• รายการ</button>
              <button type="button" class="admin-rte__btn" data-rte-cmd="insertOrderedList">1. ลำดับ</button>
              <button type="button" class="admin-rte__btn" data-rte-action="link">ลิงก์</button>
              <button type="button" class="admin-rte__btn" data-rte-cmd="removeFormat">ล้างรูปแบบ</button>
            </div>
            <div class="admin-rte__editor" contenteditable="true" data-rte-editor data-placeholder="พิมพ์เนื้อหาบทความที่นี่..."></div>
          </div>
          <textarea id="content" name="content" class="admin-input" style="display: none;">{{ old('content') }}</textarea>
        </div>

        <div class="admin-field">
          <label for="meta_description">คำอธิบายเมตา</label>
          <input type="text" id="meta_description" name="meta_description" class="admin-input" value="{{ old('meta_description') }}" maxlength="255" />
        </div>

        <div class="admin-field">
          <label>รูปหน้ารวมบทความ (แนวนอน 16:9 / 4:3)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <div class="admin-drop-zone__icon">🖼️</div>
            <div class="admin-drop-zone__text">ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
            <input type="file" name="cover_image_landscape" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
          </div>
          <div class="admin-preview-box" data-preview-box>
            <img src="" class="admin-preview-img" data-preview-img />
            <div class="admin-preview-info" data-preview-info></div>
          </div>
        </div>

        <div class="admin-field">
          <label>รูปหน้ารายละเอียดบทความ (สี่เหลี่ยมจัตุรัส 1:1)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <div class="admin-drop-zone__icon">⏹️</div>
            <div class="admin-drop-zone__text">ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
            <input type="file" name="cover_image_square" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
          </div>
          <div class="admin-preview-box" data-preview-box>
            <img src="" class="admin-preview-img" data-preview-img style="aspect-ratio:1/1; object-fit:cover;" />
            <div class="admin-preview-info" data-preview-info></div>
          </div>
        </div>

        <div class="admin-field">
          <label for="published_at">เวลาที่ต้องการเผยแพร่ (ไม่ใส่ = ตอนนี้)</label>
          <input type="datetime-local" id="published_at" name="published_at" class="admin-input" value="{{ old('published_at') }}" />
        </div>

        <label class="admin-field" style="grid-template-columns: auto 1fr; align-items: center; gap: 10px; display: grid;">
          <input type="checkbox" name="is_published" value="1" @checked(old('is_published')) />
          <span style="font-size: 14px;">เผยแพร่ทันที</span>
        </label>

        <button type="submit" class="admin-button">บันทึกบทความ</button>
      </form>
    </section>
  </div>

  <section class="admin-card admin-table-card">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title">รายการบทความ</h2>
        <p class="admin-feature-card__hint">ลิสต์บทความทั้งหมดพร้อมปุ่มจัดการด้านท้ายแถว</p>
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
        <tbody>
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
              <td>{{ optional($article->published_at)->format('Y-m-d H:i') ?: '-' }}</td>
              <td class="admin-action-cell">
                <div class="admin-action-group">
                  <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" rel="noopener noreferrer" class="admin-button admin-button--compact" style="background:#1d4f9f;">ดู</a>
                  <a href="{{ route('admin.articles.edit', $article) }}" class="admin-button admin-button--compact">แก้ไข</a>
                  @if (session('admin_user_role') === \App\Models\User::ROLE_MANAGER)
                    <form action="{{ route('admin.articles.delete', $article) }}" method="POST" onsubmit="return confirm('ยืนยันลบบทความและรูปภาพทั้งหมดถาวร? ไม่สามารถกู้คืนได้');" style="display:inline;">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="admin-button admin-button--compact" style="background:#dc3545;">ลบ</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="admin-muted">ยังไม่มีบทความ</td>
            </tr>
          @endforelse
          <tr id="articles-empty-row" hidden>
            <td colspan="4" class="admin-muted">ไม่พบบทความที่ตรงกับคำค้นหา</td>
          </tr>
        </tbody>
      </table>
    </div>

    @if ($articles->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการบทความ">
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
      const addToggle = document.getElementById("article-add-toggle");
      const addPanel = document.getElementById("article-add-panel");
      const searchInput = document.getElementById("article-search");
      const rows = Array.from(document.querySelectorAll(".article-row"));
      const emptyRow = document.getElementById("articles-empty-row");
      const visibleCount = document.getElementById("article-visible-count");

      if (addToggle && addPanel) {
        addToggle.addEventListener("click", () => {
          const isHidden = addPanel.hidden;
          addPanel.hidden = !isHidden;
        });
      }

      const initRichText = (shell) => {
        const editor = shell.querySelector("[data-rte-editor]");
        const textarea = shell.parentElement.querySelector('textarea[name="content"]');
        const form = shell.closest("form");
        if (!editor || !textarea || !form) return;

        editor.innerHTML = textarea.value || "";

        shell.querySelectorAll("[data-rte-cmd]").forEach((button) => {
          button.addEventListener("click", () => {
            const command = button.dataset.rteCmd;
            const value = button.dataset.rteValue ?? null;
            editor.focus();
            document.execCommand(command, false, value);
          });
        });

        shell.querySelectorAll("[data-rte-action='link']").forEach((button) => {
          button.addEventListener("click", () => {
            const url = window.prompt("ใส่ URL เช่น https://example.com");
            if (!url) return;
            editor.focus();
            document.execCommand("createLink", false, url);
          });
        });

        form.addEventListener("submit", () => {
          textarea.value = editor.innerHTML.trim();
        });
      };

      document.querySelectorAll("[data-rte-shell]").forEach(initRichText);

      // Drag & Drop Handling
      document.querySelectorAll("[data-drop-zone]").forEach((zone) => {
        const input = zone.querySelector("[data-drop-zone-input]");
        const previewBox = zone.parentElement.querySelector("[data-preview-box]");
        const previewImg = zone.parentElement.querySelector("[data-preview-img]");
        const previewInfo = zone.parentElement.querySelector("[data-preview-info]");

        const MAX_SIZE = 20 * 1024 * 1024; // 20MB

        const validateAndPreview = (file) => {
          if (!file) return;
          
          if (file.size > MAX_SIZE) {
            alert(`🚨 ไฟล์ใหญ่เกินไป! \n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB \nระบบรองรับได้ไม่เกิน 20 MB ครับ \n\nกรุณาลดขนาดรูปก่อนอัปโหลดนะครับ`);
            input.value = ""; // Clear input
            previewBox.style.display = "none";
            return;
          }

          if (!file.type.startsWith("image/")) return;
          
          const reader = new FileReader();
          reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewBox.style.display = "block";
            previewInfo.textContent = `ไฟล์: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
          };
          reader.readAsDataURL(file);
        };

        input.addEventListener("change", () => {
          if (input.files.length > 0) validateAndPreview(input.files[0]);
        });

        ["dragover", "dragenter"].forEach((type) => {
          zone.addEventListener(type, (e) => {
            e.preventDefault();
            zone.classList.add("is-dragover");
          });
        });

        ["dragleave", "dragend", "drop"].forEach((type) => {
          zone.addEventListener(type, () => {
            zone.classList.remove("is-dragover");
          });
        });

        zone.addEventListener("drop", (e) => {
          e.preventDefault();
          if (e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0];
            if (file.size > MAX_SIZE) {
              alert(`🚨 ไฟล์ใหญ่เกินไป! \n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB \nระบบรองรับได้ไม่เกิน 20 MB ครับ`);
              return;
            }
            input.files = e.dataTransfer.files;
            validateAndPreview(file);
          }
        });
      });
    })();
  </script>
@endpush
