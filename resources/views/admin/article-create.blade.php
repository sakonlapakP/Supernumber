@extends('layouts.admin')

@section('title', 'Supernumber Admin | สร้างบทความใหม่')

@section('content')
  <style>
    .admin-drop-zone { border: 2px dashed #d8e0ec; border-radius: 12px; padding: 24px; text-align: center; background: #f8fbff; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 100px; position: relative; }
    .admin-drop-zone:hover { border-color: #1d4f9f; background: #f0f7ff; }
    .admin-drop-zone__input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .admin-preview-img { max-width: 180px; border-radius: 10px; border: 1px solid #d8e0ec; display: block; }
    .admin-preview-box { margin-top: 12px; }
    .admin-preview-info { font-size: 12px; color: #94a3b8; margin-top: 6px; }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>สร้างบทความใหม่</h1>
      <p class="admin-subtitle">กรอกข้อมูลบทความแบบครบถ้วน (ระบบ Real-time Sync)</p>
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
    <form id="main-create-form" action="{{ route('articles.store.bypass') }}" method="post" enctype="multipart/form-data" class="admin-form">
      @csrf

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title') }}" required placeholder="ระบุหัวข้อบทความ..." />
      </div>

      <div class="admin-field">
        <label for="excerpt">คำเกริ่นสั้น (Excerpt)</label>
        <textarea name="excerpt" class="admin-input" style="min-height: 80px; padding-top: 12px;">{{ old('excerpt') }}</textarea>
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
          <div id="rich-editor" class="admin-rte__editor" contenteditable="true" style="min-height: 300px;">{!! old('content') !!}</div>
        </div>
        <textarea id="hidden-content" name="content" style="display: none;">{{ old('content') }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label> SEO Meta Description</label>
        <input type="text" name="meta_description" class="admin-input" value="{{ old('meta_description') }}" />
      </div>

      <div class="admin-field">
        <label>Keywords</label>
        <input type="text" name="keywords" class="admin-input" value="{{ old('keywords') }}" />
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่ (ไม่ใส่ = ตอนนี้)</label>
        <input type="datetime-local" name="published_at" class="admin-input" value="{{ old('published_at') }}" />
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label>รูปภาพบทความ (จัตุรัส 1:1)</label>
        <div class="admin-drop-zone">
          <span>🖼️ ลากรูปมาวางตรงนี้ หรือคลิกเลือกไฟล์</span>
          <input type="file" name="upload_media_sq" class="admin-drop-zone__input" accept="image/*" onchange="previewImg(this)" />
        </div>
        <div id="preview-container" class="admin-preview-box" style="display:none;">
          <img id="img-preview" src="" class="admin-preview-img" style="aspect-ratio:1/1; object-fit:cover;" />
          <p id="img-info" class="admin-preview-info"></p>
        </div>
      </div>

      <label style="display:flex; align-items:center; gap:10px; margin-top:20px;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true)) /> เผยแพร่ทันที
      </label>

      <div class="admin-actions" style="margin-top:30px;">
        <button type="submit" class="admin-button">🚀 สร้างและอัปโหลดบทความ</button>
        <a href="{{ route('admin.articles') }}" class="admin-button admin-button--secondary">ยกเลิก</a>
      </div>
    </form>
  </section>
@endsection

@section('scripts')
<script>
  const editor = document.getElementById('rich-editor');
  const hiddenContent = document.getElementById('hidden-content');
  const form = document.getElementById('main-create-form');

  function execCmd(cmd, val = null) {
    editor.focus();
    document.execCommand(cmd, false, val);
    syncContent();
  }

  function addLink() {
    const url = prompt("ใส่ URL:");
    if (url) execCmd("createLink", url);
  }

  function syncContent() {
    hiddenContent.value = editor.innerHTML;
  }

  editor.addEventListener('input', syncContent);
  editor.addEventListener('blur', syncContent);

  function previewImg(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        document.getElementById('img-preview').src = e.target.result;
        document.getElementById('preview-container').style.display = 'block';
        document.getElementById('img-info').innerText = "📂 เตรียมอัปโหลดไฟล์: " + input.files[0].name;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // DRAG & DROP
  const dropZone = document.querySelector('.admin-drop-zone');
  const fileInput = document.querySelector('.admin-drop-zone__input');

  if (dropZone && fileInput) {
    ['dragover', 'dragenter'].forEach(type => {
      dropZone.addEventListener(type, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = "#1d4f9f";
        dropZone.style.background = "#f0f7ff";
      });
    });
    ['dragleave', 'dragend', 'drop'].forEach(type => {
      dropZone.addEventListener(type, () => {
        dropZone.style.borderColor = "#d8e0ec";
        dropZone.style.background = "#f8fbff";
      });
    });
    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        previewImg(fileInput);
      }
    });
  }

  form.addEventListener('submit', syncContent);
</script>
@endsection
