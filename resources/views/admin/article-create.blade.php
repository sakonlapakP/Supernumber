@extends('layouts.admin')

@section('title', 'Supernumber Admin | สร้างบทความใหม่')

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
    .admin-drop-zone__icon { font-size: 24px; color: #94a3b8; }
    .admin-drop-zone__text { font-size: 14px; color: #64748b; }
    .admin-drop-zone__input {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    .admin-preview-box { margin-top: 12px; position: relative; }
    .admin-preview-img {
      max-width: 180px;
      border-radius: 10px;
      border: 1px solid #d8e0ec;
      display: block;
    }
    .admin-preview-info { font-size: 12px; color: #94a3b8; margin-top: 6px; }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>สร้างบทความใหม่</h1>
      <p class="admin-subtitle">กรอกข้อมูลบทความแบบครบถ้วน เพื่อผลลัพธ์ SEO ที่ดีที่สุด</p>
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
    <form
      id="article-create-form"
      action="{{ route('articles.store.bypass') }}"
      method="post"
      enctype="multipart/form-data"
      class="admin-form"
    >
      @csrf

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title') }}" required placeholder="ระบุหัวข้อบทความ..." />
      </div>

      <div class="admin-field">
        <label for="excerpt">คำเกริ่นสั้น (Excerpt)</label>
        <textarea id="excerpt" name="excerpt" class="admin-input" style="min-height: 80px; padding-top: 12px;" placeholder="คำโปรยที่จะโชว์ในหน้ารวมบทความ...">{{ old('excerpt') }}</textarea>
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
          <div class="admin-rte__editor" contenteditable="true" data-rte-editor data-placeholder="เริ่มพิมพ์เนื้อหาที่นี่..."></div>
        </div>
        <textarea id="content" name="content" class="admin-input" style="display: none;">{{ old('content') }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label>คำอธิบายเมตา (Meta Description)</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description') }}" placeholder="คำโปรยสั้นๆ สำหรับคนค้นหาใน Google" />
      </div>

      <div class="admin-field">
        <label>5 Keywords หลัก (SEO)</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords') }}" placeholder="คัดคำสำคัญ 5 คำ" />
      </div>

      <div class="admin-field">
        <label>10 Keywords รอง (LSI Keywords)</label>
        <input type="text" name="lsi_keywords" class="admin-input" value="{{ old('lsi_keywords') }}" placeholder="คำใกล้เคียงเพื่อขยายฐานการค้นหา..." />
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่ (ไม่ใส่ = ตอนนี้)</label>
        <input type="datetime-local" id="published_at" name="published_at" class="admin-input" value="{{ old('published_at') }}" />
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label>รูปภาพบทความ (จัตุรัส 1:1)</label>
        <div class="admin-drop-zone" data-drop-zone>
          <div class="admin-drop-zone__icon">🖼️</div>
          <div class="admin-drop-zone__text">ลากรูปมาวางตรงนี้ หรือคลิกเพื่อเลือกไฟล์</div>
          <input type="file" name="upload_media_sq" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
        </div>
        
        <div class="admin-preview-box" data-preview-box style="display:none;">
          <img src="" class="admin-preview-img" data-preview-img style="aspect-ratio:1/1; object-fit:cover;" />
          <div class="admin-preview-info" data-preview-info></div>
        </div>
      </div>

      <label class="admin-field" style="grid-template-columns: auto 1fr; align-items: center; gap: 10px; display: grid; margin-top:20px;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true)) />
        <span style="font-size: 14px;">เผยแพร่ทันที</span>
      </label>

      <div class="admin-actions" style="margin-top:30px; display:flex; gap:10px;">
        <button type="submit" class="admin-button">🚀 สร้างและอัปโหลดบทความ</button>
        <a href="{{ route('admin.articles') }}" class="admin-button admin-button--secondary">ยกเลิก</a>
      </div>
    </form>
  </section>
@endsection

@section('scripts')
  <script>
    (function() {
      // 1. RTE
      document.querySelectorAll("[data-rte-shell]").forEach(shell => {
        const editor = shell.querySelector("[data-rte-editor]");
        const textarea = shell.parentElement.querySelector('textarea[name="content"]');
        const form = shell.closest("form");
        if (!editor || !textarea || !form) return;
        shell.querySelectorAll("[data-rte-cmd]").forEach(btn => {
          btn.addEventListener("click", () => {
            const cmd = btn.dataset.rteCmd;
            const val = btn.dataset.rteValue ?? null;
            editor.focus();
            document.execCommand(cmd, false, val);
          });
        });
        shell.querySelectorAll("[data-rte-action='link']").forEach(btn => {
          btn.addEventListener("click", () => {
            const url = window.prompt("ใส่ URL:");
            if (!url) return;
            editor.focus();
            document.execCommand("createLink", false, url);
          });
        });
        form.addEventListener("submit", () => { textarea.value = editor.innerHTML.trim(); });
      });

      // 2. Preview
      document.querySelectorAll('[data-drop-zone-input]').forEach(input => {
        input.addEventListener('change', function(e) {
          const file = e.target.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
              const container = input.closest('.admin-field');
              const previewImg = container.querySelector('[data-preview-img]');
              const previewBox = container.querySelector('[data-preview-box]');
              const previewInfo = container.querySelector('[data-preview-info]');
              if (previewImg) previewImg.src = event.target.result;
              if (previewBox) previewBox.style.display = 'block';
              if (previewInfo) previewInfo.innerText = `🌟 เลือกไฟล์: ${file.name}`;
            };
            reader.readAsDataURL(file);
          }
        });
      });

      // 3. Feedback
      const form = document.getElementById('article-create-form');
      form.addEventListener('submit', () => {
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = '🚀 กำลังสร้างบทความ...';
      });
    })();
  </script>
@endsection
