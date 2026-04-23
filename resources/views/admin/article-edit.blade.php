@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขบทความ')

@section('content')
  <style>
    .admin-drop-zone { border: 2px dashed #d8e0ec; border-radius: 12px; padding: 24px; text-align: center; background: #f8fbff; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 100px; position: relative; transition: all 0.3s; }
    .admin-drop-zone.is-dragover { border-color: #1d4f9f; background: #f0f7ff; }
    .admin-drop-zone__input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .admin-preview-img { max-width: 180px; border-radius: 10px; border: 1px solid #d8e0ec; display: block; }
    .admin-preview-box { margin-top: 12px; }
    .admin-preview-info { font-size: 12px; color: #94a3b8; margin-top: 6px; }
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
    <form id="main-update-form" action="/direct-save-article/{{ $article->id }}" method="post" enctype="multipart/form-data" class="admin-form">
      @csrf

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title', $article->title) }}" required />
      </div>

      <div class="admin-field">
        <label for="slug">Slug (ที่อยู่ URL)</label>
        <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $article->slug) }}" readonly style="background:#f1f5f9;" />
      </div>

      <div class="admin-field">
        <label for="excerpt">คำเกริ่นสั้น (Excerpt)</label>
        <textarea name="excerpt" class="admin-input" style="min-height: 80px; padding-top: 12px;">{{ old('excerpt', $article->excerpt) }}</textarea>
      </div>

      <div class="admin-field">
        <label>เนื้อหาบทความ</label>
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
          <div id="rich-editor" class="admin-rte__editor" contenteditable="true" style="min-height: 300px;">{!! old('content', $article->content) !!}</div>
        </div>
        <textarea id="hidden-content" name="content" style="display: none;">{{ old('content', $article->content) }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label> SEO Meta Description</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description', $article->meta_description) }}" />
      </div>

      <div class="admin-field">
        <label>Keywords</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords', $article->keywords) }}" />
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่</label>
        <input type="datetime-local" name="published_at" class="admin-input" value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\\TH:i')) }}" />
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label>รูปภาพบทความ (จัตุรัส 1:1)</label>
        <div id="drop-zone" class="admin-drop-zone">
          <span id="drop-text">🖼️ ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</span>
          <input type="file" name="upload_media_sq" id="input-sq" class="admin-drop-zone__input" accept="image/*" />
        </div>
        <div id="preview-container" class="admin-preview-box" style="{{ $article->cover_image_square_path ? '' : 'display:none;' }}">
          <img id="img-preview" src="{{ $article->cover_image_square_path ? asset('storage/' . $article->cover_image_square_path) : '' }}" class="admin-preview-img" style="aspect-ratio:1/1; object-fit:cover;" />
          <p id="img-info" class="admin-preview-info">{{ $article->cover_image_square_path ? 'รูปปัจจุบัน: ' . $article->cover_image_square_path : '' }}</p>
        </div>
      </div>

      <label style="display:flex; align-items:center; gap:10px; margin-top:20px;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published)) /> เผยแพร่บทความ
      </label>

      <div class="admin-actions" style="margin-top:30px;">
        <button type="submit" class="admin-button" id="save-btn">💾 บันทึกการแก้ไขบทความ</button>
        <a href="{{ route('admin.articles') }}" class="admin-button admin-button--secondary">กลับหน้ารวม</a>
      </div>
    </form>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 16px;">
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
  // 1. GLOBAL SCOPE FUNCTIONS
  window.execCmd = (cmd, val = null) => {
    const editor = document.getElementById('rich-editor');
    editor.focus();
    document.execCommand(cmd, false, val);
    syncContent();
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

  window.handleFile = (file) => {
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = (e) => {
      document.getElementById('img-preview').src = e.target.result;
      document.getElementById('preview-container').style.display = 'block';
      document.getElementById('img-info').innerText = "📂 เลือกไฟล์ใหม่: " + file.name + " (" + (file.size / 1024 / 1024).toFixed(2) + " MB)";
    };
    reader.readAsDataURL(file);
  };

  document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('rich-editor');
    const inputSq = document.getElementById('input-sq');
    const dropZone = document.getElementById('drop-zone');
    const form = document.getElementById('main-update-form');

    // INITIAL SYNC
    window.syncContent();

    // RTE LISTENERS
    editor.addEventListener('input', window.syncContent);
    editor.addEventListener('blur', window.syncContent);

    // INPUT CHANGE
    inputSq.addEventListener('change', (e) => {
      if (e.target.files.length > 0) window.handleFile(e.target.files[0]);
    });

    // DRAG & DROP
    ['dragover', 'dragenter'].forEach(type => {
      dropZone.addEventListener(type, (e) => {
        e.preventDefault();
        dropZone.classList.add('is-dragover');
      });
    });

    ['dragleave', 'dragend', 'drop'].forEach(type => {
      dropZone.addEventListener(type, () => {
        dropZone.classList.remove('is-dragover');
      });
    });

    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        inputSq.files = e.dataTransfer.files;
        window.handleFile(e.dataTransfer.files[0]);
      }
    });

    // FORM SUBMIT
    form.addEventListener('submit', window.syncContent);
  });
</script>
@endsection
