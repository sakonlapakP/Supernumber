/**
 * Planning Interface — Calendar, Timeline & Table View Controller
 * Handles view switching, AJAX data loading, and status updates.
 */
class PlanningInterface {
  constructor(el) {
    this.el = el;
    this.csrfToken = el.dataset.csrf || '';
    this.apiBase = el.dataset.apiBase || '/admin/articles/api/plans';
    this.isManager = el.dataset.manager === '1';
    this.currentView = localStorage.getItem('planning-view') || 'calendar';
    this.today = this.todayStr();

    // Calendar state
    const now = new Date();
    this.calYear = now.getFullYear();
    this.calMonth = now.getMonth() + 1;

    // Timeline state — start from current week
    this.tlYear = now.getFullYear();
    this.tlWeek = this.getISOWeek(now);

    // Cached data
    this.calPlans = {}; // keyed by "YYYY-MM"
    this.tlPlans = {};  // keyed by "YYYY-WW"

    this.activeDropdown = null;
    this.init();
  }

  // ─── Bootstrap ────────────────────────────────────────────────────────────

  init() {
    this.bindViewToggle();
    this.bindCalNavigation();
    this.bindTimelineNavigation();
    this.bindStatusDropdowns();
    this.bindModalClose();
    this.bindKeyboard();
    this.bindTableFilter();
    this.switchView(this.currentView, false);
  }

  // ─── View Switching ────────────────────────────────────────────────────────

  switchView(view, save = true) {
    const valid = ['calendar', 'timeline', 'table'];
    if (!valid.includes(view)) view = 'calendar';
    this.currentView = view;
    if (save) localStorage.setItem('planning-view', view);

    // Update tab active states
    this.el.querySelectorAll('.view-tab').forEach(tab => {
      tab.classList.toggle('active', tab.dataset.view === view);
    });

    // Show/hide panels
    this.el.querySelectorAll('.planning-view').forEach(panel => {
      panel.hidden = panel.dataset.view !== view;
    });

    // Load data for the newly active view
    if (view === 'calendar') this.loadCalendar();
    else if (view === 'timeline') this.loadTimeline();
  }

  bindViewToggle() {
    this.el.querySelectorAll('.view-tab').forEach(tab => {
      tab.addEventListener('click', () => this.switchView(tab.dataset.view));
    });
  }

  // ─── Calendar ─────────────────────────────────────────────────────────────

  bindCalNavigation() {
    this.el.querySelector('[data-cal-prev]')?.addEventListener('click', () => {
      this.calMonth--;
      if (this.calMonth < 1) { this.calMonth = 12; this.calYear--; }
      this.loadCalendar();
    });

    this.el.querySelector('[data-cal-next]')?.addEventListener('click', () => {
      this.calMonth++;
      if (this.calMonth > 12) { this.calMonth = 1; this.calYear++; }
      this.loadCalendar();
    });

    this.el.querySelector('[data-cal-today]')?.addEventListener('click', () => {
      const now = new Date();
      this.calYear = now.getFullYear();
      this.calMonth = now.getMonth() + 1;
      this.loadCalendar();
    });
  }

  async loadCalendar() {
    const months = [this.prevMonth(), { year: this.calYear, month: this.calMonth }, this.nextMonth()];
    this.updateCalNav();
    this.setCalLoading(true);

    try {
      const results = await Promise.all(months.map(m => this.fetchMonth(m.year, m.month)));
      this.renderCalendar(months, results);
      this.updateCalSummary(results[1]); // summary for current month
    } catch (e) {
      console.error('Calendar load error:', e);
    } finally {
      this.setCalLoading(false);
    }
  }

  async fetchMonth(year, month) {
    const key = `${year}-${month}`;
    if (this.calPlans[key]) return this.calPlans[key];
    try {
      const res = await fetch(`${this.apiBase}/month/${year}/${month}`, { credentials: 'same-origin' });
      const json = await res.json();
      this.calPlans[key] = json.data || [];
      return this.calPlans[key];
    } catch {
      return [];
    }
  }

  setCalLoading(on) {
    const spinner = this.el.querySelector('#cal-loading');
    const grid = this.el.querySelector('#cal-grid');
    if (spinner) spinner.hidden = !on;
    if (grid) grid.hidden = on;
  }

