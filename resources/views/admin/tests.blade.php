@extends('layouts.admin')

@section('title', 'การทดสอบระบบ - Supernumber Admin')

@section('content')
<div class="admin-page-head">
    <div>
        <h1>การทดสอบระบบ (System Testing)</h1>
        <p class="admin-subtitle">ตรวจสอบความถูกต้องของระบบทั้งหมดผ่าน Automated Tests</p>
    </div>
    <div class="admin-page-actions">
        <button type="button" class="admin-button" id="run-all-btn">
            <span class="btn-text">รันเทสต์ทั้งหมด</span>
            <span class="btn-spinner" style="display: none;">กำลังประมวลผล...</span>
        </button>
    </div>
</div>

<div class="admin-panel-stack">
    {{-- Global Progress --}}
    <div class="admin-card admin-feature-card" id="progress-card" style="display: none; margin-bottom: 24px;">
        <div class="admin-feature-card__head">
            <h2 class="admin-feature-card__title">ความคืบหน้าภาพรวม</h2>
            <div id="progress-stats" class="admin-summary">รันสำเร็จ 0 / {{ count($tests) }}</div>
        </div>
        <div style="height: 12px; background: #e3eaf5; border-radius: 6px; overflow: hidden; margin-top: 12px;">
            <div id="progress-bar" style="width: 0%; height: 100%; background: var(--admin-primary); transition: width 0.3s ease;"></div>
        </div>
    </div>

    {{-- Grouped Tests Stack --}}
    @php
        $groupedTests = collect($tests)->groupBy('category');
    @endphp

    <div class="test-categories-stack">
        @foreach($groupedTests as $category => $categoryTests)
        <div class="admin-card category-card" id="category-{{ Str::slug($category) }}">
            <div class="category-header">
                <div class="category-main-info">
                    <h3 class="category-title">{{ $category }}</h3>
                    <div class="category-badge">
                        รันสำเร็จ <span class="count-passed" id="passed-count-{{ Str::slug($category) }}">0</span>
                        จาก {{ count($categoryTests) }} เทสต์
                    </div>
                </div>
                <div class="category-status-wrap" id="category-status-{{ Str::slug($category) }}">
                    <span class="admin-status-pill status-pending">รอรัน</span>
                </div>
            </div>

            {{-- Errors list - full width --}}
            <div class="category-errors-list" id="errors-list-{{ Str::slug($category) }}" style="display: none;">
                {{-- Failing tests appended here --}}
            </div>

            {{-- Hidden references --}}
            <div style="display: none;">
                @foreach($categoryTests as $test)
                    @php
                        $globalIndex = collect($tests)->search(fn($t) => $t['filter'] === $test['filter']);
                    @endphp
                    <div class="raw-test-item" 
                         data-index="{{ $globalIndex }}" 
                         data-filter="{{ $test['filter'] }}" 
                         data-category="{{ Str::slug($category) }}"
                         data-title="{{ $test['thai_title'] }}"
                         data-method="{{ $test['method'] }}">
                    </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
    .test-categories-stack {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .category-card {
        padding: 16px 24px;
        transition: all 0.3s ease;
    }

    .category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .category-title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: var(--admin-text);
    }

    .category-badge {
        font-size: 13px;
        color: var(--admin-muted);
        margin-top: 2px;
    }

    .category-badge .count-passed {
        font-weight: 700;
        color: var(--admin-primary);
    }

    .category-errors-list {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f0f4f8;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .error-item {
        background: #fff5f3;
        border: 1px solid #f3d3cd;
        border-radius: 12px;
        padding: 16px;
    }

    .error-item-head {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }

    .error-dot {
        width: 10px;
        height: 10px;
        background: var(--admin-danger);
        border-radius: 50%;
        margin-top: 5px;
        flex-shrink: 0;
    }

    .error-title {
        font-size: 14px;
        font-weight: 700;
        color: #b91c1c;
    }

    .error-meta {
        font-size: 11px;
        color: #991b1b;
        opacity: 0.7;
        font-family: monospace;
        margin-top: 2px;
    }

    .error-details {
        background: #1e2d45;
        color: #ff9d9d;
        padding: 14px;
        border-radius: 8px;
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 12px;
        line-height: 1.6;
        white-space: pre-wrap;
        overflow-x: auto;
        border: 1px solid rgba(255,255,255,0.1);
    }

    /* Status Pills */
    .admin-status-pill.status-pending { background: #f0f4f8; color: #7488a8; border-color: #d7e1f0; }
    .admin-status-pill.status-running { background: #edf3ff; color: var(--admin-primary); border-color: #b7c8e3; animation: blink 1.5s infinite; }
    .admin-status-pill.status-passed { background: #edf9f5; color: var(--admin-success); border-color: #cbe9de; }
    .admin-status-pill.status-failed { background: #fff5f3; color: var(--admin-danger); border-color: #f3d3cd; }

    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const runAllBtn = document.getElementById('run-all-btn');
    const tests = @json($tests);
    const categoryStats = {};
    
    // Initialize category stats
    document.querySelectorAll('.category-card').forEach(card => {
        const catId = card.id.replace('category-', '');
        categoryStats[catId] = {
            passed: 0,
            total: card.querySelectorAll('.raw-test-item').length,
            failed: 0
        };
    });

    async function runTest(index) {
        const rawItem = document.querySelector(`.raw-test-item[data-index="${index}"]`);
        const filter = rawItem.dataset.filter;
        const catId = rawItem.dataset.category;
        const catStatusPill = document.querySelector(`#category-status-${catId} .admin-status-pill`);
        const catPassedCounter = document.getElementById(`passed-count-${catId}`);

        catStatusPill.className = 'admin-status-pill status-running';
        catStatusPill.textContent = 'กำลังรัน...';

        try {
            const response = await fetch('{{ route('admin.tests.run') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ filter: filter })
            });

            const data = await response.json();

            if (data.success) {
                categoryStats[catId].passed++;
                catPassedCounter.textContent = categoryStats[catId].passed;
            } else {
                categoryStats[catId].failed++;
                showErrorInList(catId, rawItem, data);
            }
        } catch (error) {
            categoryStats[catId].failed++;
            showErrorInList(catId, rawItem, { output: 'System Error: ' + error.message });
        }

        // Update Category Pill
        const totalDone = categoryStats[catId].passed + categoryStats[catId].failed;
        if (totalDone === categoryStats[catId].total) {
            if (categoryStats[catId].failed > 0) {
                catStatusPill.className = 'admin-status-pill status-failed';
                catStatusPill.textContent = 'ไม่ผ่าน';
            } else {
                catStatusPill.className = 'admin-status-pill status-passed';
                catStatusPill.textContent = 'ผ่านรวด';
            }
        }
    }

    function showErrorInList(catId, rawItem, data) {
        const errorsList = document.getElementById(`errors-list-${catId}`);
        errorsList.style.display = 'flex';
        
        const errorHtml = `
            <div class="error-item">
                <div class="error-item-head">
                    <div class="error-dot"></div>
                    <div>
                        <div class="error-title">${rawItem.dataset.title}</div>
                        <div class="error-meta">${rawItem.dataset.method}</div>
                    </div>
                </div>
                <div class="error-details">${(data.output || '') + '\n' + (data.error_output || '')}</div>
            </div>
        `;
        errorsList.insertAdjacentHTML('beforeend', errorHtml);
    }

    runAllBtn.addEventListener('click', async function() {
        if (this.disabled) return;
        
        this.disabled = true;
        this.querySelector('.btn-text').style.display = 'none';
        this.querySelector('.btn-spinner').style.display = 'inline';
        
        const progressCard = document.getElementById('progress-card');
        const progressBar = document.getElementById('progress-bar');
        const progressStats = document.getElementById('progress-stats');
        
        progressCard.style.display = 'block';
        let completed = 0;

        // Reset all
        Object.keys(categoryStats).forEach(id => {
            categoryStats[id].passed = 0;
            categoryStats[id].failed = 0;
            document.getElementById(`passed-count-${id}`).textContent = '0';
            const errList = document.getElementById(`errors-list-${id}`);
            errList.innerHTML = '';
            errList.style.display = 'none';
            const pill = document.querySelector(`#category-status-${id} .admin-status-pill`);
            pill.className = 'admin-status-pill status-pending';
            pill.textContent = 'รอรัน';
        });

        for (let i = 0; i < tests.length; i++) {
            await runTest(i);
            completed++;
            const percent = Math.round((completed / tests.length) * 100);
            progressBar.style.width = percent + '%';
            progressStats.textContent = `รันสำเร็จ ${completed} / ${tests.length}`;
        }

        this.disabled = false;
        this.querySelector('.btn-text').style.display = 'inline';
        this.querySelector('.btn-spinner').style.display = 'none';
    });
});
</script>
@endpush
