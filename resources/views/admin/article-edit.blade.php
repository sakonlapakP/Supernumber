@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขบทความ')

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
      <h1>แก้ไขบทความ</h1>
      <p class="admin-subtitle">แก้ไขข้อมูล (ระบบ Standard Upload + Drag & Drop)</p>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">
      <ul style="margin: 0; padding-left: 18px;">
        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
      </ul>
    </div>
  @endif

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form id="main-update-form" action="{{ route('admin.articles.update', $article) }}" method="post" enctype="multipart/form-data" class="admin-form">
      @csrf

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title', $article->title) }}" required />
      </div>

      <div class="admin-field">
        <label for="slug">Slug (ที่อยู่ URL)</label>
        <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $article->slug) }}" readonly style="background:#f1f5f9;" />
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
        <textarea id="hidden-content" name="content" style="display: none;">{{ old('content', $article->content) }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label for="excerpt">คำเกริ่นสั้น (Excerpt สำหรับแสดงบนการ์ดหน้ารวม)</label>
        <textarea name="excerpt" class="admin-input" style="min-height: 60px; padding-top: 12px;" placeholder="พิมพ์คำโปรยสั้นๆ... (ไม่บังคับ)">{{ old('excerpt', $article->excerpt) }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label> SEO Meta Description (สำหรับ Google)</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description', $article->meta_description) }}" />
      </div>

      <div class="admin-field">
        <label>Keywords (สำหรับ Google)</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords', $article->keywords) }}" />
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่ (เว้นว่างไว้เพื่อเผยแพร่ตอนนี้เลย)</label>
        <input type="datetime-local" name="published_at" class="admin-input" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\\TH:i')) }}" />
      </div>

      <div class="admin-image-grid">
        <div class="admin-field" style="margin-top:30px; border-left: 4px solid #2563eb; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปหน้ารวมบทความ (แนวนอน 16:9 / 4:3)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <input type="file" id="upload_media_land" name="upload_media_land" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
            <label for="upload_media_land" class="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</label>
            <button type="button" class="admin-drop-zone__button" data-drop-zone-button>browse</button>
          </div>
          <div class="admin-preview-box" data-preview-box style="{{ ($article->cover_image_landscape_path || $article->cover_image_path) ? '' : 'display:none;' }}">
            <img
              src="{{ $article->cover_image_landscape_path ? asset('storage/' . $article->cover_image_landscape_path) : ($article->cover_image_path ? asset('storage/' . $article->cover_image_path) : '') }}"
              class="admin-preview-img"
              data-preview-img
              style="aspect-ratio:16/9; object-fit:cover; border: 2px solid #2563eb;"
            />
            <p class="admin-preview-info" data-preview-info style="color: #2563eb; font-weight: bold;">
              {{ $article->cover_image_landscape_path ?: ($article->cover_image_path ? 'รูปปัจจุบัน: ' . $article->cover_image_path : '') }}
            </p>
          </div>
        </div>

        <div class="admin-field" style="margin-top:30px; border-left: 4px solid #10b981; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปภาพบทความ (จัตุรัส 1:1)</label>
          <div class="admin-drop-zone" data-drop-zone>
            <input type="file" id="upload_media_sq" name="upload_media_sq" class="admin-drop-zone__input" accept="image/*" data-drop-zone-input />
            <label for="upload_media_sq" class="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</label>
            <button type="button" class="admin-drop-zone__button" data-drop-zone-button>browse</button>
          </div>
          <div class="admin-preview-box" data-preview-box style="{{ ($article->cover_image_square_path || $article->cover_image_path) ? '' : 'display:none;' }}">
            <img
              src="{{ $article->cover_image_square_path ? asset('storage/' . $article->cover_image_square_path) : ($article->cover_image_path ? asset('storage/' . $article->cover_image_path) : '') }}"
              class="admin-preview-img"
              data-preview-img
              style="aspect-ratio:1/1; object-fit:cover; border: 2px solid #10b981;"
            />
            <p class="admin-preview-info" data-preview-info style="color: #059669; font-weight: bold;">
              {{ $article->cover_image_square_path ?: ($article->cover_image_path ? 'รูปปัจจุบัน: ' . $article->cover_image_path : '') }}
            </p>
          </div>
        </div>
      </div>

      <label style="display:flex; align-items:center; gap:10px; margin-top:30px; font-size: 16px; font-weight: bold;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published)) style="width: 20px; height: 20px;" /> เผยแพร่บทความ
      </label>

      <div class="admin-actions" style="margin-top:30px;">
        <button type="submit" class="admin-button" style="font-size: 16px; padding: 12px 24px;">💾 บันทึกการแก้ไขบทความ</button>
        <a href="{{ route('admin.articles') }}" class="admin-button admin-button--secondary" style="font-size: 16px; padding: 12px 24px;">กลับหน้ารวม</a>
      </div>
    </form>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 24px;">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <h2 class="admin-feature-card__title">คอมเมนต์</h2>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>เวลา</th><th>ผู้คอมเมนต์</th><th>เนื้อหา</th><th>สถานะ</th><th>จัดการ</th></tr>
        </thead>
        <tbody>
          @foreach ($comments as $comment)
            <tr>
              <td>{{ $comment->created_at->format('Y-m-d H:i') }}</td>
              <td>{{ $comment->commenter_name }}</td>
              <td style="max-width: 400px; white-space: normal;">{{ $comment->content }}</td>
              <td><span class="admin-status-pill {{ $comment->status === 'approved' ? 'admin-status-pill--active' : '' }}">{{ $comment->status === 'approved' ? 'อนุมัติแล้ว' : 'ซ่อน' }}</span></td>
              <td>
                <form action="{{ route('admin.articles.comments.' . ($comment->status === 'approved' ? 'archive' : 'unarchive'), [$article, $comment]) }}" method="POST">
                  @csrf <button type="submit" class="admin-button admin-button--compact" style="background: {{ $comment->status === 'approved' ? '#f59e0b' : '#3b82f6' }}">{{ $comment->status === 'approved' ? 'ซ่อน' : 'โชว์' }}</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
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
    const url = prompt("ใส่ URL เช่น https://supernumber.co.th");
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
        previewInfo.innerText = "✅ รูปเปลี่ยนแล้วพร้อมบันทึก: " + file.name;
        dropText.innerText = "เปลี่ยนรูปคลิกที่นี่ หรือลากรูปใหม่มาวาง";
      }
    };
    reader.readAsDataURL(file);
  };

  document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('rich-editor');
    const form = document.getElementById('main-update-form');
    const initialContent = @json(old('content', $article->content));

    editor.innerHTML = initialContent || '';
    // INITIAL SYNC
    window.syncContent();

    // RTE LISTENERS
    editor.addEventListener('input', window.syncContent);
    editor.addEventListener('blur', window.syncContent);

    // INPUT CHANGE
    // FORM SUBMIT INTERCEPTOR
    form.addEventListener('submit', (e) => {
      window.syncContent();
      const content = document.getElementById('hidden-content').value.trim();
      if (!content || content.replace(/<[^>]*>?/gm, '').trim() === '') {
        e.preventDefault();
        alert('🛑 กรุณากรอก "เนื้อหาบทความ" ด้วยครับพี่! (กล่องพิมพ์ข้อความตรงกลาง)\n\nระบบป้องกันการเซฟเพื่อไม่ให้รูปและข้อมูลอื่นๆ ที่แก้ไขไว้หายไปครับ');
        editor.focus();
        editor.style.border = "2px solid #ef4444";
        setTimeout(() => editor.style.border = "1px solid #d8e0ec", 3000);
      } else {
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = '⏳ กำลังบันทึกการแก้ไข...';
      }
    });
  });
</script>
@endsection
