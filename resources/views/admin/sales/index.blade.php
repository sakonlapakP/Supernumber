@extends('layouts.admin')
@section('title', 'Admin | จัดการเซลล์')

@section('content')
<div class="admin-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
    <h2 style="margin:0;font-size:1.25rem;">จัดการเซลล์</h2>
    <div style="display:flex;gap:.5rem;">
      @foreach(['pending'=>'รอตรวจสอบ','approved'=>'อนุมัติแล้ว','rejected'=>'ไม่อนุมัติ','all'=>'ทั้งหมด'] as $val => $label)
        <a href="?status={{ $val }}"
          class="admin-button admin-button--sm {{ $status === $val ? '' : 'admin-button--outline' }}"
          style="padding:.35rem .8rem;font-size:.8rem;">{{ $label }}</a>
      @endforeach
    </div>
  </div>

  @if(session('success'))
    <div class="admin-alert admin-alert--success" style="margin-bottom:1rem;">{{ session('success') }}</div>
  @endif

  @if($sales->isEmpty())
    <div style="text-align:center;padding:2rem;color:#aaa;">ไม่มีรายการ</div>
  @else
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
        <thead>
          <tr style="background:#f4f6fb;">
            <th style="padding:.6rem .8rem;text-align:left;">ชื่อ</th>
            <th style="padding:.6rem .8rem;text-align:left;">อีเมล / เบอร์</th>
            <th style="padding:.6rem .8rem;text-align:left;">ผู้แนะนำ</th>
            <th style="padding:.6rem .8rem;text-align:left;">รหัส</th>
            <th style="padding:.6rem .8rem;text-align:left;">สถานะ</th>
            <th style="padding:.6rem .8rem;text-align:left;">วันที่สมัคร</th>
            <th style="padding:.6rem .8rem;"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($sales as $sale)
            <tr style="border-bottom:1px solid #f0f0f0;">
              <td style="padding:.6rem .8rem;font-weight:600;">{{ $sale->name }}</td>
              <td style="padding:.6rem .8rem;">
                <div>{{ $sale->email }}</div>
                <div style="color:#888;font-size:.8rem;">{{ $sale->phone }}</div>
              </td>
              <td style="padding:.6rem .8rem;color:#888;font-size:.85rem;">
                {{ $sale->parent?->name ?? '-' }}
              </td>
              <td style="padding:.6rem .8rem;font-family:monospace;font-weight:600;color:#1a1a2e;">
                {{ $sale->referral_code }}
              </td>
              <td style="padding:.6rem .8rem;">
                @php $statusColors = ['pending'=>'#fef3c7;color:#92400e','approved'=>'#d1fae5;color:#065f46','rejected'=>'#fee2e2;color:#991b1b']; @endphp
                <span style="padding:.2rem .55rem;border-radius:999px;font-size:.75rem;font-weight:600;background:{{ $statusColors[$sale->sale_status] ?? '#eee;color:#666' }}">
                  {{ ['pending'=>'รอตรวจ','approved'=>'อนุมัติ','rejected'=>'ไม่อนุมัติ'][$sale->sale_status] ?? $sale->sale_status }}
                </span>
              </td>
              <td style="padding:.6rem .8rem;color:#888;font-size:.8rem;">
                {{ $sale->created_at->format('d/m/Y') }}
              </td>
              <td style="padding:.6rem .8rem;">
                <a href="{{ route('admin.sales.show', $sale) }}"
                  class="admin-button admin-button--sm" style="padding:.3rem .75rem;font-size:.8rem;">ดู</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="margin-top:1rem;">{{ $sales->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
