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
    <form id="main-update-form" action="{{ route('admin.articles.update', $article) }}" method="post" class="admin-form">
      @csrf
      <input type="hidden" id="land_path" name="land_path" value="" />
      <input type="hidden" id="sq_path" name="sq_path" value="" />

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title', $article->title) }}" required />
      </div>

      <div class="admin-field">
        <label for="slug">Slug (ที่อยู่ URL)</label>
        <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $article->slug) }}" readonly style="background:#f1f5f9;" />
      </div>

      <div class="admin-image-grid" style="margin-bottom: 30px;">
        <div class="admin-field" style="border-left: 4px solid #2563eb; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">ภาพหน้าปก</label>
          <div class="admin-drop-zone" data-drop-zone data-path-target="land_path">
            <input type="file" id="upload_media_land" class="admin-drop-zone__input" accept="image/jpeg,image/png,image/webp" data-drop-zone-input />
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

        <div class="admin-field" style="border-left: 4px solid #10b981; padding-left: 15px;">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปภาพบทความ (จัตุรัส 1:1)</label>
          <div class="admin-drop-zone" data-drop-zone data-path-target="sq_path">
            <input type="file" id="upload_media_sq" class="admin-drop-zone__input" accept="image/jpeg,image/png,image/webp" data-drop-zone-input />
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

      <div class="admin-field" style="border-left: 4px solid #3b82f6; padding-left: 15px;">
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

      <div class="admin-field" style="margin-top:30px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
        <h3 style="margin: 0 0 15px; font-size: 18px; color: #334155;">🔍 ข้อมูล SEO และส่วนแสดงผลเพิ่มเติม</h3>
        
        <div class="admin-field">
          <label for="excerpt">คำเกริ่นสั้น (Excerpt สำหรับแสดงบนการ์ดหน้ารวม)</label>
          <textarea name="excerpt" class="admin-input" style="min-height: 60px; padding-top: 12px;" placeholder="พิมพ์คำโปรยสั้นๆ... (ไม่บังคับ)">{{ old('excerpt', $article->excerpt) }}</textarea>
        </div>

        <div class="admin-field" style="margin-top:15px;">
          <label>SEO Meta Description (สำหรับ Google)</label>
          <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description', $article->meta_description) }}" placeholder="คำอธิบายสั้นๆ สำหรับผลการค้นหา..." />
        </div>

        <div class="admin-field" style="margin-top:15px;">
          <label>Keywords (สำหรับ Google)</label>
          <input type="text" name="keywords" class="admin-input" value="{{ old('keywords', $article->keywords) }}" placeholder="เช่น เบอร์มงคล, ดูดวง, ตัวเลข..." />
        </div>
      </div>

      <div class="admin-field" style="margin-top:30px; padding: 20px; background: #fffbeb; border-radius: 12px; border: 1px solid #fde68a;">
        <h3 style="margin: 0 0 15px; font-size: 18px; color: #92400e;">📅 ตั้งค่าการเผยแพร่</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: end;">
          <div class="admin-field">
            <label for="published_at">วันเวลาที่ต้องการให้แสดง (เว้นว่างไว้เพื่อลงทันที)</label>
            <input type="datetime-local" name="published_at" class="admin-input" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\\TH:i')) }}" />
          </div>

          <label style="display:flex; align-items:center; gap:10px; margin-bottom: 12px; font-size: 16px; font-weight: bold; cursor: pointer;">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published)) style="width: 22px; height: 22px;" /> เผยแพร่บทความ
          </label>
        </div>
      </div>

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

