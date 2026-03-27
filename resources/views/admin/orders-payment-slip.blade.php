@extends('layouts.admin')

@section('title', 'Supernumber Admin | Payment Slip')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Payment Slip</h1>
      <p class="admin-subtitle">ไฟล์หลักฐานการโอนของคำสั่งซื้อ #{{ $order->id }}</p>
    </div>
    <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
      <a href="{{ route('admin.orders.show', $order) }}" class="admin-button admin-button--muted admin-button--compact">กลับ</a>
      <a href="{{ $slipUrl }}" target="_blank" rel="noopener noreferrer" class="admin-button admin-button--compact">เปิดไฟล์ดิบ</a>
    </div>
  </div>

  <section class="admin-card admin-feature-card">
    @if ($paymentSlip['external_url'])
      <iframe src="{{ $paymentSlip['external_url'] }}" title="Payment Slip" style="width: 100%; min-height: 80vh; border: 1px solid rgba(185, 199, 224, 0.5); border-radius: 14px;"></iframe>
    @elseif ($paymentSlip['is_image'])
      <div style="display: grid; justify-items: center;">
        <img src="{{ $slipUrl }}" alt="หลักฐานการโอน" style="max-width: min(960px, 100%); width: 100%; border-radius: 14px; border: 1px solid rgba(185, 199, 224, 0.5);" />
      </div>
    @else
      <iframe src="{{ $slipUrl }}" title="Payment Slip" style="width: 100%; min-height: 80vh; border: 1px solid rgba(185, 199, 224, 0.5); border-radius: 14px;"></iframe>
    @endif
  </section>
@endsection
