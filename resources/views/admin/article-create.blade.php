@extends('layouts.admin')

@section('title', 'Supernumber Admin | เพิ่มบทความใหม่')

@section('content')
  <style>
    .admin-preview-box {
      margin-top: 12px;
      display: none;
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
      <h1>เพิ่มบทความใหม่</h1>
      <p class="admin-subtitle">เขียนเนื้อหาใหม่และตั้งค่า SEO เพื่อเผยแพร่บนเว็บไซต์</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.articles') }}" class="admin-button admin-button--muted">ยกเลิกและกลับ</a>
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

  <section class="admin-card admin-feature-card">
    <form action="{{ route('admin.articles') }}" method="post" enctype="multipart/form-data" class="admin-form">
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

      <div class="admin-field" style="margin-bottom: 24px;">
        <label>รูปปกหลัก (Primary Cover - แนะนำ 1200x630)</label>
        <div class="admin-drop-zone" data-drop-zone>
          <div class="admin-drop-zone__icon">📸</div>
          <div class="admin-drop-zone__text">ลากรูปปกหลักมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</div>
          <input type="file" name="cover_image" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
        </div>
        <div class="admin-preview-box" data-preview-box>
          <img src="" class="admin-preview-img" data-preview-img style="max-width: 300px;" />
          <div class="admin-preview-info" data-preview-info></div>
        </div>
      </div>

      <div class="admin-image-grid">
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

  @push('scripts')
    <script>
      (() => {
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

        document.querySelectorAll("[data-drop-zone]").forEach((zone) => {
          const input = zone.querySelector("[data-drop-zone-input]");
          const previewBox = zone.parentElement.querySelector("[data-preview-box]");
          const previewImg = zone.parentElement.querySelector("[data-preview-img]");
          const previewInfo = zone.parentElement.querySelector("[data-preview-info]");
          const MAX_SIZE = 20 * 1024 * 1024;

          const validateAndPreview = (file) => {
            if (!file) return;
            if (file.size > MAX_SIZE) {
              alert(`🚨 ไฟล์ใหญ่เกินไป! \n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB \nระบบรองรับได้ไม่เกิน 20 MB ครับ`);
              input.value = "";
              previewBox.style.display = "none";
              return;
            }
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
          ["dragover", "dragenter"].forEach(t => zone.addEventListener(t, e => { e.preventDefault(); zone.classList.add("is-dragover"); }));
          ["dragleave", "dragend", "drop"].forEach(t => zone.addEventListener(t, () => zone.classList.remove("is-dragover")));
          zone.addEventListener("drop", e => {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) {
              input.files = e.dataTransfer.files;
              validateAndPreview(e.dataTransfer.files[0]);
            }
          });
        });
      })();
    </script>
  @endpush
@endsection
