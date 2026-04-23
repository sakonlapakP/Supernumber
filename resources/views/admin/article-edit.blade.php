@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขบทความ')

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
      <h1>แก้ไขบทความ</h1>
      <p class="admin-subtitle">แก้ไขข้อมูลแบบ Real-time Sync (อัปโหลดรูปปกติ)</p>
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
        {{-- Hidden textarea for real submission --}}
        <textarea id="hidden-content" name="content" style="display: none;">{{ old('content', $article->content) }}</textarea>
      </div>

      <div class="admin-field" style="margin-top:20px;">
        <label>คำอธิบายเมตา (SEO)</label>
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
        <div class="admin-drop-zone">
          <span>🖼️ ลากรูปมาวางตรงนี้ หรือคลิกเลือกไฟล์</span>
          <input type="file" name="upload_media_sq" class="admin-drop-zone__input" accept="image/*" onchange="previewImg(this)" />
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

  {{-- Comments section kept identical --}}
  <section class="admin-card admin-table-card" style="margin-top: 16px;">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <h2 class="admin-feature-card__title">คอมเมนต์ ({{ $comments->count() }})</h2>
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
  const editor = document.getElementById('rich-editor');
  const hiddenContent = document.getElementById('hidden-content');
  const form = document.getElementById('main-update-form');

  // 1. COMMANDS
  function execCmd(cmd, val = null) {
    editor.focus();
    document.execCommand(cmd, false, val);
    syncContent();
  }

  function addLink() {
    const url = prompt("ใส่ URL:");
    if (url) execCmd("createLink", url);
  }

  // 2. SYNC REAL-TIME
  function syncContent() {
    hiddenContent.value = editor.innerHTML;
  }

  editor.addEventListener('input', syncContent);
  editor.addEventListener('blur', syncContent);

  // 3. IMAGE PREVIEW
  function previewImg(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        document.getElementById('img-preview').src = e.target.result;
        document.getElementById('preview-container').style.display = 'block';
        document.getElementById('img-info').innerText = "📂 เตรียมอัปโหลดไฟล์ใหม่: " + input.files[0].name;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // 3. DRAG & DROP
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

  // 4. ENSURE SYNC ON SUBMIT
  form.addEventListener('submit', (e) => {
    syncContent();
    // Do not disable button here, let standard POST handle it
  });
</script>
@endsection
