@extends('layouts.admin')

@section('title', 'Supernumber Admin | สร้างบทความใหม่')

@section('content')
  <style>
    .admin-drop-zone { border: 2px dashed #d8e0ec; border-radius: 12px; padding: 24px; text-align: center; background: #f8fbff; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 100px; position: relative; transition: all 0.3s; }
    .admin-drop-zone.is-dragover { border-color: #1d4f9f; background: #f0f7ff; }
    .admin-drop-zone__input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .admin-preview-img { max-width: 180px; border-radius: 10px; border: 1px solid #d8e0ec; display: block; }
    .admin-preview-box { margin-top: 12px; }
    .admin-preview-info { font-size: 12px; color: #94a3b8; margin-top: 6px; }
    .admin-image-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
    @media (max-width: 768px) {
      .admin-image-grid { grid-template-columns: 1fr; }
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>สร้างบทความใหม่</h1>
      <p class="admin-subtitle">กรอกข้อมูลและอัปโหลดรูปภาพ (ระบบ Standard Upload + Drag & Drop)</p>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">
      <ul style="margin: 0; padding-left: 18px;">
        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
      </ul>
    </div>
  @endif

  <section class="admin-card admin-feature-card">
    <form id="main-create-form" action="{{ route('admin.articles.store') }}" method="post" enctype="multipart/form-data" class="admin-form">
      @csrf

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title') }}" required placeholder="ระบุหัวข้อบทความ..." />
      </div>

      <div class="admin-field" style="margin-top:20px; border-left: 4px solid #3b82f6; padding-left: 15px;">
        <label style="font-size: 16px; color: #1e293b; font-weight: bold;">เนื้อหาบทความ (สำหรับแสดงบนหน้าเว็บหลัก)</label>
        <div class="admin-rte">
          <div class="admin-rte__toolbar">
            <button type="button" class="admin-rte__btn" onclick="execCmd('bold')">B</button>
            <button type="button" class="admin-rte__btn" onclick="execCmd('italic')">I</button>
            <button type="button" class="admin-rte__btn" onclick="execCmd('underline')">U</button>
            <button type="button" class="admin-rte__btn" onclick="execCmd('formatBlock', 'h2')">H2</button>
            <button type="button" class="admin-rte__btn" onclick="execCmd('insertUnorderedList')">• รายการ</button>
            <button type="button" class="admin-rte__btn" onclick="addLink()">ลิงก์</button>
            <button type="button" class="admin-rte__btn" onclick="execCmd('removeFormat')">ล้างรูปแบบ</button>
          </div>
          <div id="rich-editor" class="admin-rte__editor" contenteditable="true" style="min-height: 400px; font-size: 16px; line-height: 1.8;"></div>
        </div>
        <textarea id="hidden-content" name="content" style="display: none;">{{ old('content') }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label for="excerpt">คำเกริ่นสั้น (Excerpt สำหรับแสดงบนการ์ดหน้ารวม)</label>
        <textarea name="excerpt" class="admin-input" style="min-height: 60px; padding-top: 12px;" placeholder="พิมพ์คำโปรยสั้นๆ... (ไม่บังคับ)">{{ old('excerpt') }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label> SEO Meta Description (สำหรับ Google)</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description') }}" />
      </div>

      <div class="admin-field">
        <label>Keywords (สำหรับ Google)</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords') }}" />
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่ (เว้นว่างไว้เพื่อเผยแพร่ตอนนี้เลย)</label>
        <input type="datetime-local" name="published_at" class="admin-input" value="{{ old('published_at') }}" />
      </div>

      <div class="admin-image-grid">
        <div class="admin-field" style="margin-top:30px; border-left: 4px solid #2563eb; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปหน้ารวมบทความ (แนวนอน 16:9 / 4:3)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <span class="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</span>
            <input type="file" name="upload_media_land" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
          </div>
          <div class="admin-preview-box" data-preview-box style="display:none;">
            <img src="" class="admin-preview-img" data-preview-img style="aspect-ratio:16/9; object-fit:cover; border: 2px solid #2563eb;" />
            <p class="admin-preview-info" data-preview-info style="color: #2563eb; font-weight: bold;"></p>
          </div>
        </div>

        <div class="admin-field" style="margin-top:30px; border-left: 4px solid #10b981; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปภาพบทความ (จัตุรัส 1:1)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <span class="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</span>
            <input type="file" name="upload_media_sq" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
          </div>
          <div class="admin-preview-box" data-preview-box style="display:none;">
            <img src="" class="admin-preview-img" data-preview-img style="aspect-ratio:1/1; object-fit:cover; border: 2px solid #10b981;" />
            <p class="admin-preview-info" data-preview-info style="color: #059669; font-weight: bold;"></p>
          </div>
        </div>
      </div>

      <label style="display:flex; align-items:center; gap:10px; margin-top:30px; font-size: 16px; font-weight: bold;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true)) style="width: 20px; height: 20px;" /> เผยแพร่ทันที
      </label>

      <div class="admin-actions" style="margin-top:30px;">
        <button type="submit" class="admin-button" style="font-size: 16px; padding: 12px 24px;">🚀 สร้างและอัปโหลดบทความ</button>
        <a href="{{ route('admin.articles') }}" class="admin-button admin-button--secondary" style="font-size: 16px; padding: 12px 24px;">ยกเลิก</a>
      </div>
    </form>
  </section>
@endsection

@section('scripts')
  <script>
  window.execCmd = (cmd, val = null) => {
    const editor = document.getElementById('rich-editor');
    editor.focus();
    document.execCommand(cmd, false, val);
    window.syncContent();
  };

  window.addLink = () => {
    const url = prompt("ใส่ URL:");
    if (url) window.execCmd("createLink", url);
  };

  window.syncContent = () => {
    const editor = document.getElementById('rich-editor');
    const hiddenContent = document.getElementById('hidden-content');
    if (editor && hiddenContent) hiddenContent.value = editor.innerHTML;
  };

  const initDropZone = (zone) => {
    const input = zone.querySelector('[data-drop-zone-input]');
    const previewBox = zone.parentElement.querySelector('[data-preview-box]');
    const previewImg = zone.parentElement.querySelector('[data-preview-img]');
    const previewInfo = zone.parentElement.querySelector('[data-preview-info]');
    const dropText = zone.querySelector('.drop-text');
    const maxSize = 20 * 1024 * 1024;

    const updatePreview = (file) => {
      if (!file || !file.type.startsWith('image/')) return;

      if (file.size > maxSize) {
        alert(`🚨 ไฟล์ใหญ่เกินไป!\n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB\nระบบรองรับได้ไม่เกิน 20 MB ครับ`);
        input.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        previewImg.src = e.target.result;
        previewBox.style.display = 'block';
        previewInfo.innerText = `✅ รูปพร้อมอัปโหลด: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
        dropText.innerText = 'เปลี่ยนรูปคลิกที่นี่ หรือลากรูปใหม่มาวาง';
      };
      reader.readAsDataURL(file);
    };

    input.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        updatePreview(e.target.files[0]);
      }
    });

    ['dragover', 'dragenter'].forEach((type) => {
      zone.addEventListener(type, (e) => {
        e.preventDefault();
        zone.classList.add('is-dragover');
      });
    });

    ['dragleave', 'dragend', 'drop'].forEach((type) => {
      zone.addEventListener(type, () => {
        zone.classList.remove('is-dragover');
      });
    });

    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        input.files = e.dataTransfer.files;
        updatePreview(e.dataTransfer.files[0]);
      }
    });
  };

  document.querySelectorAll('[data-drop-zone]').forEach(initDropZone);

  window.handleFile = (file) => {
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      const previewImg = document.getElementById('img-preview');
      const previewBox = document.getElementById('preview-container');
      const previewInfo = document.getElementById('img-info');
      const dropText = document.getElementById('drop-text');

      if (previewImg && previewBox && previewInfo && dropText) {
        previewImg.src = e.target.result;
        previewBox.style.display = 'block';
        previewInfo.innerText = "✅ รูปพร้อมอัปโหลด: " + file.name + " (" + (file.size / 1024 / 1024).toFixed(2) + " MB)";
        dropText.innerText = "เปลี่ยนรูปคลิกที่นี่ หรือลากรูปใหม่มาวาง";
      }
    };
    reader.readAsDataURL(file);
  };

  document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('rich-editor');
    const form = document.getElementById('main-create-form');
    const initialContent = @json(old('content', ''));

    editor.innerHTML = initialContent || '';
    editor.addEventListener('input', window.syncContent);
    editor.addEventListener('blur', window.syncContent);
    window.syncContent();

    form.addEventListener('submit', (e) => {
      window.syncContent();
      const content = document.getElementById('hidden-content').value.trim();
      if (!content || content.replace(/<[^>]*>?/gm, '').trim() === '') {
        e.preventDefault();
        alert('🛑 กรุณากรอก "เนื้อหาบทความ" ด้วยครับพี่! (กล่องพิมพ์ข้อความตรงกลาง)\n\nระบบป้องกันการเซฟเพื่อไม่ให้ไฟล์ภาพที่เลือกไว้หายไปครับ');
        editor.focus();
        editor.style.border = "2px solid #ef4444";
        setTimeout(() => editor.style.border = "1px solid #d8e0ec", 3000);
      } else {
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = '⏳ กำลังบันทึกข้อมูล...';
      }
    });
  });
</script>
@endsection
