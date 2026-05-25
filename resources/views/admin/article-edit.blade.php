@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขบทความ')

@section('content')
  @php
    $canToggleArticleVisibility = session('admin_user_role') === \App\Models\User::ROLE_MANAGER;
    $canDeleteArticle = session('admin_user_role') === \App\Models\User::ROLE_MANAGER;
    $imageGuidelines = old('image_guidelines', $article->image_guidelines ?? []);
    $landscapePrompt = is_array($imageGuidelines) ? ($imageGuidelines['landscape_prompt'] ?? '') : '';
    $squarePrompt = is_array($imageGuidelines) ? ($imageGuidelines['square_prompt'] ?? '') : '';
  @endphp


  <style>
    /* Premium Obsidian & Gold Redesign for Edit Article Page */
    .admin-card {
        background: #ffffff !important;
        border: 1px solid rgba(216, 163, 74, 0.16) !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(70, 55, 43, 0.05) !important;
        padding: 28px !important;
    }
    .admin-field label {
        color: #4b382a !important; /* Elegant warm brown */
        font-weight: 700 !important;
        font-size: 14px !important;
        letter-spacing: 0.02em !important;
        margin-bottom: 6px !important;
    }
    .admin-input, .admin-select {
        border: 1.5px solid rgba(216, 163, 74, 0.25) !important;
        border-radius: 10px !important;
        transition: all 0.25s ease !important;
        font-size: 15px !important;
        color: #1e1915 !important;
        background: #fff !important;
        outline: none !important;
    }
    .admin-input:focus, .admin-select:focus {
        border-color: #d8a34a !important;
        box-shadow: 0 0 0 4px rgba(216, 163, 74, 0.12) !important;
        background: #fdfbf7 !important;
    }
    
    /* Rich Text Editor Premium Theme */
    .admin-rte {
        border: 1.5px solid rgba(216, 163, 74, 0.3) !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02) !important;
        background: #fff !important;
        transition: border-color 0.25s ease !important;
    }
    .admin-rte:focus-within {
        border-color: #d8a34a !important;
        box-shadow: 0 0 0 4px rgba(216, 163, 74, 0.12) !important;
    }
    .admin-rte__toolbar {
        background: #fdfbf7 !important;
        border-bottom: 1.5px solid rgba(216, 163, 74, 0.2) !important;
        padding: 12px 14px !important;
        gap: 8px !important;
        display: flex !important;
        flex-wrap: wrap !important;
    }
    .admin-rte__btn {
        background: #fff !important;
        border: 1.5px solid rgba(216, 163, 74, 0.25) !important;
        border-radius: 8px !important;
        color: #4b382a !important;
        font-weight: 700 !important;
        transition: all 0.2s ease !important;
        min-height: 36px !important;
        padding: 6px 12px !important;
        cursor: pointer !important;
    }
    .admin-rte__btn:hover {
        background: #d8a34a !important;
        color: #fff !important;
        border-color: #d8a34a !important;
        transform: translateY(-1px) !important;
    }
    .admin-rte__editor {
        padding: 20px 22px !important;
        font-size: 16px !important;
        line-height: 1.85 !important;
        color: #1e1915 !important;
        background: #fff !important;
        min-height: 600px !important; /* Spacious full article display for typing */
        overflow-y: visible !important; /* Grow fully without inner scrollbar in app */
    }
    
    /* Drag & Drop zones */
    .admin-drop-zone {
        border: 1.5px dashed rgba(216, 163, 74, 0.3) !important;
        border-radius: 12px !important;
        padding: 16px !important;
        text-align: center !important;
        background: #fdfbf8 !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 12px !important;
        min-height: 72px !important;
        position: relative !important;
        transition: all 0.3s ease !important;
    }
    .admin-drop-zone.is-dragover {
        border-color: #d8a34a !important;
        background: #fdfbf0 !important;
    }
    .admin-drop-zone__input {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        border: 0;
        overflow: hidden;
        clip: rect(0 0 0 0);
        clip-path: inset(50%);
        white-space: nowrap;
        opacity: 0;
    }
    .admin-drop-zone__button {
        border: 1.5px solid rgba(216, 163, 74, 0.3) !important;
        background: #fff !important;
        color: #4b382a !important;
        font-weight: 700 !important;
        padding: 8px 14px !important;
        border-radius: 8px !important;
        cursor: pointer !important;
    }
    .drop-text {
        color: #7a6c62 !important;
        font-weight: 700 !important;
        text-align: left !important;
    }
    .admin-preview-img {
        width: 100% !important;
        max-width: 220px !important;
        border-radius: 12px !important;
        border: 1.5px solid rgba(216, 163, 74, 0.2) !important;
        display: block !important;
        background: #fff !important;
    }
    
    /* Image upload cards and prompts redesign */
    .article-image-card {
        border: 1.5px solid rgba(216, 163, 74, 0.2) !important;
        border-radius: 16px !important;
        background: #fff !important;
        padding: 22px !important;
        box-shadow: 0 6px 20px rgba(70, 55, 43, 0.03) !important;
        margin-top: 28px !important;
    }
    .article-image-card__head {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        gap: 12px !important;
        margin-bottom: 14px !important;
    }
    .article-image-card__title {
        margin: 0 !important;
        color: #1e293b !important;
        font-size: 16px !important;
        font-weight: 800 !important;
        line-height: 1.35 !important;
    }
    .article-image-card__ratio {
        flex: 0 0 auto !important;
        border: 1.5px solid rgba(216, 163, 74, 0.25) !important;
        border-radius: 999px !important;
        background: #fdfbf7 !important;
        color: #8b5a1f !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        padding: 4px 8px !important;
    }
    .article-image-card--square .article-image-card__ratio {
        border-color: #bbf7d0 !important;
        background: #ecfdf5 !important;
        color: #047857 !important;
    }
    
    /* Buttons and Action bar */
    .article-edit-actions {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        border-top: 1.5px solid rgba(216, 163, 74, 0.15) !important;
        padding-top: 24px !important;
        gap: 12px !important;
        margin-top: 28px !important;
    }
    .article-edit-action {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 46px !important;
        padding: 11px 20px !important;
        border: 1px solid transparent !important;
        border-radius: 10px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        cursor: pointer !important;
        transition: all 0.25s ease !important;
    }
    .article-edit-action:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
    .article-edit-action--primary {
        background: linear-gradient(135deg, #1d1816 0%, #46372b 100%) !important;
        border: 1px solid rgba(216, 163, 74, 0.4) !important;
        color: #e1b155 !important;
    }
    .article-edit-action--primary:hover {
        background: linear-gradient(135deg, #2c2420 0%, #5c493a 100%) !important;
        color: #fff !important;
        border-color: #e1b155 !important;
    }
    .article-edit-action--publish {
        background: #047857 !important;
        border-color: #047857 !important;
        color: #fff !important;
    }
    .article-edit-action--hide {
        background: #b45309 !important;
        border-color: #b45309 !important;
        color: #fff !important;
    }
    .article-edit-action--delete {
        background: #dc2626 !important;
        border-color: #dc2626 !important;
        color: #fff !important;
    }
    
    /* Copy prompt buttons */
    .copy-prompt-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        padding: 6px 12px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        background: #fdfbf7 !important;
        color: #8b5a1f !important;
        border: 1.5px solid rgba(216, 163, 74, 0.3) !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }
    .copy-prompt-btn:hover {
        background: #d8a34a !important;
        color: #fff !important;
        border-color: #d8a34a !important;
        transform: translateY(-1px) !important;
    }
    .copy-prompt-btn.success {
        background: #edf9f5 !important;
        color: #1b8b6f !important;
        border-color: #cbe9de !important;
    }
    
    /* Highlights */
    .article-edit-highlight {
        margin-top: 20px !important;
        border-left: 4px solid #d8a34a !important;
        padding-left: 15px !important;
    }
    .article-edit-highlight--landscape {
        margin-top: 30px !important;
        border-left-color: #d8a34a !important;
    }
    .article-edit-highlight--square {
        margin-top: 30px !important;
        border-left-color: #10b981 !important;
    }
    
    /* Responsive Media Queries for App/Mobile views */
    @media (max-width: 768px) {
        .admin-image-grid { grid-template-columns: 1fr !important; }
        .admin-drop-zone { display: grid !important; justify-items: center !important; text-align: center !important; }
        .drop-text { text-align: center !important; }
        .article-edit-highlight,
        .article-edit-highlight--landscape,
        .article-edit-highlight--square {
            padding-left: 0 !important;
            border-left: 0 !important;
            border-top: 4px solid #d8a34a !important;
            padding-top: 12px !important;
        }
        .article-edit-highlight--square { border-top-color: #10b981 !important; }
        .admin-preview-img { width: 100% !important; max-width: 100% !important; height: auto !important; }
        .article-edit-actions { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        .article-edit-action { width: 100% !important; min-width: 0 !important; padding: 11px 10px !important; font-size: 13px !important; }
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px;">
              <label for="landscape_prompt" style="margin-bottom: 0;">Prompt รูปหน้ารวมบทความ 16:9</label>
              <button type="button" class="copy-prompt-btn" onclick="copyFormattedPrompt('landscape_prompt', this)">
                📋 คัดลอก Prompt
              </button>
            </div>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 8px;">
              <label for="square_prompt" style="margin-bottom: 0;">Prompt รูปภาพบทความ 1:1</label>
              <button type="button" class="copy-prompt-btn" onclick="copyFormattedPrompt('square_prompt', this)">
                📋 คัดลอก Prompt
              </button>
            </div>
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
        <label style="display: flex; align-items: center; cursor: pointer;">
          <input type="hidden" name="is_pinned" value="0">
          <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $article->is_pinned ?? false) ? 'checked' : '' }} style="width: 20px; height: 20px; margin-right: 10px;">
          <span style="font-size: 16px; font-weight: bold;">📌 ปักหมุดบทความนี้ไว้ด้านบนหน้ารายการ</span>
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


  // ---- Copy Formatted Prompt Helper ----
  const showCopySuccess = (btn) => {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '✅ คัดลอกสำเร็จ!';
    btn.classList.add('success');
    btn.style.pointerEvents = 'none';
    setTimeout(() => {
      btn.innerHTML = originalHtml;
      btn.classList.remove('success');
      btn.style.pointerEvents = 'auto';
    }, 2000);
  };

  const fallbackCopyText = (text, btn) => {
    const tempTextArea = document.createElement('textarea');
    tempTextArea.value = text;
    tempTextArea.style.top = '0';
    tempTextArea.style.left = '0';
    tempTextArea.style.position = 'fixed';
    tempTextArea.style.width = '2em';
    tempTextArea.style.height = '2em';
    tempTextArea.style.padding = '0';
    tempTextArea.style.border = 'none';
    tempTextArea.style.outline = 'none';
    tempTextArea.style.boxShadow = 'none';
    tempTextArea.style.background = 'transparent';
    document.body.appendChild(tempTextArea);
    tempTextArea.focus();
    tempTextArea.select();
    
    if (navigator.userAgent.match(/ipad|ipod|iphone/i)) {
      const range = document.createRange();
      range.selectNodeContents(tempTextArea);
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      tempTextArea.setSelectionRange(0, 999999);
    }

    try {
      const successful = document.execCommand('copy');
      if (successful) {
        showCopySuccess(btn);
      } else {
        alert('ไม่สามารถคัดลอกได้โดยอัตโนมัติ กรุณาลองคัดลอกด้วยตนเอง');
      }
    } catch (err) {
      console.error('Fallback copy failed', err);
      alert('ไม่สามารถคัดลอกได้โดยอัตโนมัติ กรุณาลองคัดลอกด้วยตนเอง');
    }
    document.body.removeChild(tempTextArea);
  };

  window.copyFormattedPrompt = (textareaId, btn) => {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;
    const text = textarea.value.trim();
    if (!text) {
      alert('กรุณากรอกข้อความ Prompt ก่อนกดคัดลอกครับ');
      textarea.focus();
      return;
    }
    const template = `Here is the template. Please generate an image of [${text}] inside it. Make sure the edges of the generated image softly fade out (gradient blend) to perfectly match and fill the template without harsh borders.`;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(template).then(() => {
        showCopySuccess(btn);
      }).catch(err => {
        fallbackCopyText(template, btn);
      });
    } else {
      fallbackCopyText(template, btn);
    }
  };
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
