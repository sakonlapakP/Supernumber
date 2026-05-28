@extends('layouts.admin')
@section('title', 'Admin | เซลล์ — ' . $sale->name)

@section('content')
<div style="margin-bottom:1rem;">
  <a href="{{ route('admin.sales.index') }}" style="color:#888;font-size:.875rem;">&larr; กลับรายการเซลล์</a>
</div>

@if(session('success'))
  <div class="admin-alert admin-alert--success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

  {{-- Profile --}}
  <div class="admin-card">
    <h3 style="margin-bottom:1rem;font-size:1rem;">ข้อมูลส่วนตัว</h3>
    <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
      @foreach([
        'ชื่อ' => $sale->name,
        'อีเมล' => $sale->email,
        'เบอร์โทร' => $sale->phone ?? '-',
        'เลขบัตร' => $sale->national_id ?? '-',
        'ผู้แนะนำ' => $sale->parent?->name ?? 'ไม่มี',
        'รหัสเซลล์' => $sale->referral_code,
        'สมัครเมื่อ' => $sale->created_at->format('d/m/Y H:i'),
      ] as $label => $value)
        <tr style="border-bottom:1px solid #f0f0f0;">
          <td style="padding:.45rem 0;color:#888;width:35%;">{{ $label }}</td>
          <td style="padding:.45rem 0;font-weight:500;">{{ $value }}</td>
        </tr>
      @endforeach
    </table>
  </div>

  {{-- Bank --}}
  <div class="admin-card">
    <h3 style="margin-bottom:1rem;font-size:1rem;">ข้อมูลบัญชีธนาคาร</h3>
    <table style="width:100%;font-size:.875rem;border-collapse:collapse;">
      @foreach([
        'ธนาคาร' => $sale->bank_name ?? '-',
        'เลขบัญชี' => $sale->bank_account_number ?? '-',
        'ชื่อบัญชี' => $sale->bank_account_name ?? '-',
      ] as $label => $value)
        <tr style="border-bottom:1px solid #f0f0f0;">
          <td style="padding:.45rem 0;color:#888;width:40%;">{{ $label }}</td>
          <td style="padding:.45rem 0;font-weight:500;">{{ $value }}</td>
        </tr>
      @endforeach
    </table>
  </div>

  {{-- KYC Docs --}}
  <div class="admin-card">
    <h3 style="margin-bottom:1rem;font-size:1rem;">เอกสาร KYC</h3>
    @foreach($sale->kycDocuments as $doc)
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid #f0f0f0;">
        <div>
          <div style="font-weight:500;font-size:.875rem;">{{ $doc->type_label }}</div>
          <div style="font-size:.78rem;color:#aaa;">{{ $doc->original_name }}</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
          @php $statusColors = ['pending'=>'#fef3c7;color:#92400e','approved'=>'#d1fae5;color:#065f46','rejected'=>'#fee2e2;color:#991b1b']; @endphp
          <span style="padding:.2rem .5rem;border-radius:999px;font-size:.75rem;font-weight:600;background:{{ $statusColors[$doc->status] ?? '#eee;color:#666' }}">
            {{ $doc->status_label }}
          </span>
          <a href="{{ route('admin.sales.kyc.download', $doc) }}"
            class="admin-button admin-button--sm" style="padding:.3rem .7rem;font-size:.78rem;">ดาวน์โหลด</a>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Stats --}}
  <div class="admin-card">
    <h3 style="margin-bottom:1rem;font-size:1rem;">สถิติ</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
      <div style="background:#f8f9ff;border-radius:8px;padding:.75rem;text-align:center;">
        <div style="font-size:1.5rem;font-weight:700;">{{ $stats['total_orders'] }}</div>
        <div style="font-size:.75rem;color:#888;">ยอดขายตรงทั้งหมด</div>
      </div>
      <div style="background:#f8f9ff;border-radius:8px;padding:.75rem;text-align:center;">
        <div style="font-size:1.3rem;font-weight:700;color:#f5c842;">฿{{ number_format($stats['total_approved'], 2) }}</div>
        <div style="font-size:.75rem;color:#888;">Commission อนุมัติแล้ว</div>
      </div>
      <div style="background:#f8f9ff;border-radius:8px;padding:.75rem;text-align:center;grid-column:span 2;">
        <div style="font-size:1.3rem;font-weight:700;color:#48bb78;">฿{{ number_format($stats['this_month'], 2) }}</div>
        <div style="font-size:.75rem;color:#888;">Commission เดือนนี้ (ทุกสถานะ)</div>
      </div>
    </div>
    <div style="margin-top:.75rem;font-size:.8rem;color:#888;">
      ลูกทีมระดับ 1: {{ $sale->children->count() }} คน
    </div>
  </div>
</div>

{{-- Approve / Reject --}}
@if($sale->sale_status === 'pending')
  <div class="admin-card" style="margin-top:1.25rem;">
    <h3 style="margin-bottom:1rem;font-size:1rem;">การอนุมัติ</h3>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
      <form method="POST" action="{{ route('admin.sales.approve', $sale) }}" style="display:inline;">
        @csrf
        <button type="submit" class="admin-button" style="background:#38a169;border-color:#38a169;color:#fff;">
          อนุมัติเซลล์
        </button>
      </form>

      <form method="POST" action="{{ route('admin.sales.reject', $sale) }}" style="display:inline;"
        onsubmit="return confirm('ยืนยันการปฏิเสธ?')">
        @csrf
        <input type="text" name="reason" placeholder="เหตุผล (ไม่บังคับ)"
          style="padding:.5rem .8rem;border:1.5px solid #dde;border-radius:8px;font-size:.875rem;margin-right:.5rem;">
        <button type="submit" class="admin-button" style="background:#e53e3e;border-color:#e53e3e;color:#fff;">
          ปฏิเสธ
        </button>
      </form>
    </div>
  </div>
@elseif($sale->sale_status === 'approved')
  <div class="admin-alert admin-alert--success" style="margin-top:1rem;">เซลล์คนนี้ได้รับการอนุมัติแล้ว</div>
@endif
@endsection
