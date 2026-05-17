@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขบทความ')

@section('content')
  @php
    $canToggleArticleVisibility = in_array(session('admin_user_role'), [\App\Models\User::ROLE_MANAGER, \App\Models\User::ROLE_ADMIN], true);
    $canDeleteArticle = session('admin_user_role') === \App\Models\User::ROLE_MANAGER;
    $imageGuidelines = old('image_guidelines', $article->image_guidelines ?? []);
    $landscapePrompt = is_array($imageGuidelines) ? ($imageGuidelines['landscape_prompt'] ?? '') : '';
    $squarePrompt = is_array($imageGuidelines) ? ($imageGuidelines['square_prompt'] ?? '') : '';
  @endphp

  <style>
    .admin-drop-zone { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px; text-align: center; background: #f8fafc; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 72px; position: relative; transition: all 0.3s; }
    .admin-drop-zone.is-dragover { border-color: #1d4f9f; background: #f0f7ff; }
    .admin-drop-zone__input { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
    .admin-drop-zone__button { border: 1px solid #cbd5e1; background: #fff; color: #1e293b; font: inherit; font-weight: 700; padding: 8px 14px; border-radius: 8px; cursor: pointer; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); }
    .drop-text { color: #64748b; font-weight: 700; text-align: left; }
    .admin-preview-img { width: 100%; max-width: 220px; border-radius: 8px; border: 1px solid #d8e0ec; display: block; background: #fff; }
    .admin-preview-box { margin-top: 14px; }
    .admin-preview-info { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; color: #64748b; margin: 10px 0 0; font-weight: 600; }
    .admin-image-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
    .article-edit-highlight {
      margin-top: 20px;
      border-left: 4px solid #3b82f6;
      padding-left: 15px;
    }
    .article-edit-highlight--landscape {
      margin-top: 30px;
      border-left-color: #2563eb;
    }
    .article-edit-highlight--square {
      margin-top: 30px;
      border-left-color: #10b981;
    }
    .article-edit-preview-note,
    .article-edit-comment-content {
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    .article-edit-comments-table td {
      vertical-align: top;
    }
    .article-image-card {
      margin-top: 28px;
      padding: 18px;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #fff;
    }
    .article-image-card__head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 14px;
    }
    .article-image-card__title {
      margin: 0;
      color: #1e293b;
      font-size: 16px;
      font-weight: 800;
      line-height: 1.35;
    }
    .article-image-card__ratio {
      flex: 0 0 auto;
      border: 1px solid #dbeafe;
      border-radius: 999px;
      background: #eff6ff;
      color: #1d4ed8;
      font-size: 12px;
      font-weight: 800;
      padding: 4px 8px;
    }
    .article-image-card--square .article-image-card__ratio {
      border-color: #bbf7d0;
      background: #ecfdf5;
      color: #047857;
    }
    .article-image-card__preview {
      display: inline-block;
      max-width: 100%;
      padding: 8px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      background: #f8fafc;
    }
    .article-edit-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
      margin-top: 28px;
      padding-top: 18px;
      border-top: 1px solid #e2e8f0;
    }
    .article-edit-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 46px;
      padding: 11px 20px;
      border: 1px solid transparent;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      line-height: 1.2;
      box-shadow: 0 2px 5px rgba(15, 23, 42, 0.12);
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }
    .article-edit-action:hover {
      box-shadow: 0 4px 10px rgba(15, 23, 42, 0.16);
      filter: brightness(0.98);
      transform: translateY(-1px);
    }
    .article-edit-action:active {
      box-shadow: 0 1px 3px rgba(15, 23, 42, 0.18);
      transform: translateY(0);
    }
    .article-edit-action--primary {
      background: #1f3f6d;
      border-color: #1f3f6d;
      color: #fff;
    }
    .article-edit-action--hide {
      background: #d97706;
      border-color: #d97706;
      color: #fff;
    }
    .article-edit-action--publish {
      background: #059669;
      border-color: #059669;
      color: #fff;
    }
    .article-edit-action--delete {
      background: #dc2626;
      border-color: #dc2626;
      color: #fff;
    }
    @media (max-width: 768px) {
      .admin-image-grid { grid-template-columns: 1fr; }
      .admin-drop-zone { display: grid; justify-items: center; text-align: center; }
      .drop-text { text-align: center; }
      .article-edit-highlight,
      .article-edit-highlight--landscape,
      .article-edit-highlight--square {
        padding-left: 0;
        border-left: 0;
        border-top: 4px solid #3b82f6;
        padding-top: 12px;
      }
      .article-edit-highlight--landscape { border-top-color: #2563eb; }
      .article-edit-highlight--square { border-top-color: #10b981; }
      .admin-preview-img { max-width: 100%; }
      .article-edit-actions { display: grid; grid-template-columns: 1fr; }
      .article-edit-action { width: 100%; }
    }
    @media (max-width: 640px) {
      .article-edit-comments-table {
        width: 100%;
        min-width: 0;
      }
      .article-edit-comments-table thead {
        display: none;
      }
      .article-edit-comments-table,
      .article-edit-comments-table tbody,
      .article-edit-comments-table tr,
      .article-edit-comments-table td {
        display: block;
        width: 100%;
      }
      .article-edit-comments-table tr {
        margin-bottom: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
      }
      .article-edit-comments-table td {
        margin: 0;
        padding: 10px 12px;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 13px;
      }
      .article-edit-comments-table td:last-child {
        border-bottom: 0;
      }
      .article-edit-comments-table td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 4px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.01em;
      }
      .article-edit-comments-table td form,
      .article-edit-comments-table td .admin-button {
        width: 100%;
      }
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

      <div class="admin-field" style="margin-top:20px;">
        <label for="slug">Slug (ที่อยู่ URL)</label>
        <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $article->slug) }}" placeholder="เช่น my-article-url" />
        <p id="slug-feedback" style="margin: 8px 0 0; font-size: 14px; font-weight: bold; min-height: 20px;"></p>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label for="excerpt">คำเกริ่นสั้น (Excerpt สำหรับแสดงบนการ์ดหน้ารวม)</label>
        <textarea name="excerpt" class="admin-input" style="min-height: 60px; padding-top: 12px;" placeholder="พิมพ์คำโปรยสั้นๆ... (ไม่บังคับ)">{{ old('excerpt', $article->excerpt) }}</textarea>
      </div>

      <div class="admin-field article-edit-highlight">
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
        <label> SEO Meta Description (สำหรับ Google)</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description', $article->meta_description) }}" />
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label>Keywords (สำหรับ Google)</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords', $article->keywords) }}" />
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label> LSI Keywords (คำค้นหาที่เกี่ยวข้องคั่นด้วยจุลภาค ,)</label>
        <input type="text" name="lsi_keywords" class="admin-input" value="{{ old('lsi_keywords', $article->lsi_keywords) }}" placeholder="เช่น เปลี่ยนเบอร์, พลังตัวเลข, ทำนายดวง" />
      </div>



      <div class="admin-image-grid">
        <div class="admin-field article-edit-highlight article-edit-highlight--landscape">
          <label style="font-size: 16px; color: #1e293b; font-weight: bold;">รูปหน้ารวมบทความ (แนวนอน 16:9 / 4:3)</label>
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
            <p class="admin-preview-info article-edit-preview-note" data-preview-info style="color: #2563eb; font-weight: bold;">
              {{ $article->cover_image_landscape_path ?: ($article->cover_image_path ? 'รูปปัจจุบัน: ' . $article->cover_image_path : '') }}
            </p>
          </div>
          <div class="admin-field" style="margin-top:16px;">
            <label for="landscape_prompt">Prompt รูปหน้ารวมบทความ 16:9</label>
            <textarea id="landscape_prompt" name="image_guidelines[landscape_prompt]" class="admin-input" style="min-height: 96px; padding-top: 12px;" placeholder="Prompt สำหรับรูป 16:9">{{ $landscapePrompt }}</textarea>
          </div>
        </div>

        <div class="admin-field article-edit-highlight article-edit-highlight--square">
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
            <p class="admin-preview-info article-edit-preview-note" data-preview-info style="color: #059669; font-weight: bold;">
              {{ $article->cover_image_square_path ?: ($article->cover_image_path ? 'รูปปัจจุบัน: ' . $article->cover_image_path : '') }}
            </p>
          </div>
          <div class="admin-field" style="margin-top:16px;">
            <label for="square_prompt">Prompt รูปภาพบทความ 1:1</label>
            <textarea id="square_prompt" name="image_guidelines[square_prompt]" class="admin-input" style="min-height: 96px; padding-top: 12px;" placeholder="Prompt สำหรับรูป 1:1">{{ $squarePrompt }}</textarea>
          </div>
        </div>
      </div>

      <div class="admin-field" style="margin-top:30px;">
        <label style="display: flex; align-items: center; cursor: pointer;">
          <input type="hidden" name="is_auto_post" value="0">
          <input type="checkbox" name="is_auto_post" value="1" {{ old('is_auto_post', $article->is_auto_post ?? true) ? 'checked' : '' }} style="width: 20px; height: 20px; margin-right: 10px;">
          <span style="font-size: 16px; font-weight: bold;">แชร์ไป Social (Facebook/LINE) อัตโนมัติเมื่อเผยแพร่</span>
        </label>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label for="published_at">เวลาเผยแพร่</label>
        <input type="datetime-local" name="published_at" class="admin-input" value="{{ old('published_at', optional(optional($article->published_at)->timezone('Asia/Bangkok'))->format('Y-m-d\TH:i')) }}" />
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 12px;">ถ้าเป็นเวลาอนาคต สถานะจะแสดงเป็นตั้งเวลาเผยแพร่จนกว่าจะถึงเวลานี้</p>
      </div>

      <input type="hidden" name="is_published" value="{{ $article->is_published ? '1' : '0' }}" />

      <div class="article-edit-actions">
        <button type="submit" class="admin-button article-edit-action article-edit-action--primary">บันทึกบทความ</button>
        @php
          $isPreview = !$article->is_published || ($article->published_at && $article->published_at->gt(now('Asia/Bangkok')));
          $viewUrl = $isPreview 
              ? URL::temporarySignedRoute('articles.signed-preview', now()->addHours(24), ['article' => $article])
              : route('articles.show', $article->slug);
        @endphp
        <a href="{{ $viewUrl }}" target="_blank" class="admin-button article-edit-action" style="background: #eef2f7; color: #1e293b; border-color: #cbd5e1; text-decoration: none;">ดูตัวอย่าง</a>
        @if($canToggleArticleVisibility)
          <button
            type="submit"
            form="article-visibility-form"
            class="admin-button article-edit-action {{ $article->is_published ? 'article-edit-action--hide' : 'article-edit-action--publish' }}"
          >
            {{ $article->is_published ? 'ซ่อนบทความ' : 'เผยแพร่บทความ' }}
          </button>
        @endif
        @if($canDeleteArticle)
          <button
            type="submit"
            form="article-delete-form"
            class="admin-button article-edit-action article-edit-action--delete"
          >
            ลบบทความ
          </button>
        @endif
      </div>
    </form>

    @if($canToggleArticleVisibility)
      <form
        id="article-visibility-form"
        action="{{ route('admin.articles.toggle-publish', $article) }}"
        method="post"
        style="display: none;"
        onsubmit="return confirm('{{ $article->is_published ? 'ยืนยันซ่อนบทความนี้จากหน้าเว็บ?' : 'ยืนยันเผยแพร่บทความนี้?' }}')"
      >
        @csrf
      </form>
    @endif

    @if($canDeleteArticle)
      <form
        id="article-delete-form"
        action="{{ route('admin.articles.delete', $article) }}"
        method="post"
        style="display: none;"
        onsubmit="return confirm('ยืนยันลบบทความนี้? การลบจะลบไฟล์รูปและคอมเมนต์ที่เกี่ยวข้องด้วย')"
      >
        @csrf
        @method('DELETE')
      </form>
    @endif
    
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 24px;">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <h2 class="admin-feature-card__title">คอมเมนต์</h2>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table article-edit-comments-table">
        <thead>
          <tr><th>เวลา</th><th>ผู้คอมเมนต์</th><th>เนื้อหา</th><th>สถานะ</th><th>จัดการ</th></tr>
        </thead>
        <tbody>
          @foreach ($comments as $comment)
            <tr>
              <td data-label="เวลา">{{ $comment->created_at->format('Y-m-d H:i') }}</td>
              <td data-label="ผู้คอมเมนต์">{{ $comment->commenter_name }}</td>
              <td data-label="เนื้อหา" class="article-edit-comment-content" style="max-width: 400px; white-space: normal;">{{ $comment->content }}</td>
              <td data-label="สถานะ"><span class="admin-status-pill {{ $comment->status === 'approved' ? 'admin-status-pill--active' : '' }}">{{ $comment->status === 'approved' ? 'อนุมัติแล้ว' : 'ซ่อน' }}</span></td>
              <td data-label="จัดการ">
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

    const slugInput = document.getElementById('slug');
    const slugFeedback = document.getElementById('slug-feedback');
    let slugTimer = null;

    if (slugInput && slugFeedback) {
      slugInput.addEventListener('input', () => {
        clearTimeout(slugTimer);
        const val = slugInput.value.trim();
        if (!val) { slugFeedback.innerText = ''; return; }
        
        slugTimer = setTimeout(async () => {
          try {
            const resp = await fetch('{{ route('admin.articles.check-slug') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
              },
              body: JSON.stringify({ slug: val, ignore_id: {{ $article->id }} })
            });
            const data = await resp.json();
            if (data.exists) {
              slugFeedback.innerText = '❌ Slug นี้ถูกใช้ไปแล้ว (ซ้ำ) กรุณาเปลี่ยนใหม่';
              slugFeedback.style.color = '#e11d48';
            } else {
              slugFeedback.innerText = '✅ Slug นี้ใช้งานได้';
              slugFeedback.style.color = '#059669';
            }
          } catch (err) {}
        }, 600);
      });
    }

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
