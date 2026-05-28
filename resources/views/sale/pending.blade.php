@extends('layouts.sale')
@section('title', 'Supernumber Sale | รอการอนุมัติ')

@section('content')
<div style="max-width:520px;margin:3rem auto;text-align:center;">
  <div class="sale-card">
    @if($user && $user->sale_status === 'rejected')
      <div style="font-size:3rem;margin-bottom:1rem;">❌</div>
      <div style="font-size:1.25rem;font-weight:700;color:#c53030;margin-bottom:.5rem;">ใบสมัครไม่ผ่านการอนุมัติ</div>
      <p style="color:#666;font-size:.9rem;">กรุณาติดต่อทีมงานเพื่อสอบถามรายละเอียด</p>
    @else
      <div style="font-size:3rem;margin-bottom:1rem;">⏳</div>
      <div style="font-size:1.25rem;font-weight:700;color:#1a1a2e;margin-bottom:.5rem;">รอการตรวจสอบ</div>
      <p style="color:#666;font-size:.9rem;margin-bottom:1.5rem;">
        ทีมงานกำลังตรวจสอบเอกสาร KYC ของคุณ<br>
        ปกติใช้เวลา 1-3 วันทำการ
      </p>

      @if($user)
        <div style="background:#f8f9ff;border-radius:10px;padding:1rem;text-align:left;font-size:.875rem;">
          <div style="font-weight:600;margin-bottom:.5rem;color:#444;">สถานะเอกสาร</div>
          @foreach($user->kycDocuments as $doc)
            <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-bottom:1px solid #eee;">
              <span>{{ $doc->type_label }}</span>
              <span class="badge badge-{{ $doc->status }}">{{ $doc->status_label }}</span>
            </div>
          @endforeach
        </div>
      @endif
    @endif

    <div style="margin-top:1.5rem;">
      <a href="{{ route('sale.logout') }}" class="btn btn-primary btn-sm">ออกจากระบบ</a>
    </div>
  </div>
</div>
@endsection
