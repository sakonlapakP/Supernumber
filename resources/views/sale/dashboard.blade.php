@extends('layouts.sale')
@section('title', 'Sale Dashboard | Supernumber')

@section('content')

{{-- Header --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
  <div>
    <div style="font-size:1.25rem;font-weight:700;">สวัสดี, {{ $user->name }}</div>
    <div style="color:#888;font-size:.875rem;">รหัสเซลล์ของคุณ</div>
  </div>

  {{-- Period selector --}}
  <form method="GET">
    <select name="month" class="form-control" style="width:auto;" onchange="this.form.submit()">
      @foreach($months as $value => $label)
        <option value="{{ $value }}" @selected($value === $period)>{{ $label }}</option>
      @endforeach
    </select>
  </form>
</div>

{{-- Referral code --}}
<div class="referral-box" style="margin-bottom:1.5rem;">
  <div class="referral-box__label">รหัสแนะนำของคุณ</div>
  <div class="referral-box__code" id="refCode">{{ $user->referral_code }}</div>
  <div class="referral-box__url">
    ลิงก์สมัคร: {{ url('/sale/register?ref=' . $user->referral_code) }}
  </div>
  <button onclick="copyCode()" class="btn btn-sm"
    style="background:#f5c842;color:#1a1a2e;margin-top:.75rem;">คัดลอก</button>
</div>

{{-- Stats --}}
<div class="stat-grid" style="margin-bottom:1.5rem;">
  <div class="stat-item">
    <div class="stat-item__value">{{ $summary['total_orders'] }}</div>
    <div class="stat-item__label">เบอร์ที่ขายได้ (เดือนนี้)</div>
  </div>
  <div class="stat-item">
    <div class="stat-item__value" style="color:#f5c842;">
      ฿{{ number_format($summary['direct_amount'], 2) }}
    </div>
    <div class="stat-item__label">Commission ขายตรง</div>
  </div>
  <div class="stat-item">
    <div class="stat-item__value" style="color:#48bb78;">
      ฿{{ number_format($summary['override_amount'], 2) }}
    </div>
    <div class="stat-item__label">Override ลูกทีม</div>
  </div>
  <div class="stat-item">
    <div class="stat-item__value" style="color:#4299e1;">
      ฿{{ number_format($summary['approved_amount'], 2) }}
    </div>
    <div class="stat-item__label">อนุมัติแล้ว</div>
  </div>
</div>

{{-- Downline summary --}}
<div class="sale-card" style="margin-bottom:1.5rem;">
  <div class="sale-card__title">ทีมของคุณ</div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;text-align:center;">
    <div>
      <div style="font-size:1.5rem;font-weight:700;color:#1a1a2e;">{{ $downlineLevel1 }}</div>
      <div style="font-size:.8rem;color:#888;">ลูกทีมระดับ 1 (ได้ override 15%)</div>
    </div>
    <div>
      <div style="font-size:1.5rem;font-weight:700;color:#1a1a2e;">{{ $downlineLevel2 }}</div>
      <div style="font-size:.8rem;color:#888;">ลูกทีมระดับ 2 (ได้ override 10%)</div>
    </div>
  </div>
</div>

{{-- Commission table --}}
<div class="sale-card">
  <div class="sale-card__title">รายละเอียด Commission — {{ $months[$period] ?? $period }}</div>

  @if($commissions->isEmpty())
    <div style="text-align:center;padding:2rem;color:#aaa;font-size:.9rem;">
      ยังไม่มีรายการในเดือนนี้
    </div>
  @else
    <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr>
            <th>วันที่</th>
            <th>เบอร์โทร</th>
            <th>ประเภท</th>
            <th>ยอดสุทธิ</th>
            <th>Commission</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          @foreach($commissions as $c)
            <tr>
              <td style="white-space:nowrap;font-size:.8rem;color:#888;">
                {{ $c->created_at->format('d/m/y') }}
              </td>
              <td>{{ $c->order?->ordered_number ?? '-' }}</td>
              <td>{{ $c->tier_label }}</td>
              <td>฿{{ number_format($c->order?->net_amount ?? 0, 2) }}</td>
              <td style="font-weight:600;">฿{{ number_format($c->calculated_amount, 2) }}</td>
              <td><span class="badge badge-{{ $c->status }}">{{ $c->status_label }}</span></td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="background:#f8f9ff;font-weight:700;">
            <td colspan="4">รวม (pending + approved)</td>
            <td>฿{{ number_format($summary['direct_amount'] + $summary['override_amount'], 2) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  @endif
</div>

{{-- Anti-fraud notice --}}
<div class="alert alert-info" style="font-size:.8rem;margin-top:1rem;">
  Commission ชั้น 2 และ 3 จะถูกตรวจสอบเกณฑ์ 3:1 ปลายเดือน
  (ต้องขายตรงอย่างน้อย 1 เบอร์ต่อทุก 3 เบอร์ที่ลูกทีมขาย) ก่อนเปลี่ยนเป็น "อนุมัติ"
</div>

@endsection

@push('scripts')
<script>
function copyCode() {
  navigator.clipboard.writeText('{{ $user->referral_code }}').then(() => {
    alert('คัดลอกรหัสแล้ว: {{ $user->referral_code }}');
  });
}
</script>
@endpush