@push('scripts')
<script>
  // ---- RTE helpers ----
  window.execCmd = (cmd, val = null) => {
    const editor = document.getElementById('rich-editor');
    editor.focus();
    document.execCommand(cmd, false, val);
    window.syncContent();
  };
  window.addLink = () => {
    const url = prompt('ใส่ URL เช่น https://supernumber.co.th');
    if (url) window.execCmd('createLink', url);
  };
  window.syncContent = () => {
    const ed = document.getElementById('rich-editor');
    const hc = document.getElementById('hidden-content');
    if (ed && hc) hc.value = ed.innerHTML;
  };

  // ---- Upload state tracker ----
  // Tracks how many uploads are currently in-progress so we can block submit.
  let pendingUploads = 0;

  // ---- Canvas compress -> Blob ----
  const compressToBlob = (file, cb) => {
    const img = new Image();
    const ou = URL.createObjectURL(file);
    img.onload = () => {
      URL.revokeObjectURL(ou);
      const MAX = 900;
      let w = img.width, h = img.height;
      if (w > MAX || h > MAX) {
        if (w > h) { h = Math.round(h * MAX / w); w = MAX; }
        else       { w = Math.round(w * MAX / h); h = MAX; }
      }
      const c = document.createElement('canvas');
      c.width = w; c.height = h;
      c.getContext('2d').drawImage(img, 0, 0, w, h);
      c.toBlob(blob => cb(blob), 'image/jpeg', 0.82);
    };
    img.src = ou;
  };

  // ---- Pre-upload a file to /p/img, store returned path ----
  const preUpload = async (file, pathInput, previewImg, previewBox, previewInfo, dropText) => {
    pendingUploads++;
    previewInfo.innerText = '⏳ กำลังอัปโหลดรูป...';
    previewBox.style.display = 'block';

    try {
      // Show preview immediately from local file
      const ou = URL.createObjectURL(file);
      previewImg.src = ou;

      const blob = await new Promise(resolve => compressToBlob(file, resolve));
      const fd = new FormData();
      fd.append('img', blob, 'img.jpg');
      fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value || '');

      const resp = await fetch('/p/img', { method: 'POST', body: fd });
      const json = await resp.json();

      if (json.ok && json.path) {
        pathInput.value = json.path;
        const kb = Math.round(blob.size / 1024);
        previewInfo.innerText = `✅ อัปโหลดเรียบร้อย: ${file.name} → ${kb} KB`;
        dropText.innerText = 'เปลี่ยนรูปคลิกที่นี่';
      } else {
        previewInfo.innerText = `❌ อัปโหลดไม่สำเร็จ: ${json.error || 'unknown error'}`;
        pathInput.value = '';
        previewImg.src = '';
        previewBox.style.display = 'none';
      }
    } catch (err) {
      previewInfo.innerText = `❌ เน็ตเวิร์ค: ${err.message}`;
      pathInput.value = '';
    } finally {
      pendingUploads--;
    }
  };

  // ---- Drop zone init ----
  const initDropZone = (zone) => {
    const input     = zone.querySelector('[data-drop-zone-input]');
    const button    = zone.querySelector('[data-drop-zone-button]');
    const previewBox  = zone.parentElement.querySelector('[data-preview-box]');
    const previewImg  = zone.parentElement.querySelector('[data-preview-img]');
    const previewInfo = zone.parentElement.querySelector('[data-preview-info]');
    const dropText  = zone.querySelector('.drop-text');
    const pathTargetId = zone.getAttribute('data-path-target');
    const pathInput = pathTargetId ? document.getElementById(pathTargetId) : null;
    const maxSize = 5 * 1024 * 1024;

    const handleFile = (file) => {
      if (!file || !file.type.startsWith('image/')) return;
      if (file.size > maxSize) {
        alert(`🚨 ไฟล์ใหญ่เกินไป (max 5 MB)`);
        input.value = '';
        return;
      }
      preUpload(file, pathInput, previewImg, previewBox, previewInfo, dropText);
    };

    input.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
    if (button) button.addEventListener('click', e => { e.preventDefault(); input.click(); });

    ['dragover','dragenter'].forEach(t => zone.addEventListener(t, e => {
      e.preventDefault(); e.stopPropagation(); zone.classList.add('is-dragover');
    }));
    ['dragleave','dragend','drop'].forEach(t => zone.addEventListener(t, e => {
      e.preventDefault(); e.stopPropagation(); zone.classList.remove('is-dragover');
    }));
    zone.addEventListener('drop', e => {
      e.preventDefault(); e.stopPropagation();
      if (e.dataTransfer.files?.[0]) handleFile(e.dataTransfer.files[0]);
    });
  };

  document.querySelectorAll('[data-drop-zone]').forEach(initDropZone);
  document.addEventListener('dragover', e => e.preventDefault());
  document.addEventListener('drop',     e => e.preventDefault());

  // ---- DOMContentLoaded ----
  document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('rich-editor');
    const form   = document.getElementById('main-update-form');
    const initialContent = @json(old('content', $article->content));

    editor.innerHTML = initialContent || '';
    window.syncContent();
    editor.addEventListener('input', window.syncContent);
    editor.addEventListener('blur',  window.syncContent);

    form.addEventListener('submit', e => {
      window.syncContent();

      if (pendingUploads > 0) {
        e.preventDefault();
        alert('⏳ ยังอัปโหลดรูปไม่เสร็จ กรุณารอสักครู่ครับ');
        return;
      }

      const content = document.getElementById('hidden-content').value.trim();
      if (!content || content.replace(/<[^>]*>?/gm, '').trim() === '') {
        e.preventDefault();
        alert('🛑 กรุณากรอก "เนื้อหาบทความ" ด้วยครับพี่!');
        editor.focus();
        editor.style.border = '2px solid #ef4444';
        setTimeout(() => editor.style.border = '1px solid #d8e0ec', 3000);
        return;
      }

      const btn = form.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.innerText = '⏳ กำลังบันทึก...';
    });
  });
</script>
@endpush

