@extends('layouts.admin')

@section('title', 'Supernumber Admin | ตรวจสอบบทความ #{{ $post->id }}')

@section('content')
  @php
    $statusLabels = [
      'pending'   => ['label' => 'รอดำเนินการ', 'color' => '#dc2626', 'bg' => '#fef2f2'],
      'refreshed' => ['label' => 'เขียนใหม่แล้ว',  'color' => '#d97706', 'bg' => '#fffbeb'],
      'approved'  => ['label' => 'อนุมัติแล้ว',    'color' => '#059669', 'bg' => '#ecfdf5'],
      'published' => ['label' => 'เผยแพร่แล้ว',    'color' => '#1d4ed8', 'bg' => '#eff6ff'],
    ];
    $statusInfo    = $statusLabels[$post->status] ?? $statusLabels['pending'];
    $fbImageUrl    = $post->resolveImageUrl();
    $fbCreatedTime = $post->facebook_created_time
      ? $post->facebook_created_time->timezone('Asia/Bangkok')
      : null;
    $fbDate        = $fbCreatedTime ? $fbCreatedTime->format('d/m/Y H:i') : '-';
    $fbDatetimeLocal = $fbCreatedTime ? $fbCreatedTime->format('Y-m-d\TH:i') : '';
    $fbMessage     = trim((string) ($post->message ?: $post->story ?: ''));
    $fbCreatedIso  = $fbCreatedTime ? $fbCreatedTime->toIso8601String() : '';
  @endphp

  <style>
    .review-layout {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 24px;
      align-items: start;
    }
    .review-fb-panel {
      position: sticky;
      top: 24px;
    }
    .review-fb-panel .admin-card {
      padding: 20px;
    }
    .fb-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }
    .fb-panel-title {
      font-size: 15px;
      font-weight: 800;
      color: #1e293b;
      margin: 0;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
    }
    .fb-image {
      width: 100%;
      aspect-ratio: 4/3;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      background: #f1f5f9;
      margin-bottom: 14px;
    }
    .fb-image-placeholder {
      width: 100%;
      aspect-ratio: 4/3;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      border-radius: 10px;
      color: #94a3b8;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 14px;
    }
    .fb-meta {
      font-size: 12px;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 10px;
    }
    .fb-message {
      font-size: 14px;
      line-height: 1.7;
      color: #334155;
      white-space: pre-wrap;
      word-break: break-word;
      max-height: 420px;
      overflow-y: auto;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 14px;
    }
    .fb-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #1d4f9f;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
    }
    .fb-link:hover { text-decoration: underline; }
    .review-form-section {
      padding: 24px;
    }
    .review-form-section .admin-form {
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .review-field {
      margin-top: 20px;
    }
    .review-field:first-child {
      margin-top: 0;
    }
    .review-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 28px;
      padding-top: 20px;
      border-top: 1px solid #e2e8f0;
    }
    .review-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 46px;
      padding: 11px 24px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: filter 0.15s, transform 0.15s;
    }
    .review-action:hover { filter: brightness(0.95); transform: translateY(-1px); }
    .review-action--draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .review-action--approve { background: #1f3f6d; color: #fff; }
    .review-action--publish { background: #059669; color: #fff; }
    .review-section-divider {
      margin: 24px 0 0;
      padding-top: 20px;
      border-top: 1px solid #f1f5f9;
      font-size: 12px;
      font-weight: 800;
      color: #94a3b8;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }
    @media (max-width: 900px) {
      .review-layout { grid-template-columns: 1fr; }
      .review-fb-panel { position: static; }
    }

    /* Prompt modal */
    .prompt-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(15,23,42,0.55);
      z-index: 9000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .prompt-overlay.is-open { display: flex; }
    .prompt-modal {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 20px 60px rgba(15,23,42,0.25);
      width: 100%;
      max-width: 760px;
      max-height: 88vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .prompt-modal__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 22px;
      border-bottom: 1px solid #e2e8f0;
    }
    .prompt-modal__title { font-size: 16px; font-weight: 800; color: #1e293b; margin: 0; }
    .prompt-modal__close {
      border: none; background: #f1f5f9; color: #64748b;
      width: 32px; height: 32px; border-radius: 50%;
      font-size: 18px; line-height: 1; cursor: pointer;
    }
    .prompt-modal__body { flex: 1; overflow-y: auto; padding: 18px 22px; }
    .prompt-modal__textarea {
      width: 100%; min-height: 340px; font-family: monospace;
      font-size: 13px; line-height: 1.6; color: #1e293b;
      border: 1px solid #e2e8f0; border-radius: 8px;
      padding: 14px; resize: vertical; box-sizing: border-box;
    }
    .prompt-modal__foot {
      padding: 14px 22px;
      border-top: 1px solid #e2e8f0;
      display: flex; gap: 10px;
    }
    .prompt-copy-btn {
      background: #1f3f6d; color: #fff; border: none;
      padding: 10px 22px; border-radius: 8px; font-size: 14px;
      font-weight: 700; cursor: pointer;
    }
    .prompt-copy-btn.copied { background: #059669; }

    /* JSON import */
    .json-import-section {
      margin-top: 28px;
      padding: 20px;
      border: 2px dashed #cbd5e1;
      border-radius: 10px;
      background: #f8fafc;
    }
    .json-import-title {
      font-size: 14px; font-weight: 800; color: #475569; margin: 0 0 12px;
    }
    .json-import-textarea {
      width: 100%; min-height: 120px; font-family: monospace;
      font-size: 12px; border: 1px solid #cbd5e1; border-radius: 6px;
      padding: 10px; box-sizing: border-box; resize: vertical;
    }
    .json-import-btn {
      margin-top: 10px; padding: 9px 20px;
      background: #0f766e; color: #fff; border: none;
      border-radius: 8px; font-size: 13px; font-weight: 700;
      cursor: pointer;
    }
    .json-import-error {
      margin-top: 8px; font-size: 12px; color: #dc2626; font-weight: 600; display: none;
    }

    /* RTE */
    .admin-rte { border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; margin-top: 8px; }
    .admin-rte__toolbar {
      display: flex; flex-wrap: wrap; gap: 4px; padding: 8px 10px;
      background: #f8fafc; border-bottom: 1px solid #e2e8f0;
    }
    .admin-rte__btn {
      padding: 4px 10px; border: 1px solid #cbd5e1; background: #fff;
      border-radius: 4px; font-size: 12px; font-weight: 700; cursor: pointer;
      color: #334155;
    }
    .admin-rte__btn:hover { background: #f1f5f9; }
    .admin-rte__editor {
      padding: 14px 16px; min-height: 380px; outline: none;
      font-size: 15px; line-height: 1.8; color: #1e293b;
    }
    .admin-rte__editor h2 { font-size: 1.25em; font-weight: 800; margin: 1em 0 0.4em; }
    .admin-rte__editor h3 { font-size: 1.1em; font-weight: 700; margin: 0.9em 0 0.3em; }
    .admin-rte__editor p  { margin: 0 0 0.8em; }

    /* Publish date + checkbox row */
    .publish-row {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 16px;
      align-items: end;
    }
    .publish-toggle {
      display: flex;
      align-items: center;
      gap: 8px;
      padding-bottom: 10px;
      white-space: nowrap;
    }
    .publish-toggle input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; }
    .publish-toggle label { font-size: 14px; font-weight: 700; cursor: pointer; }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>ตรวจสอบบทความ</h1>
      <p class="admin-subtitle">โพสต์ Facebook #{{ $post->id }}
        <span class="status-badge" style="color: {{ $statusInfo['color'] }}; background: {{ $statusInfo['bg'] }}; margin-left: 8px;">
          {{ $statusInfo['label'] }}
        </span>
      </p>
    </div>
    <a href="{{ route('admin.facebook-imports') }}" class="admin-button admin-button--muted admin-button--compact">← กลับ</a>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">
      <ul style="margin: 0; padding-left: 18px;">
        @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="review-layout">

    {{-- LEFT: Original Facebook post --}}
    <div class="review-fb-panel">
      <div class="admin-card" style="padding: 20px;">
        <div class="fb-panel-header">
          <h2 class="fb-panel-title">โพสต์ Facebook ต้นฉบับ</h2>
        </div>

        @if ($fbImageUrl !== '')
          <img src="{{ $fbImageUrl }}" alt="รูปโพสต์" referrerpolicy="no-referrer" class="fb-image" />
        @else
          <div class="fb-image-placeholder">ไม่มีรูปภาพ</div>
        @endif

        <p class="fb-meta">📅 โพสต์เมื่อ {{ $fbDate }}</p>

        @if ($fbMessage !== '')
          <div class="fb-message">{{ $fbMessage }}</div>
        @else
          <p class="admin-muted">ไม่มีข้อความ</p>
        @endif

        @if ($post->permalink_url)
          <a href="{{ $post->permalink_url }}" target="_blank" rel="noopener" class="fb-link">
            🔗 ดูโพสต์ Facebook ต้นฉบับ
          </a>
        @endif
      </div>
    </div>

    {{-- RIGHT: Article form --}}
    <section class="admin-card review-form-section">
      <form id="review-form" action="{{ route('admin.facebook-imports.approve', $post->id) }}" method="post" class="admin-form">
        @csrf

        <div class="review-field">
          <label class="admin-label" for="title">หัวข้อบทความ <span style="color:#dc2626">*</span></label>
          <input
            type="text"
            id="title"
            name="title"
            class="admin-input"
            value="{{ old('title', $article->title ?? '') }}"
            required
            placeholder="ชื่อบทความสำหรับ SEO"
          />
        </div>

        <div class="review-field">
          <label class="admin-label" for="slug">Slug (URL) <span style="color:#dc2626">*</span></label>
          <input
            type="text"
            id="slug"
            name="slug"
            class="admin-input"
            value="{{ old('slug', $article->slug ?? '') }}"
            required
            placeholder="เช่น numerology-lucky-number-2026"
          />
          <p id="slug-feedback" style="margin: 6px 0 0; font-size: 12px; color: #64748b; min-height: 16px;"></p>
        </div>

        <div class="review-field">
          <label class="admin-label" for="excerpt">Excerpt (คำเกริ่นสั้น)</label>
          <textarea
            id="excerpt"
            name="excerpt"
            class="admin-input"
            style="min-height: 72px; padding-top: 10px;"
            placeholder="ประโยคเปิดบทความ 1-2 บรรทัด สำหรับแสดงบนการ์ดรายการ"
          >{{ old('excerpt', $article->excerpt ?? '') }}</textarea>
        </div>

        <div class="review-field" style="border-left: 4px solid #3b82f6; padding-left: 16px;">
          <label class="admin-label" style="font-size: 15px; color: #1e293b;">เนื้อหาบทความ (HTML)</label>
          <div class="admin-rte">
            <div class="admin-rte__toolbar">
              <button type="button" class="admin-rte__btn" onclick="execRte('bold')"><b>B</b></button>
              <button type="button" class="admin-rte__btn" onclick="execRte('italic')"><i>I</i></button>
              <button type="button" class="admin-rte__btn" onclick="execRte('formatBlock', 'h2')">H2</button>
              <button type="button" class="admin-rte__btn" onclick="execRte('formatBlock', 'h3')">H3</button>
              <button type="button" class="admin-rte__btn" onclick="execRte('insertUnorderedList')">• รายการ</button>
              <button type="button" class="admin-rte__btn" onclick="addRteLink()">ลิงก์</button>
              <button type="button" class="admin-rte__btn" onclick="execRte('removeFormat')">ล้างรูปแบบ</button>
            </div>
            <div
              id="rich-editor"
              class="admin-rte__editor"
              contenteditable="true"
            ></div>
          </div>
          <textarea id="hidden-content" name="content" style="display:none;">{{ old('content', $article->content ?? '') }}</textarea>
        </div>

        <p class="review-section-divider">SEO</p>

        <div class="review-field">
          <label class="admin-label" for="meta_description">Meta Description (สำหรับ Google)</label>
          <input
            type="text"
            id="meta_description"
            name="meta_description"
            class="admin-input"
            value="{{ old('meta_description', $article->meta_description ?? '') }}"
            placeholder="อธิบายบทความใน 120-160 ตัวอักษร"
          />
        </div>

        <div class="review-field">
          <label class="admin-label" for="keywords">Keywords หลัก</label>
          <input
            type="text"
            id="keywords"
            name="keywords"
            class="admin-input"
            value="{{ old('keywords', $article->keywords ?? '') }}"
            placeholder="เช่น เลขมงคล, เบอร์มงคล 2026"
          />
        </div>

        <div class="review-field">
          <label class="admin-label" for="lsi_keywords">LSI Keywords (คั่นด้วย ,)</label>
          <input
            type="text"
            id="lsi_keywords"
            name="lsi_keywords"
            class="admin-input"
            value="{{ old('lsi_keywords', $article->lsi_keywords ?? '') }}"
            placeholder="เช่น เลขดี, ดูดวง, พลังตัวเลข, ทำนายดวงชะตา"
          />
        </div>

        <p class="review-section-divider">การเผยแพร่</p>

        <div class="review-field">
          <div class="publish-row">
            <div class="admin-field" style="margin: 0;">
              <label class="admin-label" for="published_at">วันที่เผยแพร่ (ตั้งเวลาล่วงหน้าได้)</label>
              <input
                type="datetime-local"
                id="published_at"
                name="published_at"
                class="admin-input"
                value="{{ old('published_at', $article && $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : $fbDatetimeLocal) }}"
              />
              <p style="margin:5px 0 0;font-size:11px;color:#94a3b8;">⚠ ตรวจสอบวันก่อน Generate Prompt เพราะงานเทศกาลแต่ละปีวันต่างกัน</p>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;padding-bottom:10px;">
              <div class="publish-toggle">
                <input type="hidden" name="is_published" value="0" />
                <input type="checkbox" id="is_published" name="is_published" value="1"
                  {{ old('is_published', $article->is_published ?? false) ? 'checked' : '' }} />
                <label for="is_published">เผยแพร่ทันที</label>
              </div>
              <div class="publish-toggle">
                <input type="hidden" name="is_auto_post" value="0" />
                <input type="checkbox" id="is_auto_post" name="is_auto_post" value="1"
                  {{ old('is_auto_post', $article->is_auto_post ?? true) ? 'checked' : '' }} />
                <label for="is_auto_post">Auto-post Social</label>
              </div>
            </div>
          </div>
        </div>

        <div class="review-actions">
          <div style="display:flex;align-items:center;gap:0;order:-1;border:1px solid #7c3aed;border-radius:8px;overflow:hidden;">
            <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;cursor:pointer;font-size:13px;font-weight:700;background:#f5f3ff;color:#7c3aed;">
              <input type="radio" name="prompt_length" value="short" checked style="accent-color:#7c3aed;"> สั้น <span style="font-weight:400;color:#a78bfa;">(150-300)</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;cursor:pointer;font-size:13px;font-weight:700;border-left:1px solid #7c3aed;background:#f5f3ff;color:#7c3aed;">
              <input type="radio" name="prompt_length" value="long" style="accent-color:#7c3aed;"> ยาว <span style="font-weight:400;color:#a78bfa;">(500-1000)</span>
            </label>
          </div>
          <button type="button" id="btn-gen-prompt" class="review-action"
            style="background:#7c3aed;color:#fff;order:-1;">
            ✨ Generate Prompt Article
          </button>
          <button type="submit" name="_action" value="draft" class="review-action review-action--draft">
            บันทึก Draft
          </button>
          <button
            type="submit"
            name="_action"
            value="approve"
            class="review-action review-action--approve"
            onclick="document.getElementById('is_published').checked = false;"
          >
            อนุมัติ (Draft)
          </button>
          <button
            type="submit"
            name="_action"
            value="publish"
            class="review-action review-action--publish"
            onclick="document.getElementById('is_published').checked = true;"
          >
            อนุมัติ &amp; เผยแพร่
          </button>

          @if ($article)
            <a
              href="{{ route('admin.articles.edit', $article) }}"
              target="_blank"
              class="review-action"
              style="background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; text-decoration: none;"
            >
              เปิดหน้าแก้ไขบทความ ↗
            </a>
          @endif
        </div>
        {{-- JSON Import --}}
        <div class="json-import-section">
          <p class="json-import-title">📥 นำเข้า JSON จาก AI</p>
          <textarea
            id="json-import-input"
            class="json-import-textarea"
            placeholder='วาง JSON ที่ได้จาก AI ตรงนี้ แล้วกด "นำเข้า" เพื่อกรอกข้อมูลอัตโนมัติ...'
          ></textarea>
          <p id="json-import-error" class="json-import-error"></p>
          <button type="button" id="btn-json-import" class="json-import-btn">นำเข้า JSON → กรอกฟอร์ม</button>
        </div>

      </form>
    </section>

  </div>

  {{-- Generate Prompt Modal --}}
  <div id="prompt-overlay" class="prompt-overlay" role="dialog" aria-modal="true">
    <div class="prompt-modal">
      <div class="prompt-modal__head">
        <h3 class="prompt-modal__title">✨ Prompt สำหรับ AI</h3>
        <button class="prompt-modal__close" id="btn-close-prompt" aria-label="ปิด">×</button>
      </div>
      <div class="prompt-modal__body">
        <textarea id="prompt-output" class="prompt-modal__textarea" readonly></textarea>
      </div>
      <div class="prompt-modal__foot">
        <button class="prompt-copy-btn" id="btn-copy-prompt">คัดลอก Prompt</button>
        <span id="copy-feedback" style="font-size:13px;color:#059669;font-weight:700;align-self:center;display:none;">✓ คัดลอกแล้ว!</span>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  // --- RTE setup ---
  const editor    = document.getElementById('rich-editor');
  const hiddenTxt = document.getElementById('hidden-content');

  // Load initial content into editor
  if (hiddenTxt.value.trim() !== '') {
    editor.innerHTML = hiddenTxt.value;
  }

  function execRte(cmd, val) {
    document.execCommand(cmd, false, val ?? null);
    editor.focus();
  }

  function addRteLink() {
    const url = prompt('URL ลิงก์:');
    if (url) execRte('createLink', url);
  }

  // Sync editor → hidden textarea before submit
  document.getElementById('review-form').addEventListener('submit', () => {
    hiddenTxt.value = editor.innerHTML;
  });

  // --- Slug auto-generate from title ---
  const titleInput    = document.getElementById('title');
  const slugInput     = document.getElementById('slug');
  const slugFeedback  = document.getElementById('slug-feedback');
  let slugManual      = slugInput.value.trim() !== '';

  function toSlug(str) {
    return str
      .toLowerCase()
      .replace(/[^฀-๿a-z0-9\s-]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }

  titleInput.addEventListener('input', () => {
    if (!slugManual) {
      slugInput.value = toSlug(titleInput.value);
      slugFeedback.textContent = '';
    }
  });

  slugInput.addEventListener('input', () => {
    slugManual = slugInput.value.trim() !== '';
    slugFeedback.textContent = '';
  });

  // Slug uniqueness check
  let slugTimer;
  slugInput.addEventListener('input', () => {
    clearTimeout(slugTimer);
    const val = slugInput.value.trim();
    if (val === '') return;
    slugTimer = setTimeout(async () => {
      try {
        const articleId = {{ $article?->id ?? 'null' }};
        const res = await fetch(`/admin/articles/check-slug?slug=${encodeURIComponent(val)}&exclude=${articleId ?? ''}`);
        const data = await res.json();
        slugFeedback.textContent = data.available ? '✓ Slug ว่างอยู่' : '⚠ Slug นี้ถูกใช้แล้ว';
        slugFeedback.style.color  = data.available ? '#059669' : '#dc2626';
      } catch {
        slugFeedback.textContent = '';
      }
    }, 500);
  });

  // ─────────────────────────────────────────────
  // GENERATE PROMPT
  // ─────────────────────────────────────────────
  const fbMessage      = @json($fbMessage);
  const fbCreatedIso   = @json($fbCreatedIso);
  const fbDateDisplay  = @json($fbDate);

  document.getElementById('btn-gen-prompt').addEventListener('click', () => {
    const publishedAt = document.getElementById('published_at').value;
    if (!publishedAt) {
      alert('กรุณากรอกวันที่เผยแพร่ก่อน แล้วค่อย Generate Prompt\n(ตรวจสอบวันงานเทศกาลให้ถูกต้องก่อนด้วย)');
      document.getElementById('published_at').focus();
      return;
    }

    const publishedDate = publishedAt.slice(0, 10);
    const length = document.querySelector('input[name="prompt_length"]:checked').value;
    const isShort = length === 'short';
    const wordRange    = isShort ? '150-300' : '500-1000';
    const constraint   = isShort ? 'CONTENT_150_TO_300_WORDS' : 'CONTENT_500_TO_1000_WORDS';
    const contentHint  = isShort
      ? 'เนื้อหา HTML กระชับ ใช้ h2 p ul li เท่านั้น ห้าม h1 ความยาว 150-300 คำ'
      : 'เนื้อหา HTML ใช้ h2 h3 p ul li เท่านั้น ห้าม h1 ความยาว 500-1000 คำ มีหัวข้อย่อยอย่างน้อย 3 หัวข้อ';

    const prompt = `ช่วยเขียนบทความใหม่จากเนื้อหาต้นฉบับด้านล่างนี้ให้สมบูรณ์และน่าสนใจ โดยอ้างอิงข้อมูลจากแหล่งที่น่าเชื่อถือเท่านั้น เขียนเป็นภาษาไทย ความยาว ${wordRange} คำ

──── เนื้อหาต้นฉบับจาก Facebook (โพสต์เมื่อ ${fbDateDisplay}) ────
${fbMessage}
──────────────────────────────────────────────────────────────────────

ให้ตอบกลับเป็น JSON array รูปแบบนี้เท่านั้น ห้ามมีข้อความอื่นนอก JSON:

[
  {
    "_constraints": "${constraint} | NO_YEAR_IN_SLUG | HTML_FORMAT_ONLY | THAI_LANGUAGE_ONLY | EVERGREEN_CONTENT_NO_DATE_REFERENCES",
    "title": "พาดหัวที่ดึงดูดใจ ใส่ปี พ.ศ. ได้ถ้าเหมาะสม",
    "slug": "url-slug-ภาษาอังกฤษ-ห้ามใส่ปี-คั่นด้วย-hyphen",
    "excerpt": "คำเกริ่นนำ 1-2 ประโยค ดึงดูดให้อ่านต่อ ไม่เกิน 160 ตัวอักษร",
    "content": "<h2>หัวข้อหลัก</h2><p>${contentHint}</p>",
    "meta_description": "สรุปเนื้อหาสำหรับ Google Search ความยาว 120-155 ตัวอักษร",
    "keywords": "keyword หลัก 1, keyword หลัก 2, keyword หลัก 3, keyword หลัก 4, keyword หลัก 5",
    "lsi_keywords": "คำค้นหาเกี่ยวข้อง 1, คำที่ 2, คำที่ 3, คำที่ 4, คำที่ 5, คำที่ 6, คำที่ 7, คำที่ 8, คำที่ 9, คำที่ 10",
    "is_published": false,
    "published_at": "${publishedDate}T08:00:00+07:00",
    "is_auto_post": true,
    "image_guidelines": {
      "landscape_prompt": "Describe an image in English that visually represents this article topic, landscape 16:9 ratio, photorealistic style, no text in image",
      "square_prompt": "Describe an image in English that visually represents this article topic, square 1:1 ratio, photorealistic style, no text in image"
    }
  }
]`;

    document.getElementById('prompt-output').value = prompt;
    document.getElementById('prompt-overlay').classList.add('is-open');
  });

  document.getElementById('btn-close-prompt').addEventListener('click', () => {
    document.getElementById('prompt-overlay').classList.remove('is-open');
  });
  document.getElementById('prompt-overlay').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) e.currentTarget.classList.remove('is-open');
  });

  document.getElementById('btn-copy-prompt').addEventListener('click', async () => {
    const text = document.getElementById('prompt-output').value;
    await navigator.clipboard.writeText(text);
    const btn = document.getElementById('btn-copy-prompt');
    const fb  = document.getElementById('copy-feedback');
    btn.textContent = '✓ คัดลอกแล้ว!';
    btn.classList.add('copied');
    fb.style.display = 'inline';
    setTimeout(() => {
      btn.textContent = 'คัดลอก Prompt';
      btn.classList.remove('copied');
      fb.style.display = 'none';
    }, 2500);
  });

  // ─────────────────────────────────────────────
  // JSON IMPORT → auto-fill form
  // ─────────────────────────────────────────────
  document.getElementById('btn-json-import').addEventListener('click', () => {
    const raw       = document.getElementById('json-import-input').value.trim();
    const errorEl   = document.getElementById('json-import-error');
    errorEl.style.display = 'none';

    if (!raw) {
      errorEl.textContent = 'กรุณาวาง JSON ก่อน';
      errorEl.style.display = 'block';
      return;
    }

    let data;
    try {
      const parsed = JSON.parse(raw);
      data = Array.isArray(parsed) ? parsed[0] : parsed;
    } catch {
      errorEl.textContent = 'JSON ไม่ถูกต้อง กรุณาตรวจสอบรูปแบบ';
      errorEl.style.display = 'block';
      return;
    }

    // Fill text fields
    const setVal = (id, val) => {
      const el = document.getElementById(id);
      if (el && val !== undefined && val !== null) el.value = val;
    };

    setVal('title',            data.title);
    setVal('slug',             data.slug);
    setVal('excerpt',          data.excerpt);
    setVal('meta_description', data.meta_description);
    setVal('keywords',         data.keywords);
    setVal('lsi_keywords',     data.lsi_keywords);

    // Content → RTE
    if (data.content) {
      editor.innerHTML        = data.content;
      hiddenTxt.value         = data.content;
    }

    // published_at
    if (data.published_at) {
      const dt = data.published_at.slice(0, 16).replace(' ', 'T');
      setVal('published_at', dt);
    }

    // Checkboxes
    if (data.is_published !== undefined) {
      document.getElementById('is_published').checked = !!data.is_published;
    }
    if (data.is_auto_post !== undefined) {
      document.getElementById('is_auto_post').checked = !!data.is_auto_post;
    }

    // image_guidelines (hidden inputs — stored in hidden fields for approve route)
    if (data.image_guidelines) {
      let lgEl = document.getElementById('landscape_prompt');
      let sqEl = document.getElementById('square_prompt');
      if (!lgEl) {
        lgEl = Object.assign(document.createElement('input'), { type:'hidden', id:'landscape_prompt', name:'image_guidelines[landscape_prompt]' });
        document.getElementById('review-form').appendChild(lgEl);
      }
      if (!sqEl) {
        sqEl = Object.assign(document.createElement('input'), { type:'hidden', id:'square_prompt', name:'image_guidelines[square_prompt]' });
        document.getElementById('review-form').appendChild(sqEl);
      }
      lgEl.value = data.image_guidelines.landscape_prompt ?? '';
      sqEl.value = data.image_guidelines.square_prompt    ?? '';
    }

    // Mark slug as manual so it won't be overwritten
    slugManual = true;

    // Clear import area and show success
    document.getElementById('json-import-input').value = '';
    errorEl.textContent    = '✓ กรอกข้อมูลเรียบร้อย ตรวจสอบแล้วกด Approve ได้เลย';
    errorEl.style.color    = '#059669';
    errorEl.style.display  = 'block';

    // Scroll to title
    document.getElementById('title').scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
</script>
@endpush
