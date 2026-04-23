@extends('layouts.admin')

@section('title', 'Supernumber Admin | สร้างบทความใหม่')

@section('content')
  <style>
    .admin-drop-zone { border: 2px dashed #d8e0ec; border-radius: 12px; padding: 24px; text-align: center; background: #f8fbff; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 100px; position: relative; transition: all 0.3s; }
    .admin-drop-zone.is-dragover { border-color: #1d4f9f; background: #f0f7ff; }
    .admin-drop-zone__input { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
    .admin-drop-zone__button { border: 1px solid #cfd8e7; background: #fff; color: #1e293b; font: inherit; padding: 8px 14px; border-radius: 999px; cursor: pointer; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
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
    <form id="main-create-form" action="{{ route('admin.articles.store') }}" method="post" class="admin-form">
      @csrf
      <input type="hidden" id="upload_media_land_b64" name="upload_media_land_b64" value="" />
      <input type="hidden" id="upload_media_sq_b64" name="upload_media_sq_b64" value="" />

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
          <div class="admin-drop-zone" data-drop-zone data-b64-target="upload_media_land_b64">
            <input type="file" id="upload_media_land" class="admin-drop-zone__input" accept="image/jpeg,image/png,image/webp" data-drop-zone-input />
            <label for="upload_media_land" class="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</label>
            <button type="button" class="admin-drop-zone__button" data-drop-zone-button>browse</button>
          </div>
          <div class="admin-preview-box" data-preview-box style="display:none;">
            <img src="" class="admin-preview-img" data-preview-img style="aspect-ratio:16/9; object-fit:cover; border: 2px solid #2563eb;" />
            <p class="admin-preview-info" data-preview-info style="color: #2563eb; font-weight: bold;"></p>
          </div>
        </div>

        <div class="admin-field" style="margin-top:30px; border-left: 4px solid #10b981; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปภาพบทความ (จัตุรัส 1:1)</label>
          <div class="admin-drop-zone" data-drop-zone data-b64-target="upload_media_sq_b64">
            <input type="file" id="upload_media_sq" class="admin-drop-zone__input" accept="image/jpeg,image/png,image/webp" data-drop-zone-input />
            <label for="upload_media_sq" class="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</label>
            <button type="button" class="admin-drop-zone__button" data-drop-zone-button>browse</button>
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

@push('scripts')
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
    const button = zone.querySelector('[data-drop-zone-button]');
    const previewBox = zone.parentElement.querySelector('[data-preview-box]');
    const previewImg = zone.parentElement.querySelector('[data-preview-img]');
    const previewInfo = zone.parentElement.querySelector('[data-preview-info]');
    const dropText = zone.querySelector('.drop-text');
    const maxSize = 5 * 1024 * 1024; // 5 MB raw file limit (will be compressed)
    const b64TargetId = zone.getAttribute('data-b64-target');
    const b64Input = b64TargetId ? document.getElementById(b64TargetId) : null;

    // Compress image via Canvas before storing as base64
    const compressAndStore = (file, onDone) => {
      const img = new Image();
      const objectUrl = URL.createObjectURL(file);
      img.onload = () => {
        URL.revokeObjectURL(objectUrl);
        const MAX_DIM = 900;
        let w = img.width, h = img.height;
        if (w > MAX_DIM || h > MAX_DIM) {
          if (w > h) { h = Math.round(h * MAX_DIM / w); w = MAX_DIM; }
          else       { w = Math.round(w * MAX_DIM / h); h = MAX_DIM; }
        }
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        const dataUri = canvas.toDataURL('image/jpeg', 0.82);
        onDone(dataUri);
      };
      img.src = objectUrl;
    };

    const updatePreview = (file) => {
      if (!file || !file.type.startsWith('image/')) return;

      if (file.size > maxSize) {
        alert(`🚨 ไฟล์ใหญ่เกินไป!\n\nรูป "${file.name}" มีขนาด ${(file.size / 1024 / 1024).toFixed(2)} MB\nระบบรองรับได้ไม่เกิน 5 MB ครับ`);
        input.value = '';
        return;
      }

      compressAndStore(file, (dataUri) => {
        const kb = Math.round(dataUri.length * 0.75 / 1024);
        previewImg.src = dataUri;
        previewBox.style.display = 'block';
        previewInfo.innerText = `✅ รูปพร้อมอัปโหลด: ${file.name} → บีบอัดเหลือ ~${kb} KB`;
        dropText.innerText = 'เปลี่ยนรูปคลิกที่นี่ หรือลากรูปใหม่มาวาง';
        if (b64Input) b64Input.value = dataUri;
      });
    };

    input.addEventListener('change', (e) => {
      if (e.target.files.length > 0) {
        updatePreview(e.target.files[0]);
      }
    });

    if (button) {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        input.click();
      });
    }

    ['dragover', 'dragenter'].forEach((type) => {
      zone.addEventListener(type, (e) => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.add('is-dragover');
      });
    });

    ['dragleave', 'dragend', 'drop'].forEach((type) => {
      zone.addEventListener(type, (e) => {
        e.preventDefault();
        e.stopPropagation();
        zone.classList.remove('is-dragover');
      });
    });

    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        input.files = e.dataTransfer.files;
        updatePreview(e.dataTransfer.files[0]);
      }
    });
  };

  document.querySelectorAll('[data-drop-zone]').forEach(initDropZone);
  document.addEventListener('dragover', (e) => {
    e.preventDefault();
  });
  document.addEventListener('drop', (e) => {
    e.preventDefault();
  });

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
@endpush
