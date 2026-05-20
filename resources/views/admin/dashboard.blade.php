@extends('layouts.admin')

@section('title', 'Supernumber Admin | Dashboard')

@section('content')
  @php
    $formatNumber = static fn ($value): string => number_format((int) $value);
    $formatDateTime = static function ($value): string {
        if (! $value) return '-';
        try {
            return $value->timezone('Asia/Bangkok')->format('d/m/Y H:i');
        } catch (\Throwable) {
            return '-';
        }
    };

    $workItems = [
      [
        'label' => 'คำสั่งซื้อกำลังดำเนินการ',
        'value' => $stats['orders_processing'] ?? 0,
        'url' => route('admin.orders'),
        'tone' => 'blue',
      ],
      [
        'label' => 'คอมเมนต์รอตรวจ',
        'value' => $stats['comments_pending'] ?? 0,
        'url' => route('admin.comments'),
        'tone' => 'amber',
      ],
      [
        'label' => 'เบอร์ที่พักไว้',
        'value' => $stats['numbers_hold'] ?? 0,
        'url' => route('admin.hold-numbers'),
        'tone' => 'slate',
      ],
      [
        'label' => 'ลูกค้าเลือกเบอร์อัตโนมัติ',
        'value' => $stats['estimate_leads_total'] ?? 0,
        'url' => route('admin.estimate-leads'),
        'tone' => 'green',
      ],
    ];

    $shortcuts = [
      ['label' => 'ดูเบอร์ทั้งหมด', 'url' => route('admin.numbers'), 'hint' => 'ค้นหา แก้สถานะ และจัดการเบอร์'],
      ['label' => 'คำสั่งซื้อ', 'url' => route('admin.orders'), 'hint' => 'ติดตาม order ที่ต้องดำเนินการ'],
      ['label' => 'บทความ', 'url' => route('admin.articles'), 'hint' => 'จัดการแผนและเนื้อหา SEO'],
      ['label' => 'ข้อความติดต่อ', 'url' => route('admin.contact-messages'), 'hint' => 'อ่าน lead จาก Contact Us'],
    ];
  @endphp

  <style>
    .admin-dashboard {
      display: grid;
      gap: 18px;
    }

    .admin-dashboard-hero {
      padding: 18px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 18px;
      align-items: center;
    }

    .admin-dashboard-hero__eyebrow {
      margin: 0 0 6px;
      color: #2563eb;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .admin-dashboard-hero h1 {
      margin: 0;
      font-size: clamp(26px, 3vw, 34px);
      line-height: 1.15;
    }

    .admin-dashboard-hero p {
      margin: 8px 0 0;
      color: var(--admin-muted);
      font-size: 14px;
      line-height: 1.65;
      font-weight: 600;
    }

    .admin-dashboard-hero__meta {
      display: grid;
      gap: 8px;
      min-width: 220px;
    }

    .admin-dashboard-metric {
      padding: 12px 14px;
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius-lg);
      background: #f8fafc;
    }

    .admin-dashboard-metric__label {
      color: var(--admin-muted);
      font-size: 12px;
      font-weight: 700;
    }

    .admin-dashboard-metric__value {
      margin-top: 4px;
      color: var(--admin-text);
      font-size: 24px;
      font-weight: 800;
      line-height: 1;
    }

    .admin-work-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }

    .admin-work-card {
      padding: 16px;
      display: grid;
      gap: 12px;
      text-decoration: none;
      transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }

    .admin-work-card:hover {
      transform: translateY(-1px);
      border-color: #bfdbfe;
      box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
    }

    .admin-work-card__top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .admin-work-card__label {
      color: #475569;
      font-size: 13px;
      font-weight: 800;
      line-height: 1.4;
    }

    .admin-work-card__value {
      color: var(--admin-text);
      font-size: 34px;
      font-weight: 800;
      line-height: 1;
    }

    .admin-work-card__pill {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
    }

    .admin-work-card--blue .admin-work-card__pill { color: #1d4ed8; background: #eff6ff; }
    .admin-work-card--amber .admin-work-card__pill { color: #b45309; background: #fffbeb; }
    .admin-work-card--slate .admin-work-card__pill { color: #475569; background: #f1f5f9; }
    .admin-work-card--green .admin-work-card__pill { color: #047857; background: #ecfdf5; }

    .admin-dashboard-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr);
      gap: 18px;
    }

    .admin-dashboard-section {
      padding: 16px;
    }

    .admin-dashboard-section__head {
      margin-bottom: 12px;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
    }

    .admin-dashboard-section__title {
      margin: 0;
      font-size: 16px;
      font-weight: 800;
    }

    .admin-dashboard-section__hint {
      margin: 4px 0 0;
      color: var(--admin-muted);
      font-size: 12px;
      line-height: 1.5;
      font-weight: 600;
    }

    .admin-activity-list {
      display: grid;
      gap: 8px;
    }

    .admin-activity-item {
      padding: 12px;
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius-lg);
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 12px;
      align-items: center;
      background: #fff;
    }

    .admin-activity-item__title {
      color: var(--admin-text);
      font-size: 14px;
      font-weight: 800;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }

    .admin-activity-item__meta {
      margin-top: 4px;
      color: var(--admin-muted);
      font-size: 12px;
      line-height: 1.45;
      font-weight: 600;
    }

    .admin-shortcut-list {
      display: grid;
      gap: 8px;
    }

    .admin-shortcut {
      padding: 12px;
      border: 1px solid var(--admin-border);
      border-radius: var(--admin-radius-lg);
      display: grid;
      gap: 4px;
      text-decoration: none;
      background: #fff;
    }

    .admin-shortcut:hover {
      border-color: #bfdbfe;
      background: #f8fafc;
    }

    .admin-shortcut strong {
      font-size: 14px;
      line-height: 1.35;
    }

    .admin-shortcut span {
      color: var(--admin-muted);
      font-size: 12px;
      line-height: 1.5;
      font-weight: 600;
    }

    .admin-empty-state {
      padding: 18px;
      border: 1px dashed var(--admin-border-strong);
      border-radius: var(--admin-radius-lg);
      color: var(--admin-muted);
      background: #f8fafc;
      font-size: 14px;
      font-weight: 700;
      text-align: center;
    }

    @media (max-width: 1100px) {
      .admin-work-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .admin-dashboard-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 720px) {
      .admin-dashboard-hero,
      .admin-activity-item {
        grid-template-columns: 1fr;
      }

      .admin-dashboard-hero__meta {
        min-width: 0;
      }

      .admin-work-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <div class="admin-dashboard">
    <section class="admin-card admin-dashboard-hero">
      <div>
        <p class="admin-dashboard-hero__eyebrow">Admin workspace</p>
        <h1>ภาพรวมงานวันนี้</h1>
        <p>จุดเริ่มงานสำหรับตรวจ order, lead, comment และสถานะเบอร์ที่ต้องจัดการต่อ</p>
      </div>
      <div class="admin-dashboard-hero__meta" aria-label="ภาพรวมเบอร์ในระบบ">
        <div class="admin-dashboard-metric">
          <div class="admin-dashboard-metric__label">เบอร์ทั้งหมด</div>
          <div class="admin-dashboard-metric__value">{{ $formatNumber($stats['numbers_total'] ?? 0) }}</div>
        </div>
        <div class="admin-dashboard-metric">
          <div class="admin-dashboard-metric__label">พร้อมขาย</div>
          <div class="admin-dashboard-metric__value">{{ $formatNumber($stats['numbers_active'] ?? 0) }}</div>
        </div>
      </div>
    </section>

    <section class="admin-work-grid" aria-label="งานที่ต้องติดตาม">
      @foreach ($workItems as $item)
        <a href="{{ $item['url'] }}" class="admin-card admin-work-card admin-work-card--{{ $item['tone'] }}">
          <div class="admin-work-card__top">
            <div class="admin-work-card__label">{{ $item['label'] }}</div>
            <span class="admin-work-card__pill" aria-hidden="true">›</span>
          </div>
          <div class="admin-work-card__value">{{ $formatNumber($item['value']) }}</div>
        </a>
      @endforeach
    </section>

    <div class="admin-dashboard-grid">
      <section class="admin-card admin-dashboard-section">
        <div class="admin-dashboard-section__head">
          <div>
            <h2 class="admin-dashboard-section__title">คำสั่งซื้อล่าสุด</h2>
            <p class="admin-dashboard-section__hint">รายการล่าสุดจากหน้าซื้อเบอร์</p>
          </div>
          <a href="{{ route('admin.orders') }}" class="admin-button admin-button--muted admin-button--compact">ดูทั้งหมด</a>
        </div>

        <div class="admin-activity-list">
          @forelse ($recentOrders as $order)
            <div class="admin-activity-item">
              <div>
                <div class="admin-activity-item__title">
                  {{ $order->full_name !== '' ? $order->full_name : 'ไม่ระบุชื่อ' }}
                </div>
                <div class="admin-activity-item__meta">
                  {{ $order->ordered_number ?: '-' }} · {{ $order->service_type_label }} · {{ $formatDateTime($order->created_at) }}
                </div>
              </div>
              <span class="admin-status-pill {{ $order->status === \App\Models\CustomerOrder::STATUS_COMPLETED ? 'admin-status-pill--active' : 'admin-status-pill--hold' }}">
                {{ $order->status_label }}
              </span>
            </div>
          @empty
            <div class="admin-empty-state">ยังไม่มีคำสั่งซื้อ</div>
          @endforelse
        </div>
      </section>

      <aside class="admin-card admin-dashboard-section">
        <div class="admin-dashboard-section__head">
          <div>
            <h2 class="admin-dashboard-section__title">ทางลัด</h2>
            <p class="admin-dashboard-section__hint">งานที่ใช้บ่อยใน admin</p>
          </div>
        </div>
        <div class="admin-shortcut-list">
          @foreach ($shortcuts as $shortcut)
            <a class="admin-shortcut" href="{{ $shortcut['url'] }}">
              <strong>{{ $shortcut['label'] }}</strong>
              <span>{{ $shortcut['hint'] }}</span>
            </a>
          @endforeach
        </div>
      </aside>
    </div>

    <div class="admin-dashboard-grid">
      <section class="admin-card admin-dashboard-section">
        <div class="admin-dashboard-section__head">
          <div>
            <h2 class="admin-dashboard-section__title">Lead เลือกเบอร์ล่าสุด</h2>
            <p class="admin-dashboard-section__hint">ข้อมูลจากฟอร์มเลือกเบอร์อัตโนมัติ</p>
          </div>
          <a href="{{ route('admin.estimate-leads') }}" class="admin-button admin-button--muted admin-button--compact">ดูทั้งหมด</a>
        </div>

        <div class="admin-activity-list">
          @forelse ($recentEstimateLeads as $lead)
            <div class="admin-activity-item">
              <div>
                <div class="admin-activity-item__title">{{ $lead->full_name !== '' ? $lead->full_name : 'ไม่ระบุชื่อ' }}</div>
                <div class="admin-activity-item__meta">
                  {{ $lead->main_phone ?: $lead->current_phone ?: '-' }} · {{ $lead->goal_label }} · {{ $formatDateTime($lead->submitted_at ?? $lead->created_at) }}
                </div>
              </div>
              <a href="{{ route('admin.estimate-leads.show', $lead) }}" class="admin-button admin-button--muted admin-button--compact">เปิด</a>
            </div>
          @empty
            <div class="admin-empty-state">ยังไม่มี lead จากฟอร์มเลือกเบอร์</div>
          @endforelse
        </div>
      </section>

      <aside class="admin-card admin-dashboard-section">
        <div class="admin-dashboard-section__head">
          <div>
            <h2 class="admin-dashboard-section__title">คอมเมนต์รอตรวจ</h2>
            <p class="admin-dashboard-section__hint">ตรวจและอนุมัติเนื้อหาก่อนแสดงบนเว็บ</p>
          </div>
          <a href="{{ route('admin.comments') }}" class="admin-button admin-button--muted admin-button--compact">จัดการ</a>
        </div>
        <div class="admin-activity-list">
          @forelse ($pendingComments as $comment)
            <div class="admin-activity-item">
              <div>
                <div class="admin-activity-item__title">{{ $comment->commenter_name ?: 'ไม่ระบุชื่อ' }}</div>
                <div class="admin-activity-item__meta">
                  {{ $comment->article?->title ?: 'ไม่พบบทความ' }} · {{ $formatDateTime($comment->created_at) }}
                </div>
              </div>
            </div>
          @empty
            <div class="admin-empty-state">ไม่มีคอมเมนต์รอตรวจ</div>
          @endforelse
        </div>
      </aside>
    </div>

    <section class="admin-card admin-dashboard-section">
      <div class="admin-dashboard-section__head">
        <div>
          <h2 class="admin-dashboard-section__title">ข้อความติดต่อใหม่</h2>
          <p class="admin-dashboard-section__hint">รายการล่าสุดจาก Contact Us</p>
        </div>
        <a href="{{ route('admin.contact-messages') }}" class="admin-button admin-button--muted admin-button--compact">ดูทั้งหมด</a>
      </div>

      <div class="admin-activity-list">
        @forelse ($recentContactMessages as $message)
          <div class="admin-activity-item">
            <div>
              <div class="admin-activity-item__title">{{ $message->name ?: 'ไม่ระบุชื่อ' }}</div>
              <div class="admin-activity-item__meta">
                {{ $message->phone ?: '-' }} · {{ \Illuminate\Support\Str::limit((string) $message->message, 90) }}
              </div>
            </div>
            <span class="admin-muted">{{ $formatDateTime($message->submitted_at ?? $message->created_at) }}</span>
          </div>
        @empty
          <div class="admin-empty-state">ยังไม่มีข้อความติดต่อ</div>
        @endforelse
      </div>
    </section>
  </div>
@endsection