  updateCalNav() {
    const label = this.el.querySelector('#cal-nav-label');
    if (label) {
      label.textContent = `${this.thaiMonth(this.calMonth)} ${this.calYear + 543 - 2500 + 2500}`;
      // Display as "พฤษภาคม 2569"
      label.textContent = `${this.thaiMonth(this.calMonth)} ${this.calYear + 543}`;
    }
  }

  updateCalSummary(plans) {
    const counts = this.countStatuses(plans);
    const summaryEl = this.el.querySelector('#cal-summary');
    if (!summaryEl) return;

    const parts = [];
    if (counts.done)       parts.push(`<span class="cal-summary-badge cal-summary-badge--done">✅ ${counts.done} เสร็จ</span>`);
    if (counts.in_progress) parts.push(`<span class="cal-summary-badge cal-summary-badge--progress">📝 ${counts.in_progress} กำลังทำ</span>`);
    if (counts.todo)       parts.push(`<span class="cal-summary-badge cal-summary-badge--todo">⭕ ${counts.todo} วางแผน</span>`);
    if (counts.blocked)    parts.push(`<span class="cal-summary-badge cal-summary-badge--blocked">🔴 ${counts.blocked} ติดขัด</span>`);
    if (counts.overdue)    parts.push(`<span class="cal-summary-badge cal-summary-badge--overdue">⚠️ ${counts.overdue} เกินกำหนด</span>`);
    if (parts.length === 0) parts.push('<span class="cal-summary-badge cal-summary-badge--todo">ไม่มีแผนเดือนนี้</span>');

    summaryEl.innerHTML = parts.join('');
  }

  renderCalendar(months, plansPerMonth) {
    const grid = this.el.querySelector('#cal-grid');
    if (!grid) return;

    const labels = ['--prev', '', '--next'];
    grid.innerHTML = months.map((m, i) =>
      this.renderMonthGrid(m.year, m.month, plansPerMonth[i], labels[i])
    ).join('');

    // Bind day-click handlers
    grid.querySelectorAll('.cal-day--has-plans').forEach(cell => {
      cell.addEventListener('click', () => this.openDayModal(cell.dataset.date, cell.dataset.plans));
    });
  }

  renderMonthGrid(year, month, plans, headerClass = '') {
    const plansByDate = this.groupByDate(plans);
    const firstDow = (new Date(year, month - 1, 1).getDay() + 6) % 7; // Mon=0
    const daysInMonth = new Date(year, month, 0).getDate();

    const weekdays = ['จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส', 'อา'];

    let cells = '';
    for (let i = 0; i < firstDow; i++) {
      cells += '<div class="cal-day cal-day--empty"></div>';
    }
    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const dayPlans = plansByDate[dateStr] || [];
      const isToday = dateStr === this.today;
      const hasPlans = dayPlans.length > 0;

      cells += `
        <div class="cal-day${isToday ? ' cal-day--today' : ''}${hasPlans ? ' cal-day--has-plans' : ''}"
             ${hasPlans ? `data-date="${dateStr}" data-plans="${encodeURIComponent(JSON.stringify(dayPlans))}"` : ''}>
          <div class="cal-day-num">${d}</div>
          ${hasPlans ? `<div class="cal-day-dots">${this.renderDots(dayPlans)}</div>` : ''}
        </div>`;
    }

