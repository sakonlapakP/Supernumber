@extends('layouts.admin')

@section('title', 'Supernumber Admin | แพ็กเกจรายเดือน')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>แพ็กเกจรายเดือน</h1>
      <p class="admin-subtitle">จัดการรหัสแพ็กเกจที่ใช้กับไฟล์ CSV และหน้าสั่งซื้อ</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.phone-packages.create') }}" class="admin-button admin-button--compact">เพิ่มแพ็กเกจ</a>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card admin-feature-card--compact">
    <form action="{{ route('admin.phone-packages') }}" method="get" class="admin-form admin-form--inline admin-form--numbers-search">
      <div class="admin-field">
        <input class="admin-input" type="text" name="q" value="{{ $search ?? '' }}" placeholder="เช่น TRUE-SV-1499, true, 1499" />
      </div>
      <button type="submit" class="admin-button admin-button--compact">ค้นหา</button>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>รหัส</th>
            <th>เครือข่าย</th>
            <th>ชื่อแพ็กเกจ</th>
            <th>ค่ารายเดือน</th>
            <th>Data / โทร</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($packages as $package)
            <tr>
              <td><strong>{{ $package->code }}</strong></td>
              <td>{{ $package->network_label }}</td>
              <td>{{ $package->name }}</td>
              <td>{{ $package->monthly_price_label }}</td>
              <td>{{ $package->data_quota ?: '-' }} / {{ $package->voice_minutes ?: '-' }}</td>
              <td>
                <span class="{{ $package->is_active ? 'admin-status-pill admin-status-pill--active' : 'admin-status-pill' }}">
                  {{ $package->is_active ? 'active' : 'inactive' }}
                </span>
              </td>
              <td>
                <a href="{{ route('admin.phone-packages.edit', $package) }}" class="admin-button admin-button--secondary admin-button--compact">แก้ไข</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="admin-muted">ยังไม่มีแพ็กเกจรายเดือน</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($packages->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้าแพ็กเกจ">
        {{ $packages->appends(request()->query())->links() }}
      </nav>
    @endif
  </section>
@endsection
