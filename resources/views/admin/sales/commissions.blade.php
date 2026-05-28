@extends('layouts.admin')
@section('title', 'Admin | Commission')

@section('content')
<div class="admin-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem;">
    <h2 style="margin:0;font-size:1.25rem;">Commission รายเดือน</h2>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
      <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
        <select name="period" class="admin-input" style="padding:.4rem .7rem;font-size:.875rem;" onchange="this.form.submit()">
          @foreach($months as $value => $label)
            <option value="{{ $value }}" @selected($value === $period)>{{ $label }}</option>
          @endforeach
        </select>
        <select name="status" class="admin-input" style="padding:.4rem .7rem;font-size:.875rem;" onchange="this.form.submit()">
          <option value="all" @selected($status === 'all')>ทุกสถานะ</option>
          <option value="pending" @selected($status === 'pending')>Pending</option>
          <option value="approved" @selected($status === 'approved')>อนุมัติ</option>
          <option value="rejected" @selected($status === 'rejected')>ไม่อนุมัติ</option>
        </select>
      </form>
    </div>
  </div>

  @if(session('success'))
    <div class="admin-alert admin-alert--success" style="margin-bottom:1rem;">{{ session('success') }}</div>
  @endif

  {{-- Totals --}}
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.25rem;">
    @foreach(['pending'=>['รอตรวจ','#fef3c7','#92400e'],'approved'=>['อนุมัติ','#d1fae5','#065f46'],'rejected'=>['ไม่อนุมัติ','#fee2e2','#991b1b']] as $s => [$label,$bg,$color])
      <div style="background:{{ $bg }};color:{{ $color }};border-radius:10px;padding:.75rem;text-align:center;">
        <div style="font-size:1.25rem;font-weight:700;">฿{{ number_format($totals[$s]->total ?? 0, 2) }}</div>
        <div style="font-size:.75rem;">{{ $label }} ({{ $totals[$s]->count ?? 0 }} รายการ)</div>
      </div>
    @endforeach
  </div>

  @if($commissions->isEmpty())
    <div style="text-align:center;padding:2rem;color:#aaa;">ไม่มีรายการ</div>
  @else
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
        <thead>
          <tr style="background:#f4f6fb;">
            <th style="padding:.6rem .8rem;text-align:left;">เซลล์</th>
            <th style="padding:.6rem .8rem;text-align:left;">เบอร์</th>
            <th style="padding:.6rem .8rem;text-align:left;">Tier</th>
            <th style="padding:.6rem .8rem;text-align:right;">ยอดสุทธิ</th>
            <th style="padding:.6rem .8rem;text-align:right;">Commission</th>
            <th style="padding:.6rem .8rem;text-align:left;">สถานะ</th>
            <th style="padding:.6rem .8rem;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($commissions as $c)
            <tr style="border-bottom:1px solid #f0f0f0;">
              <td style="padding:.55rem .8rem;">
                <div style="font-weight:500;">{{ $c->user?->name }}</div>
                <div style="font-size:.78rem;color:#aaa;">{{ $c->user?->referral_code }}</div>
              </td>
              <td style="padding:.55rem .8rem;font-family:monospace;">
                {{ $c->order?->ordered_number ?? '-' }}
              </td>
              <td style="padding:.55rem .8rem;font-size:.8rem;">{{ $c->tier_label }}</td>
              <td style="padding:.55rem .8rem;text-align:right;">
                ฿{{ number_format($c->order?->net_amount ?? 0, 2) }}
              </td>
              <td style="padding:.55rem .8rem;text-align:right;font-weight:700;">
                ฿{{ number_format($c->calculated_amount, 2) }}
              </td>
              <td style="padding:.55rem .8rem;">
                @php $statusColors = ['pending'=>'#fef3c7;color:#92400e','approved'=>'#d1fae5;color:#065f46','rejected'=>'#fee2e2;color:#991b1b']; @endphp
                <span style="padding:.2rem .5rem;border-radius:999px;font-size:.75rem;font-weight:600;background:{{ $statusColors[$c->status] ?? '#eee;color:#666' }}">
                  {{ $c->status_label }}
                </span>
              </td>
              <td style="padding:.55rem .8rem;">
                @if($c->status === 'pending')
                  <div style="display:flex;gap:.35rem;">
                    <form method="POST" action="{{ route('admin.commissions.approve', $c) }}">
                      @csrf
                      <button type="submit" class="admin-button admin-button--sm"
                        style="padding:.25rem .6rem;font-size:.75rem;background:#38a169;border-color:#38a169;color:#fff;">
                        อนุมัติ
                      </button>
                    </form>
                    <form method="POST" action="{{ route('admin.commissions.reject', $c) }}"
                      onsubmit="return confirm('ปฏิเสธ commission นี้?')">
                      @csrf
                      <button type="submit" class="admin-button admin-button--sm"
                        style="padding:.25rem .6rem;font-size:.75rem;background:#e53e3e;border-color:#e53e3e;color:#fff;">
                        ปฏิเสธ
                      </button>
                    </form>
                  </div>
                @elseif($c->rejection_reason)
                  <div style="font-size:.75rem;color:#e53e3e;">{{ $c->rejection_reason }}</div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:1rem;">{{ $commissions->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