    return `
      <div class="cal-month">
        <div class="cal-month-header${headerClass ? ' cal-month-header' + headerClass : ''}">
          ${this.thaiMonth(month)} ${year + 543}
        </div>
        <div class="cal-weekdays">${weekdays.map(d => `<div class="cal-wd">${d}</div>`).join('')}</div>
        <div class="cal-days">${cells}</div>
      </div>`;
  }

  renderDots(plans) {
    const visible = plans.slice(0, 3);
    const extra = plans.length - 3;
    let html = visible.map(p => {
      const s = this.effectiveStatus(p);
      return `<span class="cal-dot cal-dot--${s}" title="${this.escHtml(p.topic)}"></span>`;
    }).join('');
    if (extra > 0) html += `<span class="cal-dot-more">+${extra}</span>`;
    return html;
  }

  // ─── Timeline ─────────────────────────────────────────────────────────────

  bindTimelineNavigation() {
    this.el.querySelector('[data-tl-prev]')?.addEventListener('click', () => {
      this.tlWeek--;
      if (this.tlWeek < 1) { this.tlYear--; this.tlWeek = this.weeksInYear(this.tlYear); }
      this.loadTimeline();
    });

    this.el.querySelector('[data-tl-next]')?.addEventListener('click', () => {
      this.tlWeek++;
      if (this.tlWeek > this.weeksInYear(this.tlYear)) { this.tlWeek = 1; this.tlYear++; }
      this.loadTimeline();
    });

    this.el.querySelector('[data-tl-today]')?.addEventListener('click', () => {
      const now = new Date();
      this.tlYear = now.getFullYear();
      this.tlWeek = this.getISOWeek(now);
      this.loadTimeline();
    });
  }

  async loadTimeline() {
    this.updateTlNav();

    // Build 8 weeks starting from tlYear/tlWeek
    const weeks = [];
    let y = this.tlYear, w = this.tlWeek;
    for (let i = 0; i < 8; i++) {
      weeks.push({ year: y, week: w, startDate: this.isoWeekStart(y, w) });
      w++;
      if (w > this.weeksInYear(y)) { w = 1; y++; }
    }

    try {
      const results = await Promise.all(weeks.map(wk => this.fetchWeek(wk.year, wk.week)));
      this.renderTimeline(weeks, results);
    } catch (e) {
      console.error('Timeline load error:', e);
    }
  }

  async fetchWeek(year, week) {
    const key = `${year}-W${week}`;
    if (this.tlPlans[key]) return this.tlPlans[key];
    try {
      const res = await fetch(`${this.apiBase}/week/${year}/${week}`, { credentials: 'same-origin' });
      const json = await res.json();
      this.tlPlans[key] = json.data || [];
      return this.tlPlans[key];
    } catch {
      return [];
    }
  }

  updateTlNav() {
    const label = this.el.querySelector('#tl-nav-label');
    if (!label) return;
    const start = this.isoWeekStart(this.tlYear, this.tlWeek);
    const end = new Date(start);
    end.setDate(end.getDate() + 7);
    label.textContent = `สัปดาห์ที่ ${this.tlWeek} / ${this.tlYear + 543} — ${this.formatDateShort(start)} ถึง ${this.formatDateShort(end)}`;
  }

  renderTimeline(weeks, plansPerWeek) {
    const grid = this.el.querySelector('#timeline-grid');
    if (!grid) return;

    const nowWeek = this.getISOWeek(new Date());
    const nowYear = new Date().getFullYear();

    grid.innerHTML = weeks.map((wk, i) => {
      const plans = plansPerWeek[i];
      const counts = this.countStatuses(plans);
      const isCurrent = wk.year === nowYear && wk.week === nowWeek;
      const start = wk.startDate;
      const end = new Date(start);
      end.setDate(end.getDate() + 6);

      const badges = [
        counts.done       ? `<span class="tl-badge tl-badge--done">✅ ${counts.done}</span>` : '',
        counts.in_progress ? `<span class="tl-badge tl-badge--progress">📝 ${counts.in_progress}</span>` : '',
        counts.todo       ? `<span class="tl-badge tl-badge--todo">⭕ ${counts.todo}</span>` : '',
        counts.blocked    ? `<span class="tl-badge tl-badge--blocked">🔴 ${counts.blocked}</span>` : '',
        counts.overdue    ? `<span class="tl-badge tl-badge--overdue">⚠️ ${counts.overdue}</span>` : '',
      ].filter(Boolean).join('');

      return `
        <div class="timeline-week${isCurrent ? ' timeline-week--current' : ''}"
             data-week="${wk.week}" data-year="${wk.year}"
             data-plans="${encodeURIComponent(JSON.stringify(plans))}">
          <div class="timeline-week-header">
            <div class="timeline-week-range">${this.formatDateShort(start)} – ${this.formatDateShort(end)}</div>
            <div class="timeline-week-count">${plans.length} แผน</div>
          </div>
          <div class="timeline-week-body">${badges || '<span style="color:#cbd5e1;font-size:12px;">ว่าง</span>'}</div>
        </div>`;
    }).join('');

    // Workload chart
    this.renderWorkloadChart(weeks, plansPerWeek);

    // Click handlers
    grid.querySelectorAll('.timeline-week[data-plans]').forEach(cell => {
      cell.addEventListener('click', () => {
        const plans = JSON.parse(decodeURIComponent(cell.dataset.plans));
        if (plans.length) this.openWeekModal(cell.dataset.week, cell.dataset.year, plans);
      });
    });
  }

  renderWorkloadChart(weeks, plansPerWeek) {
    const chart = this.el.querySelector('#tl-chart');
    if (!chart) return;
    const counts = plansPerWeek.map(p => p.length);
    const max = Math.max(...counts, 1);

    chart.innerHTML = weeks.map((wk, i) => {
      const pct = Math.round((counts[i] / max) * 100);
      const isEmpty = counts[i] === 0;
      return `
        <div class="tl-bar-col">
          <div class="tl-bar${isEmpty ? ' tl-bar--empty' : ''}" style="height:${Math.max(pct, 4)}%"></div>
          <div class="tl-bar-label">W${wk.week}</div>
        </div>`;
    }).join('');
  }

  // ─── Day / Week Modal ──────────────────────────────────────────────────────

  openDayModal(dateStr, encodedPlans) {
    const plans = JSON.parse(decodeURIComponent(encodedPlans));
    const [y, m, d] = dateStr.split('-');
    const title = `${parseInt(d, 10)} ${this.thaiMonth(parseInt(m, 10))} ${parseInt(y, 10) + 543}`;
    this.showModal(title, plans, dateStr);
  }

  openWeekModal(week, year, plans) {
    const title = `สัปดาห์ที่ ${week} ปี ${parseInt(year, 10) + 543}`;
    this.showModal(title, plans, null);
  }

  showModal(title, plans, dateStr) {
    const overlay = document.getElementById('planning-detail-overlay');
    const modalTitle = document.getElementById('planning-modal-title');
    const plansList = document.getElementById('planning-modal-plans');
    if (!overlay || !modalTitle || !plansList) return;

    modalTitle.textContent = title;
    plansList.innerHTML = plans.length
      ? plans.map(p => this.renderPlanCard(p)).join('')
      : '<p style="color:#94a3b8;text-align:center;padding:20px;font-family:Kanit,sans-serif;">ไม่มีแผนในวันนี้</p>';

    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Bind action buttons inside modal
    plansList.querySelectorAll('[data-plan-status-btn]').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        this.toggleStatusDropdown(btn, JSON.parse(decodeURIComponent(btn.dataset.plan)));
      });
    });

    if (this.isManager) {
      plansList.querySelectorAll('[data-plan-edit-btn]').forEach(btn => {
        btn.addEventListener('click', () => {
          const plan = JSON.parse(decodeURIComponent(btn.dataset.plan));
          this.closeModal();
          this.openEditPlanModal(plan);
        });
      });
    }
  }

  renderPlanCard(plan) {
    const status = this.effectiveStatus(plan);
    const statusLabel = this.statusLabel(status);
    const encoded = encodeURIComponent(JSON.stringify(plan));
    const hasArticle = plan.article && plan.article.id;

    const editBtn = this.isManager
      ? `<button class="plan-item-card__btn" data-plan-edit-btn data-plan="${encoded}" title="แก้ไขแผน">✏️</button>`
      : '';

    const articleBtn = hasArticle
      ? `<a href="/articles/${plan.article.slug}" target="_blank" class="plan-item-card__btn" title="ดูบทความ">📄</a>`
      : '';

    return `
      <div class="plan-item-card">
        <div class="plan-item-card__time">${plan.publish_time || '—'}</div>
        <div class="plan-item-card__topic">
          <div style="font-size:13px;font-weight:700;color:#1e293b;margin-bottom:3px;">${this.escHtml(plan.topic)}</div>
          <div style="font-size:11px;color:#94a3b8;">${plan.type || ''}</div>
        </div>
        <div class="plan-item-card__actions">
          <span class="plan-status-cell plan-status-cell--${status} plan-item-card__btn"
                style="width:auto;padding:3px 8px;"
                data-plan-status-btn data-plan="${encoded}"
                title="เปลี่ยนสถานะ">
            ${statusLabel}
          </span>
          ${editBtn}
          ${articleBtn}
        </div>
      </div>`;
  }

  openEditPlanModal(plan) {
    // Delegate to the existing server-side plan modal
    if (typeof window.openEditPlanModal === 'function') {
      window.openEditPlanModal(plan);
    }
  }

  bindModalClose() {
    const overlay = document.getElementById('planning-detail-overlay');
    if (!overlay) return;

    document.getElementById('planning-modal-close')?.addEventListener('click', () => this.closeModal());

    overlay.addEventListener('click', e => {
      if (e.target === overlay) this.closeModal();
    });
  }

  closeModal() {
    const overlay = document.getElementById('planning-detail-overlay');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
    this.closeActiveDropdown();
  }

  // ─── Status Dropdown ───────────────────────────────────────────────────────

  bindStatusDropdowns() {
    // Delegate to dynamically-created dropdowns
    document.addEventListener('click', e => {
      if (!e.target.closest('.status-dropdown')) {
        this.closeActiveDropdown();
      }
    });
  }

  toggleStatusDropdown(triggerEl, plan) {
    this.closeActiveDropdown();

    const statuses = [
      { value: 'todo',        label: '⭕ วางแผน',    color: '#94a3b8' },
      { value: 'in_progress', label: '📝 กำลังทำ',    color: '#f59e0b' },
      { value: 'done',        label: '✅ เสร็จแล้ว',  color: '#10b981' },
      { value: 'blocked',     label: '🔴 ติดขัด',     color: '#ef4444' },
      { value: 'cancelled',   label: '❌ ยกเลิก',     color: '#6b7280' },
    ];

    const dropdown = document.createElement('div');
    dropdown.className = 'status-dropdown';
    dropdown.innerHTML = statuses.map(s => `
      <button class="status-dropdown-item" data-status="${s.value}" ${s.value === plan.status ? 'style="font-weight:800;"' : ''}>
        <span class="dot" style="background:${s.color}"></span>
        ${s.label}
      </button>`).join('');

    dropdown.querySelectorAll('.status-dropdown-item').forEach(item => {
      item.addEventListener('click', async () => {
        this.closeActiveDropdown();
        await this.updateStatus(plan.id, item.dataset.status);
      });
    });

    // Position relative to trigger
    const rect = triggerEl.getBoundingClientRect();
    dropdown.style.position = 'fixed';
    dropdown.style.top = `${rect.bottom + 4}px`;
    dropdown.style.left = `${rect.left}px`;

    document.body.appendChild(dropdown);
    this.activeDropdown = dropdown;
  }

  closeActiveDropdown() {
    if (this.activeDropdown) {
      this.activeDropdown.remove();
      this.activeDropdown = null;
    }
  }

  async updateStatus(planId, newStatus) {
    if (!this.isManager) {
      this.showToast('เฉพาะ Manager เท่านั้นที่เปลี่ยนสถานะได้', 'error');
      return;
    }
    try {
      const res = await fetch(`${this.apiBase}/${planId}/status`, {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify({ status: newStatus }),
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `HTTP ${res.status}`);
      }

      // Bust cache so next load fetches fresh data
      this.calPlans = {};
      this.tlPlans = {};

      this.showToast('อัปเดตสถานะเรียบร้อยแล้ว', 'success');
      this.closeModal();
      this.loadCurrentView();
    } catch (e) {
      console.error('Status update failed:', e);
      this.showToast('เกิดข้อผิดพลาด: ' + e.message, 'error');
    }
  }

  loadCurrentView() {
    if (this.currentView === 'calendar') this.loadCalendar();
    else if (this.currentView === 'timeline') this.loadTimeline();
  }

  // ─── Table View Filter ─────────────────────────────────────────────────────

  bindTableFilter() {
    const statusFilter = this.el.querySelector('#table-status-filter');
    const typeFilter = this.el.querySelector('#table-type-filter');

    const applyTableFilter = () => {
      const selectedStatus = statusFilter?.value || '';
      const selectedType = typeFilter?.value || '';

      this.el.querySelectorAll('.plan-item-row').forEach(row => {
        const rowStatus = row.dataset.status || '';
        const rowType = row.dataset.type || '';
        const statusMatch = !selectedStatus || rowStatus === selectedStatus;
        const typeMatch = !selectedType || rowType === selectedType;
        row.style.display = statusMatch && typeMatch ? '' : 'none';
      });

      // Show/hide month dividers based on visible rows
      this.el.querySelectorAll('.month-divider').forEach(divider => {
        const month = divider.dataset.monthDivider;
        const hasVisible = [...this.el.querySelectorAll(`.plan-item-row[data-month="${month}"]`)]
          .some(row => row.style.display !== 'none');
        divider.style.display = hasVisible ? '' : 'none';
      });
    };

    statusFilter?.addEventListener('change', applyTableFilter);
    typeFilter?.addEventListener('change', applyTableFilter);
  }

  // ─── Keyboard Shortcuts ────────────────────────────────────────────────────

  bindKeyboard() {
    document.addEventListener('keydown', e => {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

      const modal = document.getElementById('planning-detail-overlay');
      const modalOpen = modal?.classList.contains('open');

      if (e.key === 'Escape') {
        if (this.activeDropdown) { this.closeActiveDropdown(); return; }
        if (modalOpen) { this.closeModal(); return; }
      }

      if (modalOpen) return;

      if (e.key === 'ArrowLeft' && !e.shiftKey) {
        if (this.currentView === 'calendar') {
          this.el.querySelector('[data-cal-prev]')?.click();
        } else if (this.currentView === 'timeline') {
          this.el.querySelector('[data-tl-prev]')?.click();
        }
      }
      if (e.key === 'ArrowRight' && !e.shiftKey) {
        if (this.currentView === 'calendar') {
          this.el.querySelector('[data-cal-next]')?.click();
        } else if (this.currentView === 'timeline') {
          this.el.querySelector('[data-tl-next]')?.click();
        }
      }
    });
  }

  // ─── Toast ─────────────────────────────────────────────────────────────────

  showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `planning-toast planning-toast--${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add('visible'));
    });
    setTimeout(() => {
      toast.classList.remove('visible');
      setTimeout(() => toast.remove(), 350);
    }, 3000);
  }

  // ─── Utilities ─────────────────────────────────────────────────────────────

  groupByDate(plans) {
    const map = {};
    plans.forEach(p => {
      const date = p.publish_date;
      if (!map[date]) map[date] = [];
      map[date].push(p);
    });
    return map;
  }

  countStatuses(plans) {
    const counts = {};
    plans.forEach(p => {
      const s = this.effectiveStatus(p);
      counts[s] = (counts[s] || 0) + 1;
    });
    return counts;
  }

  effectiveStatus(plan) {
    if (plan.publish_date < this.today &&
        plan.status !== 'done' && plan.status !== 'cancelled') {
      return 'overdue';
    }
    return plan.status || 'todo';
  }

  statusLabel(status) {
    return {
      todo: '⭕ วางแผน',
      in_progress: '📝 กำลังทำ',
      done: '✅ เสร็จ',
      blocked: '🔴 ติดขัด',
      cancelled: '❌ ยกเลิก',
      overdue: '⚠️ เลยกำหนด',
    }[status] || status;
  }

  prevMonth() {
    let m = this.calMonth - 1, y = this.calYear;
    if (m < 1) { m = 12; y--; }
    return { year: y, month: m };
  }

  nextMonth() {
    let m = this.calMonth + 1, y = this.calYear;
    if (m > 12) { m = 1; y++; }
    return { year: y, month: m };
  }

  getISOWeek(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const day = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - day);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
  }

  weeksInYear(year) {
    const d = new Date(year, 11, 31);
    return this.getISOWeek(d) === 1 ? this.getISOWeek(new Date(year, 11, 24)) : this.getISOWeek(d);
  }

  isoWeekStart(year, week) {
    const simple = new Date(year, 0, 1 + (week - 1) * 7);
    const dow = simple.getDay();
    const monday = simple;
    if (dow <= 4) {
      monday.setDate(simple.getDate() - simple.getDay() + 1);
    } else {
      monday.setDate(simple.getDate() + 8 - simple.getDay());
    }
    return monday;
  }

  todayStr() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
  }

  thaiMonth(m) {
    return ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
            'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'][m];
  }

  thaiMonthShort(m) {
    return ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.',
            'ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'][m];
  }

  formatDateShort(date) {
    return `${date.getDate()} ${this.thaiMonthShort(date.getMonth()+1)}`;
  }

  escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
}

// ─── Auto-init ────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const el = document.querySelector('[data-planning-interface]');
  if (el) window.planningInterface = new PlanningInterface(el);
});
