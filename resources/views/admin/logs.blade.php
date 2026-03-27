@extends('layouts.admin')

@section('title', 'Supernumber Admin | Application Logs')

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
      <h1>Application Logs</h1>
      <p class="admin-subtitle">แสดง log จากระบบ, filter ตามไฟล์/ระดับ/วันที่/คำค้น และล้างไฟล์ได้จากหน้าเดียวสำหรับผู้ใช้ระดับ manager</p>
    </div>
    <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
      <a href="{{ route('admin.logs', array_filter($filters, fn ($value) => $value !== '')) }}" class="admin-button admin-button--muted admin-button--compact">Refresh</a>
      <a href="{{ route('admin.logs') }}" class="admin-button admin-button--compact">Reset</a>
      <form action="{{ route('admin.logs.clear') }}" method="post" style="margin: 0;" onsubmit="return confirm('ล้างไฟล์ log นี้ใช่หรือไม่?');">
        @csrf
        <input type="hidden" name="file" value="{{ $filters['file'] }}" />
        <button type="submit" class="admin-button admin-button--secondary admin-button--compact">Clear Log</button>
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
        <label for="log_file">Log File</label>
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
        <label for="log_level">Level</label>
        <select id="log_level" class="admin-input" name="level">
          <option value="">ทั้งหมด</option>
          @foreach ($availableLevels as $level)
            <option value="{{ $level }}" @selected($filters['level'] === $level)>{{ $level }}</option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="log_date">Date</label>
        <input id="log_date" class="admin-input" type="date" name="date" value="{{ $filters['date'] }}" list="log-date-options" />
        <datalist id="log-date-options">
          @foreach ($availableDates as $date)
            <option value="{{ $date }}"></option>
          @endforeach
        </datalist>
      </div>

      <div class="admin-field">
        <label for="log_search">Search</label>
        <input id="log_search" class="admin-input" type="text" name="search" value="{{ $filters['search'] }}" placeholder="ค้นคำจาก message หรือ stack trace" />
      </div>

      <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
        <button type="submit" class="admin-button admin-button--compact">Apply Filters</button>
      </div>
    </form>
  </section>

  <section class="admin-card admin-feature-card">
    <div style="display: grid; gap: 8px;">
      <div><strong>ไฟล์:</strong> {{ $logPath }}</div>
      <div><strong>ขนาดไฟล์:</strong> {{ number_format($logSize) }} bytes</div>
      <div><strong>ข้อมูลที่อ่าน:</strong> {{ number_format($displayedByteCount) }} bytes ล่าสุด</div>
      <div><strong>Entries:</strong> {{ number_format($displayedEntryCount) }} / {{ number_format($totalEntryCount) }} entries</div>
      <div><strong>Per Page:</strong> 5 entries</div>
    </div>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 18px;">
    <div style="padding: 18px 20px 0;">
      <h2 style="margin: 0; font-size: 1.1rem;">Log Output</h2>
      <p class="admin-subtitle" style="margin-top: 6px;">หน้านี้อ่านเฉพาะท้ายไฟล์เพื่อให้เปิดได้เร็วขึ้น แม้ log จะมีขนาดใหญ่ และกรองผลบน entries ล่าสุดที่อ่านมา</p>
    </div>

    <div style="padding: 20px;">
      @if (! $logExists)
        <div class="admin-muted">ยังไม่พบไฟล์ log หรือไฟล์นี้อ่านไม่ได้</div>
      @elseif ($totalEntryCount === 0)
        <div class="admin-muted">ไฟล์ log ว่างอยู่ในตอนนี้</div>
      @elseif ($displayedEntryCount === 0)
        <div class="admin-muted">ไม่พบรายการที่ตรงกับ filter ที่เลือก</div>
      @else
        <div style="display: grid; gap: 14px;">
          @foreach ($entries as $entry)
            @php
              $entryLevel = strtoupper((string) ($entry['level'] ?? ''));
              $levelStyle = $levelStyles[$entryLevel] ?? ['bg' => '#f8f9fc', 'border' => '#d0d5dd', 'text' => '#344054'];
            @endphp

            <article style="border: 1px solid #dbe4f0; border-radius: 18px; background: #fff; overflow: hidden;">
              <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 14px 16px; border-bottom: 1px solid #e7edf5; background: #f8fbff;">
                <span style="display: inline-flex; align-items: center; min-height: 30px; padding: 4px 10px; border-radius: 999px; background: {{ $levelStyle['bg'] }}; border: 1px solid {{ $levelStyle['border'] }}; color: {{ $levelStyle['text'] }}; font-size: 0.8rem; font-weight: 700;">
                  {{ $entryLevel !== '' ? $entryLevel : 'RAW' }}
                </span>
                <span style="font-size: 0.92rem; color: #475467;">{{ $entry['timestamp'] ?: '-' }}</span>
                @if ($entry['environment'])
                  <span class="admin-muted" style="font-size: 0.86rem;">env: {{ $entry['environment'] }}</span>
                @endif
              </div>
              <div style="padding: 16px;">
                <div style="font-weight: 700; color: #101828; margin-bottom: 10px; white-space: pre-wrap;">{{ $entry['message'] !== '' ? $entry['message'] : '(no message)' }}</div>
                <pre style="margin: 0; white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere; background: #0f172a; color: #e2e8f0; border-radius: 14px; padding: 16px; font-size: 0.84rem; line-height: 1.6; max-height: 45vh; overflow: auto;">{{ $entry['raw_display'] ?? $entry['raw'] }}</pre>
              </div>
            </article>
          @endforeach
        </div>

        @if ($entries->hasPages())
          <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการ application logs" style="margin-top: 18px;">
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
