@extends('layouts.admin')

@section('title', 'Supernumber Admin | Edit Article')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Edit Article</h1>
      <p class="admin-subtitle">แก้ไขข้อมูลบทความและการเผยแพร่</p>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form
      id="article-update-form"
      action="{{ route('admin.articles.update', $article) }}"
      method="post"
      enctype="multipart/form-data"
      class="admin-form"
      data-has-cover="{{ $article->cover_image_path ? '1' : '0' }}"
    >
      @csrf
      @method('PUT')

      <div class="admin-field">
        <label for="title">หัวข้อบทความ</label>
        <input type="text" id="title" name="title" class="admin-input" value="{{ old('title', $article->title) }}" required />
      </div>

      <div class="admin-field">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $article->slug) }}" />
      </div>

      <div class="admin-field">
        <label for="excerpt">คำเกริ่นสั้น</label>
        <textarea id="excerpt" name="excerpt" class="admin-input" style="min-height: 90px; padding-top: 12px;">{{ old('excerpt', $article->excerpt) }}</textarea>
      </div>

      <div class="admin-field">
        <label for="content">เนื้อหาบทความ</label>
        <div class="admin-rte" data-rte-shell>
          <div class="admin-rte__toolbar">
            <button type="button" class="admin-rte__btn" data-rte-cmd="bold">B</button>
            <button type="button" class="admin-rte__btn" data-rte-cmd="italic">I</button>
            <button type="button" class="admin-rte__btn" data-rte-cmd="underline">U</button>
            <button type="button" class="admin-rte__btn" data-rte-cmd="formatBlock" data-rte-value="h2">H2</button>
            <button type="button" class="admin-rte__btn" data-rte-cmd="insertUnorderedList">• List</button>
            <button type="button" class="admin-rte__btn" data-rte-cmd="insertOrderedList">1. List</button>
            <button type="button" class="admin-rte__btn" data-rte-action="link">Link</button>
            <button type="button" class="admin-rte__btn" data-rte-cmd="removeFormat">Clear</button>
          </div>
          <div class="admin-rte__editor" contenteditable="true" data-rte-editor data-placeholder="พิมพ์เนื้อหาบทความที่นี่..."></div>
        </div>
        <textarea id="content" name="content" class="admin-input" style="display: none;" required>{{ old('content', $article->content) }}</textarea>
      </div>

      <div class="admin-field">
        <label for="meta_description">Meta Description</label>
        <input type="text" id="meta_description" name="meta_description" class="admin-input" value="{{ old('meta_description', $article->meta_description) }}" maxlength="255" />
      </div>

      <div class="admin-field">
        <label for="published_at">เวลาเผยแพร่</label>
        <input
          type="datetime-local"
          id="published_at"
          name="published_at"
          class="admin-input"
          value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\\TH:i')) }}"
          @disabled($article->is_published)
        />
        @if ($article->is_published)
          <p class="admin-subtitle" style="margin: 0;">โพสต์นี้เผยแพร่แล้ว จึงล็อกเวลาเผยแพร่ไว้ (ใช้ Archive Post หากต้องการถอดออก)</p>
        @endif
      </div>

      <div class="admin-field">
        <label for="cover_image">เปลี่ยนรูปปก</label>
        <input type="file" id="cover_image" name="cover_image" class="admin-input" accept="image/*" />
        @if ($article->cover_image_path)
          <p class="admin-subtitle" style="margin: 0;">ไฟล์ปัจจุบัน: <code>{{ $article->cover_image_path }}</code></p>
        @endif
        <p id="cover-selected-path" class="admin-subtitle" style="margin: 0; display: none;"></p>
      </div>

      @if ($article->cover_image_path)
        <div class="admin-field">
          <div id="cover-preview-wrap" style="position: relative; width: 220px;">
            <img id="cover-preview-image" src="{{ asset('storage/' . $article->cover_image_path) }}" alt="{{ $article->title }}" style="width: 220px; border-radius: 12px; border:1px solid #d8e0ec; display:block;" />
            <button
              type="button"
              id="cover-replace-toggle"
              title="เปลี่ยนรูปปก"
              style="position:absolute; top:8px; right:8px; width:28px; height:28px; border-radius:999px; border:1px solid #d8e0ec; background:#ffffff; color:#334155; font-size:18px; line-height:1; display:grid; place-items:center; cursor:pointer;"
            >
              ×
            </button>
          </div>
          <p id="cover-replace-note" class="admin-subtitle" style="margin: 0; display:none; color:#b45309;">
            โหมดเปลี่ยนรูปถูกเปิดแล้ว: รูปเดิมจะถูกลบจริงเมื่อเลือกไฟล์ใหม่และกดบันทึกการเปลี่ยนแปลง
          </p>
        </div>
      @endif

      <label class="admin-field" style="grid-template-columns: auto 1fr; align-items: center; gap: 10px; display: grid;">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published)) />
        <span style="font-size: 14px;">เผยแพร่บทความ</span>
      </label>
      <p class="admin-subtitle" style="margin: 0;">สถานะปัจจุบัน: <strong>{{ $article->is_published ? 'Published' : 'Draft' }}</strong></p>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="submit" class="admin-button">บันทึกการเปลี่ยนแปลง</button>
      </div>
    </form>
    <div
      id="article-cover-confirm-modal"
      style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 9999; display: none; place-items: center; padding: 20px;"
    >
      <div style="width:min(560px, 100%); background:#fff; border-radius:16px; border:1px solid #d8e0ec; padding:18px; text-align:center;">
        <h3 style="margin:0; font-size:20px;">ยืนยันการบันทึก</h3>
        <p style="margin:10px 0 0; color:#4f5f7b; line-height:1.6; text-align:center;">ถ้าไม่ใส่รูปจะใช้รูปเก่า ต้องการดำเนินการต่อหรือไม่?</p>
        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
          <button type="button" id="article-cover-confirm-continue" class="admin-button">ดำเนินการต่อ</button>
          <button type="button" id="article-cover-confirm-close" class="admin-button" style="background:#60708e;">ปิดเพื่อกลับไปใส่รูป</button>
        </div>
      </div>
    </div>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 16px;">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title">Comments ({{ $comments->count() }})</h2>
        <p class="admin-feature-card__hint">คอมเมนต์ทั้งหมดของโพสต์นี้ พร้อมปุ่ม Archive / Unarchive</p>
      </div>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เวลา</th>
            <th>ผู้คอมเมนต์</th>
            <th>เนื้อหา</th>
            <th>สถานะ</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($comments as $comment)
            <tr>
              <td>{{ optional($comment->created_at)->format('Y-m-d H:i') }}</td>
              <td>{{ $comment->commenter_name }}</td>
              <td style="max-width: 460px; white-space: normal; line-height: 1.6;">{{ $comment->content }}</td>
              <td>
                @if ($comment->status === \App\Models\ArticleComment::STATUS_APPROVED)
                  <span class="admin-status-pill admin-status-pill--active">Approved</span>
                @elseif ($comment->status === \App\Models\ArticleComment::STATUS_REJECTED)
                  <span class="admin-status-pill" style="background:#fef3f2; color:#b42318; border-color:#fecdca;">Archived</span>
                @else
                  <span class="admin-status-pill admin-status-pill--hold">Pending</span>
                @endif
              </td>
              <td class="admin-action-cell">
                @if ($comment->status !== \App\Models\ArticleComment::STATUS_REJECTED)
                  <form action="{{ route('admin.articles.comments.archive', [$article, $comment]) }}" method="post" onsubmit="return confirm('Archive คอมเมนต์นี้?');">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact" style="background:#b45309;">Archive</button>
                  </form>
                @else
                  <form action="{{ route('admin.articles.comments.unarchive', [$article, $comment]) }}" method="post" onsubmit="return confirm('Unarchive คอมเมนต์นี้?');">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact" style="background:#2563eb;">Unarchive</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="admin-muted">ยังไม่มีคอมเมนต์ในโพสต์นี้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      const updateForm = document.getElementById("article-update-form");
      const coverInput = document.getElementById("cover_image");
      const modal = document.getElementById("article-cover-confirm-modal");
      const continueBtn = document.getElementById("article-cover-confirm-continue");
      const closeBtn = document.getElementById("article-cover-confirm-close");
      const replaceToggleBtn = document.getElementById("cover-replace-toggle");
      const replaceNote = document.getElementById("cover-replace-note");

      let bypassCoverConfirm = false;
      let replaceMode = false;

      const initRichText = (shell) => {
        const editor = shell.querySelector("[data-rte-editor]");
        const textarea = shell.parentElement.querySelector('textarea[name="content"]');
        const form = shell.closest("form");
        if (!editor || !textarea || !form) return;

        editor.innerHTML = textarea.value || "";

        shell.querySelectorAll("[data-rte-cmd]").forEach((button) => {
          button.addEventListener("click", () => {
            const command = button.dataset.rteCmd;
            const value = button.dataset.rteValue ?? null;
            editor.focus();
            document.execCommand(command, false, value);
          });
        });

        shell.querySelectorAll("[data-rte-action='link']").forEach((button) => {
          button.addEventListener("click", () => {
            const url = window.prompt("ใส่ URL เช่น https://example.com");
            if (!url) return;
            editor.focus();
            document.execCommand("createLink", false, url);
          });
        });

        form.addEventListener("submit", () => {
          textarea.value = editor.innerHTML.trim();
        });
      };

      document.querySelectorAll("[data-rte-shell]").forEach(initRichText);

      if (updateForm && coverInput && modal && continueBtn && closeBtn) {
        if (replaceToggleBtn) {
          replaceToggleBtn.addEventListener("click", () => {
            replaceMode = !replaceMode;

            if (replaceNote) {
              replaceNote.style.display = replaceMode ? "block" : "none";
            }

            if (replaceMode) {
              coverInput.focus();
              coverInput.click();
            }
          });
        }

        coverInput.addEventListener("change", () => {
          const selectedPathEl = document.getElementById("cover-selected-path");
          if (!selectedPathEl) return;

          if (coverInput.files && coverInput.files.length > 0) {
            selectedPathEl.style.display = "block";
            selectedPathEl.innerHTML = "ไฟล์ที่เลือก: <code>" + coverInput.files[0].name + "</code>";
            replaceMode = false;
            if (replaceNote) {
              replaceNote.style.display = "none";
            }
          } else {
            selectedPathEl.style.display = "none";
            selectedPathEl.textContent = "";
          }
        });

        updateForm.addEventListener("submit", (event) => {
          const hasCover = updateForm.dataset.hasCover === "1";
          const hasNewFile = coverInput.files && coverInput.files.length > 0;

          if (bypassCoverConfirm || !hasCover || hasNewFile) {
            return;
          }

          if (replaceMode && !hasNewFile) {
            event.preventDefault();
            window.alert("โปรดเลือกรูปใหม่ก่อนบันทึก ระบบจะลบรูปเก่าเมื่อมีรูปใหม่และกดบันทึกเท่านั้น");
            coverInput.focus();
            return;
          }

          event.preventDefault();
          modal.style.display = "grid";
        });

        continueBtn.addEventListener("click", () => {
          bypassCoverConfirm = true;
          modal.style.display = "none";
          updateForm.requestSubmit();
        });

        closeBtn.addEventListener("click", () => {
          modal.style.display = "none";
          coverInput.focus();
        });
      }
    })();
  </script>
@endpush
