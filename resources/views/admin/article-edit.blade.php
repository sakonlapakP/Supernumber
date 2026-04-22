@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขบทความ')

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
      min-height: 100px;
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
      position: relative;
    }
    .admin-preview-img {
      max-width: 180px;
      border-radius: 10px;
      border: 1px solid #d8e0ec;
      display: block;
    }
    .admin-preview-info {
      font-size: 12px;
      color: #94a3b8;
      margin-top: 6px;
    }
    .admin-image-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    @media (max-width: 768px) {
      .admin-image-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>แก้ไขบทความ</h1>
      <p class="admin-subtitle">แก้ไขข้อมูลบทความและการเผยแพร่</p>
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

  <section class="admin-card admin-feature-card">
    <form
      id="article-update-form"
      action="{{ route('admin.articles.update', $article) }}"
      method="post"
      enctype="multipart/form-data"
      class="admin-form"
      data-has-cover="{{ ($article->cover_image_path || $article->cover_image_landscape_path || $article->cover_image_square_path) ? '1' : '0' }}"
    >
      @csrf

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title', $article->title) }}" required />
      </div>

      <div class="admin-field">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $article->slug) }}" />
      </div>

      <div class="admin-field">
        <label for="excerpt">คำเกริ่นสั้น</label>
        <textarea id="excerpt" name="excerpt" class="admin-input" style="min-height: 90px; padding-top: 12px;">{{ old('excerpt', $article->excerpt) }}</textarea>
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
        <textarea id="content" name="content" class="admin-input" style="display: none;">{{ old('content', $article->content) }}</textarea>
      </div>

      <div class="admin-field">
        <label>คำอธิบายเมตา (Meta Description)</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description', $article->meta_description) }}" placeholder="คำโปรยสำหรับ Google (ไม่เกิน 500 ตัวอักษร)" />
      </div>

      <div class="admin-field">
        <label>5 Keywords หลัก (SEO)</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords', $article->keywords) }}" placeholder="เช่น: เบอร์มงคล, เสริมดวง, ประวัติจิ้มมี่" />
        <p class="admin-subtitle" style="margin: 0;">เน้นคำสำคัญที่เป็นหัวใจของบทความ</p>
      </div>

      <div class="admin-field">
        <label>10 Keywords รอง (LSI Keywords)</label>
        <input type="text" name="lsi_keywords" class="admin-input" value="{{ old('lsi_keywords', $article->lsi_keywords) }}" placeholder="คำใกล้เคียง เช่น: เลขศาสตร์, เปลี่ยนเบอร์มือถือ, คัดเบอร์พิเศษ" />
        <p class="admin-subtitle" style="margin: 0;">ช่วยให้ Google ค้นพบง่ายขึ้นจากระยะค้นหาที่กว้างขึ้น</p>
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่</label>
        <input
          type="datetime-local"
          id="published_at"
          name="published_at"
          class="admin-input"
          value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\\TH:i')) }}"
          @disabled($article->is_published)
        />
        @if ($article->is_published)
          <p class="admin-subtitle" style="margin: 0;">โพสต์นี้เผยแพร่แล้ว จึงล็อกเวลาเผยแพร่ไว้ (ใช้ Archive Post หากต้องการถอดออก)</p>
        @endif
      </div>

      <div class="admin-image-grid">
        <div class="admin-field">
          <label>รูปหน้ารวมบทความ (แนวนอน 16:9 / 4:3)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <div class="admin-drop-zone__icon">🖼️</div>
            <div class="admin-drop-zone__text">ลากรูปใหม่มาวางที่นี่เพื่อเปลี่ยนรูป หรือคลิกเลือกไฟล์</div>
            <input type="file" name="cover_image_landscape" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
          </div>
          <div class="admin-preview-box" data-preview-box style="{{ ($article->cover_image_landscape_path || $article->cover_image_path) ? 'display:block;' : '' }}">
            <img src="{{ $article->cover_image_landscape_path ? asset('storage/' . $article->cover_image_landscape_path) : ( $article->cover_image_path ? asset('storage/' . $article->cover_image_path) : '' ) }}" class="admin-preview-img" data-preview-img />
            <div class="admin-preview-info" data-preview-info>
              @if ($article->cover_image_landscape_path || $article->cover_image_path)
                รูปปัจจุบัน: {{ $article->cover_image_landscape_path ?: $article->cover_image_path }}
              @endif
            </div>
          </div>
        </div>

        <div class="admin-field">
          <label>รูปหน้ารายละเอียดบทความ (สี่เหลี่ยมจัตุรัส 1:1)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <div class="admin-drop-zone__icon">⏹️</div>
            <div class="admin-drop-zone__text">ลากรูปใหม่มาวางที่นี่เพื่อเปลี่ยนรูป หรือคลิกเลือกไฟล์</div>
            <input type="file" name="cover_image_square" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
          </div>
          <div class="admin-preview-box" data-preview-box style="{{ ($article->cover_image_square_path || $article->cover_image_path) ? 'display:block;' : '' }}">
            <img src="{{ $article->cover_image_square_path ? asset('storage/' . $article->cover_image_square_path) : ( $article->cover_image_path ? asset('storage/' . $article->cover_image_path) : '' ) }}" class="admin-preview-img" data-preview-img style="aspect-ratio:1/1; object-fit:cover;" />
            <div class="admin-preview-info" data-preview-info>
              @if ($article->cover_image_square_path || $article->cover_image_path)
                รูปปัจจุบัน: {{ $article->cover_image_square_path ?: $article->cover_image_path }}
              @endif
            </div>
          </div>
        </div>
      </div>

      <label class="admin-field" style="grid-template-columns: auto 1fr; align-items: center; gap: 10px; display: grid;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published)) />
        <span style="font-size: 14px;">เผยแพร่บทความ</span>
      </label>
      <p class="admin-subtitle" style="margin: 0;">สถานะปัจจุบัน: <strong>{{ $article->is_published ? 'เผยแพร่แล้ว' : 'ฉบับร่าง' }}</strong></p>

      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items: center;">
        <button type="submit" class="admin-button">บันทึกการเปลี่ยนแปลง</button>
        @if (session('admin_user_role') === \App\Models\User::ROLE_MANAGER)
          <form action="{{ route('admin.articles.delete', $article) }}" method="POST" onsubmit="return confirm('ยืนยันลบบทความและรูปภาพทั้งหมดถาวร? ไม่สามารถกู้คืนได้');" style="margin: 0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="admin-button" style="background:#dc3545;">ลบบทความ</button>
          </form>
        @endif
      </div>
    </form>
    <div
      id="article-cover-confirm-modal"
      style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 9999; display: none; place-items: center; padding: 20px;"
    >
      <div style="width:min(560px, 100%); background:#fff; border-radius:16px; border:1px solid #d8e0ec; padding:18px; text-align:center;">
        <h3 style="margin:0; font-size:20px;">ยืนยันการบันทึก</h3>
        <p style="margin:10px 0 0; color:#4f5f7b; line-height:1.6; text-align:center;">ถ้าไม่ใส่รูปจะใช้รูปเก่า ต้องการดำเนินการต่อหรือไม่?</p>
        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
          <button type="button" id="article-cover-confirm-continue" class="admin-button">ดำเนินการต่อ</button>
          <button type="button" id="article-cover-confirm-close" class="admin-button" style="background:#60708e;">ปิดเพื่อกลับไปใส่รูป</button>
        </div>
      </div>
    </div>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 16px;">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title">คอมเมนต์ ({{ $comments->count() }})</h2>
        <p class="admin-feature-card__hint">คอมเมนต์ทั้งหมดของโพสต์นี้ พร้อมปุ่มเก็บซ่อน / ยกเลิกเก็บซ่อน</p>
      </div>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เวลา</th>
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
              <td>{{ $comment->commenter_name }}</td>
              <td style="max-width: 460px; white-space: normal; line-height: 1.6;">{{ $comment->content }}</td>
              <td>
                @if ($comment->status === \App\Models\ArticleComment::STATUS_APPROVED)
                  <span class="admin-status-pill admin-status-pill--active">อนุมัติแล้ว</span>
                @elseif ($comment->status === \App\Models\ArticleComment::STATUS_REJECTED)
                  <span class="admin-status-pill" style="background:#fef3f2; color:#b42318; border-color:#fecdca;">เก็บซ่อนแล้ว</span>
                @else
                  <span class="admin-status-pill admin-status-pill--hold">รอพิจารณา</span>
                @endif
              </td>
              <td class="admin-action-cell">
                @if ($comment->status !== \App\Models\ArticleComment::STATUS_REJECTED)
                  <form action="{{ route('admin.articles.comments.archive', [$article, $comment]) }}" method="post" onsubmit="return confirm('เก็บซ่อนคอมเมนต์นี้หรือไม่?');">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact" style="background:#b45309;">เก็บซ่อน</button>
                  </form>
                @else
                  <form action="{{ route('admin.articles.comments.unarchive', [$article, $comment]) }}" method="post" onsubmit="return confirm('ยกเลิกเก็บซ่อนคอมเมนต์นี้หรือไม่?');">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact" style="background:#2563eb;">ยกเลิกเก็บซ่อน</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="admin-muted">ยังไม่มีคอมเมนต์ในโพสต์นี้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      const updateForm = document.getElementById("article-update-form");
      const coverInputs = Array.from(document.querySelectorAll("[data-cover-input]"));
      const modal = document.getElementById("article-cover-confirm-modal");
      const continueBtn = document.getElementById("article-cover-confirm-continue");
      const closeBtn = document.getElementById("article-cover-confirm-close");

      let bypassCoverConfirm = false;

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

      if (updateForm && modal && continueBtn && closeBtn) {
        // Drag & Drop Handling
        document.querySelectorAll("[data-drop-zone]").forEach((zone) => {
          const input = zone.querySelector("[data-drop-zone-input]");
          const previewBox = zone.parentElement.querySelector("[data-preview-box]");
          const previewImg = zone.parentElement.querySelector("[data-preview-img]");
          const previewInfo = zone.parentElement.querySelector("[data-preview-info]");

          const MAX_SIZE = 10 * 1024 * 1024; // 10MB Limit

          const validateAndPreview = (file) => {
            if (!file) return;
            
            if (file.size > MAX_SIZE) {
              alert(`🚨 ไฟล์ใหญ่เกินไป! \n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB \nระบบรองรับได้ไม่เกิน 20 MB ครับ \n\nกรุณาลดขนาดรูปก่อนอัปโหลดนะครับ`);
              input.value = ""; // Clear input
              return;
            }

            if (!file.type.startsWith("image/")) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
              previewImg.src = e.target.result;
              previewBox.style.display = "block";
              previewInfo.innerHTML = `🌟 ไฟล์ใหม่ที่เลือก: <code>${file.name}</code> (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
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
                alert(`🚨 ไฟล์ใหญ่เกินไปครับพี่! \n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB \n\nเซิร์ฟเวอร์ระบบรองรับได้ไม่เกิน 10 MB เพื่อความรวดเร็วของเว็บครับ \nรบกวนพี่ช่วย "ย่อรูป" ก่อนอัพโหลดอีกรอบนะครับ 🙏`);
                return;
              }
              input.files = e.dataTransfer.files;
              validateAndPreview(file);
            }
          });
        });

        updateForm.addEventListener("submit", (event) => {
          const hasCover = updateForm.dataset.hasCover === "1";
          const coverInputs = Array.from(document.querySelectorAll("[data-drop-zone-input]"));
          const hasNewFile = coverInputs.some((input) => input.files && input.files.length > 0);

          if (bypassCoverConfirm || !hasCover || hasNewFile) {
            return;
          }

          event.preventDefault();
          modal.style.display = "grid";
        });

        continueBtn.addEventListener("click", () => {
          bypassCoverConfirm = true;
          modal.style.display = "none";
          updateForm.requestSubmit();
        });

        closeBtn.addEventListener("click", () => {
          modal.style.display = "none";
          if (coverInputs[0]) {
            coverInputs[0].focus();
          }
        });
      }
    })();
  </script>
@endpush
