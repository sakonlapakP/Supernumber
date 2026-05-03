@extends('layouts.admin')

@section('title', 'Supernumber Admin | บันทึกระบบ')

@section('content')
  @php
    $levelStyles = [
      'ERROR' => ['bg' => '#fef3f2', 'border' => '#fecdca', 'text' => '#b42318'],
      'WARNING' => ['bg' => '#fffaeb', 'border' => '#fedf89', 'text' => '#b54708'],
      'NOTICE' => ['bg' => '#eff8ff', 'border' => '#b2ddff', 'text' => '#175cd3'],
      'INFO' => ['bg' => '#f5f8ff', 'border' => '#d9e4ff', 'text' => '#3538cd'],
      'DEBUG' => ['bg' => '#f8f9fc', 'border' => '#d0d5dd', 'text' => '#344054'],
      'CRITICAL' => ['bg' => '#fef3f2', 'border' => '#fda29b', 'text' => '#912018'],
      'ALERT' => ['bg' => '#fef3f2', 'border' => '#f97066', 'text' => '#912018'],
      'EMERGENCY' => ['bg' => '#fef3f2', 'border' => '#f04438', 'text' => '#7a271a'],
    ];
  @endphp

  <div class="admin-page-head">
    <div>
      <h1>บันทึกระบบ</h1>
      <p class="admin-subtitle">แสดง log จากระบบ, filter ตามไฟล์/ระดับ/วันที่/คำค้น และล้างไฟล์ได้จากหน้าเดียวสำหรับผู้ใช้ระดับ manager</p>
    </div>
    <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
      <a href="{{ route('admin.logs', array_filter($filters, fn ($value) => $value !== '')) }}" class="admin-button admin-button--muted admin-button--compact">รีเฟรช</a>
      <a href="{{ route('admin.logs') }}" class="admin-button admin-button--compact">รีเซ็ต</a>
      <form action="{{ route('admin.logs.clear') }}" method="post" style="margin: 0;" onsubmit="return confirm('ล้างไฟล์ log นี้ใช่หรือไม่?');">
        @csrf
        <input type="hidden" name="file" value="{{ $filters['file'] }}" />
        <button type="submit" class="admin-button admin-button--secondary admin-button--compact">ล้างไฟล์บันทึก</button>
      </form>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form class="admin-form" action="{{ route('admin.logs') }}" method="get">
      <div class="admin-field">
        <label for="log_file">ไฟล์บันทึก</label>
        <select id="log_file" class="admin-input" name="file">
          <option value="{{ $selectedFile['name'] }}">{{ $selectedFile['name'] }}</option>
          @foreach ($availableFiles as $file)
            @if ($file['name'] !== $selectedFile['name'])
              <option value="{{ $file['name'] }}">{{ $file['name'] }}</option>
            @endif
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="log_level">ระดับ</label>
        <select id="log_level" class="admin-input" name="level">
          <option value="">ทั้งหมด</option>
          @foreach ($availableLevels as $level)
            <option value="{{ $level }}" @selected($filters['level'] === $level)>{{ $level }}</option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="log_date">วันที่</label>
        <input id="log_date" class="admin-input" type="date" name="date" value="{{ $filters['date'] }}" list="log-date-options" />
        <datalist id="log-date-options">
          @foreach ($availableDates as $date)
            <option value="{{ $date }}"></option>
          @endforeach
        </datalist>
      </div>

      <div class="admin-field">
        <label for="log_search">ค้นหา</label>
        <input id="log_search" class="admin-input" type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นคำจาก message หรือ stack trace" />
      </div>

      <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
        <button type="submit" class="admin-button admin-button--compact">ใช้ตัวกรอง</button>
      </div>
    </form>
  </section>

  <section class="admin-card admin-feature-card">
    <div style="display: grid; gap: 8px;">
      <div><strong>ไฟล์:</strong> {{ $logPath }}</div>
      <div><strong>ขนาดไฟล์:</strong> {{ number_format($logSize) }} ไบต์</div>
      <div><strong>ข้อมูลที่อ่าน:</strong> {{ number_format($displayedByteCount) }} ไบต์ล่าสุด</div>
      <div><strong>จำนวนรายการ:</strong> {{ number_format($displayedEntryCount) }} / {{ number_format($totalEntryCount) }} รายการ</div>
      <div><strong>ต่อหน้า:</strong> {{ ($filters['date'] === '' && $filters['search'] === '' && $filters['level'] === '') ? '1 วัน (แสดงบันทึกย้อนหลังทีละวัน)' : '50 รายการ' }}</div>
    </div>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 18px;">
    <div style="padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e7edf5; flex-wrap: wrap; gap: 10px;">
      <div>
        <h2 style="margin: 0; font-size: 1.1rem;">ผลลัพธ์บันทึกระบบ</h2>
        <p class="admin-subtitle" style="margin-top: 6px;">แสดงบันทึกย้อนหลัง 3 วันล่าสุด (72 ชม.) แยกตามวัน หากไม่มีการเลือกวันที่</p>
      </div>
      <div id="bulk-actions" style="display: none; align-items: center; gap: 10px;">
        <span id="selected-count" style="font-weight: 600; font-size: 0.9rem; color: #175cd3;">เลือกแล้ว 0 รายการ</span>
        <button type="button" class="admin-button admin-button--compact js-copy-selected">Copy Selected Errors</button>
      </div>
    </div>

    <div style="padding: 20px;">
      @if (! $logExists)
        <div class="admin-muted">ยังไม่พบไฟล์ log หรือไฟล์นี้อ่านไม่ได้</div>
      @elseif ($totalEntryCount === 0)
        <div class="admin-muted">ไฟล์ log ว่างอยู่ในตอนนี้</div>
      @elseif ($displayedEntryCount === 0)
        <div class="admin-muted">ไม่พบรายการที่ตรงกับ filter ที่เลือก</div>
      @else
        <div style="display: grid; gap: 24px;">
          @foreach ($groupedEntries as $date => $dayEntries)
            <div class="log-day-group">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px; background: #f1f5f9; padding: 10px 16px; border-radius: 12px; border-left: 4px solid #64748b;">
                <input type="checkbox" class="js-select-all-day" style="width: 18px; height: 18px; cursor: pointer;">
                <h3 style="margin: 0; font-size: 1rem; color: #334155;">{{ $date }} ({{ count($dayEntries) }} รายการ)</h3>
              </div>

              <div style="display: grid; gap: 14px; padding-left: 10px;">
                @foreach ($dayEntries as $index => $entry)
                  @php
                    $entryLevel = strtoupper((string) ($entry['level'] ?? ''));
                    $levelStyle = $levelStyles[$entryLevel] ?? ['bg' => '#f8f9fc', 'border' => '#d0d5dd', 'text' => '#344054'];
                    $copyPayload = trim((string) ($entry['raw_display'] ?? $entry['raw'] ?? ''));
                    $uniqueId = 'log-' . md5($copyPayload . $index . $date);
                  @endphp

                  <article style="border: 1px solid #dbe4f0; border-radius: 18px; background: #fff; overflow: hidden; position: relative;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 10px 16px; border-bottom: 1px solid #e7edf5; background: #f8fbff;">
                      <input type="checkbox" class="js-log-checkbox" data-copy-text="{{ $copyPayload }}" style="width: 16px; height: 16px; cursor: pointer;">
                      
                      <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; min-width: 0;">
                        <span style="display: inline-flex; align-items: center; min-height: 26px; padding: 2px 8px; border-radius: 999px; background: {{ $levelStyle['bg'] }}; border: 1px solid {{ $levelStyle['border'] }}; color: {{ $levelStyle['text'] }}; font-size: 0.75rem; font-weight: 700;">
                          {{ $entryLevel !== '' ? $entryLevel : 'RAW' }}
                        </span>
                        <span style="font-size: 0.88rem; color: #475467;">{{ $entry['timestamp'] ?: '-' }}</span>
                        @if ($entry['environment'])
                          <span class="admin-muted" style="font-size: 0.82rem;">env: {{ $entry['environment'] }}</span>
                        @endif
                      </div>
                    </div>
                    <div style="padding: 14px 16px;">
                      <div style="font-weight: 600; color: #101828; margin-bottom: 10px; white-space: pre-wrap; font-size: 0.95rem;">{{ $entry['message'] !== '' ? $entry['message'] : '(ไม่มีข้อความ)' }}</div>
                      <div style="display: flex; justify-content: flex-end; margin-bottom: 8px;">
                        <button
                          type="button"
                          class="admin-button admin-button--secondary admin-button--compact js-copy-log-entry"
                          data-copy-text="{{ $copyPayload }}"
                          style="min-width: 92px; font-size: 0.75rem;"
                        >
                          Copy Error
                        </button>
                      </div>
                      <pre style="margin: 0; white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere; background: #0f172a; color: #e2e8f0; border-radius: 12px; padding: 14px; font-size: 0.8rem; line-height: 1.5; max-height: 35vh; overflow: auto;">{{ $entry['raw_display'] ?? $entry['raw'] }}</pre>
                    </div>
                  </article>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>

        @if ($entries->hasPages())
          <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการ application logs" style="margin-top: 24px;">
            @if ($entries->onFirstPage())
              <span>ก่อนหน้า</span>
            @else
              <a href="{{ $entries->previousPageUrl() }}">ก่อนหน้า</a>
            @endif

            @php
              $startPage = max(1, $entries->currentPage() - 2);
              $endPage = min($entries->lastPage(), $entries->currentPage() + 2);
            @endphp

            @for ($page = $startPage; $page <= $endPage; $page++)
              @if ($page === $entries->currentPage())
                <span class="is-active">{{ $page }}</span>
              @else
                <a href="{{ $entries->url($page) }}">{{ $page }}</a>
              @endif
            @endfor

            @if ($entries->hasMorePages())
              <a href="{{ $entries->nextPageUrl() }}">ถัดไป</a>
            @else
              <span>ถัดไป</span>
            @endif
          </nav>
        @endif
      @endif
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      const copyButtons = document.querySelectorAll(".js-copy-log-entry");
      const logCheckboxes = document.querySelectorAll(".js-log-checkbox");
      const selectAllDayCheckboxes = document.querySelectorAll(".js-select-all-day");
      const copySelectedBtn = document.querySelector(".js-copy-selected");
      const bulkActions = document.getElementById("bulk-actions");
      const selectedCountLabel = document.getElementById("selected-count");

      const fallbackCopy = (text) => {
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.setAttribute("readonly", "readonly");
        textarea.style.position = "fixed";
        textarea.style.opacity = "0";
        document.body.appendChild(textarea);
        textarea.select();
        const isCopied = document.execCommand("copy");
        document.body.removeChild(textarea);
        return isCopied;
      };

      const copyToClipboard = async (text) => {
        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
          } else {
            return fallbackCopy(text);
          }
        } catch (error) {
          return fallbackCopy(text);
        }
      };

      const updateBulkUI = () => {
        const checked = document.querySelectorAll(".js-log-checkbox:checked");
        if (checked.length > 0) {
          bulkActions.style.display = "flex";
          selectedCountLabel.textContent = `เลือกแล้ว ${checked.length} รายการ`;
        } else {
          bulkActions.style.display = "none";
        }
      };

      logCheckboxes.forEach(cb => {
        cb.addEventListener("change", updateBulkUI);
      });

      selectAllDayCheckboxes.forEach(selectAll => {
        selectAll.addEventListener("change", (e) => {
          const dayGroup = selectAll.closest(".log-day-group");
          const dayCheckboxes = dayGroup.querySelectorAll(".js-log-checkbox");
          dayCheckboxes.forEach(cb => {
            cb.checked = e.target.checked;
          });
          updateBulkUI();
        });
      });

      copySelectedBtn.addEventListener("click", async () => {
        const checked = document.querySelectorAll(".js-log-checkbox:checked");
        const texts = Array.from(checked).map(cb => cb.dataset.copyText);
        const combinedText = texts.join("\n\n" + "=".repeat(50) + "\n\n");
        
        if (combinedText === "") return;

        const originalText = copySelectedBtn.textContent;
        const success = await copyToClipboard(combinedText);
        
        copySelectedBtn.textContent = success ? "Copied All!" : "Error!";
        setTimeout(() => {
          copySelectedBtn.textContent = originalText;
        }, 2000);
      });

      copyButtons.forEach((button) => {
        button.addEventListener("click", async () => {
          const originalLabel = button.dataset.originalLabel || button.textContent.trim();
          const text = button.dataset.copyText || "";

          if (text === "") {
            button.textContent = "ไม่มีข้อมูล";
            setTimeout(() => {
              button.textContent = originalLabel;
            }, 1400);
            return;
          }

          button.dataset.originalLabel = originalLabel;
          const isCopied = await copyToClipboard(text);
          button.textContent = isCopied ? "Copied" : "คัดลอกไม่ได้";

          setTimeout(() => {
            button.textContent = originalLabel;
          }, 1400);
        });
      });
    })();
  </script>
@endpush
