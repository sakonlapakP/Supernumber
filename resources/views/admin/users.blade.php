@extends('layouts.admin')

@section('title', 'Supernumber Admin | ผู้ใช้งาน')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>ผู้ใช้งาน</h1>
      <p class="admin-subtitle">สร้างผู้ใช้สำหรับหลังบ้าน และกำหนด role เพื่อควบคุมสิทธิ์การใช้งาน</p>
    </div>
    <div class="admin-page-actions">
      <div class="admin-summary">ทั้งหมด {{ number_format($users->count()) }} ผู้ใช้</div>
      <button type="button" id="users-add-toggle" class="admin-button admin-button--compact">เพิ่มผู้ใช้</button>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success" style="margin-bottom: 18px;">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <section
    id="users-add-panel"
    class="admin-card admin-feature-card"
    style="margin-bottom: 18px;"
    @if (! $errors->any() && ! old('name') && ! old('username') && ! old('email') && ! old('role')) hidden @endif
  >
    <h2 class="admin-feature-card__title" style="margin-bottom: 14px; font-size: 24px;">สร้างผู้ใช้</h2>
    <form class="admin-form" action="{{ route('admin.users.store') }}" method="post">
      @csrf
      <div class="admin-field">
        <label for="user-name">ชื่อ</label>
        <input id="user-name" class="admin-input" type="text" name="name" value="{{ old('name') }}" required />
      </div>
      <div class="admin-field">
        <label for="user-username">ชื่อผู้ใช้</label>
        <input id="user-username" class="admin-input" type="text" name="username" value="{{ old('username') }}" required />
      </div>
      <div class="admin-field">
        <label for="user-email">อีเมล</label>
        <input id="user-email" class="admin-input" type="email" name="email" value="{{ old('email') }}" required />
      </div>
      <div class="admin-field">
        <label for="user-role">สิทธิ์</label>
        <select id="user-role" class="admin-select" name="role" required>
          @foreach ($roleOptions as $role)
            <option value="{{ $role }}" @selected(old('role', 'staff') === $role)>{{ \App\Models\User::roleLabelMap()[$role] ?? $role }}</option>
          @endforeach
        </select>
      </div>
      <div class="admin-field">
        <label for="user-password">รหัสผ่าน</label>
        <input id="user-password" class="admin-input" type="password" name="password" required />
      </div>
      <button type="submit" class="admin-button">สร้างผู้ใช้</button>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ชื่อ</th>
            <th>ชื่อผู้ใช้</th>
            <th>อีเมล</th>
            <th>สิทธิ์</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr>
              <td>{{ $user->name }}</td>
              <td>{{ $user->username ?: '-' }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->role_label }}</td>
              <td>
                @if ($user->is_active)
                  <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span class="admin-badge admin-badge--success">เปิดใช้งาน</span>
                    <form action="{{ route('admin.users.toggle-active', $user) }}" method="post">
                      @csrf
                      <button type="submit" class="admin-button admin-button--compact admin-button--muted" onclick="return confirm('ปิดการใช้งานผู้ใช้ {{ $user->name }}?')">ปิดใช้งาน</button>
                    </form>
                  </div>
                @else
                  <span class="admin-badge admin-badge--warning" style="margin-bottom: 6px; display: inline-flex;">รอการอนุมัติ</span>
                  <form action="{{ route('admin.users.approve', $user) }}" method="post" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 6px;">
                    @csrf
                    <select name="role" class="admin-select admin-select--compact" style="margin: 0; width: auto;">
                      @foreach ($roleOptions as $role)
                        <option value="{{ $role }}" @selected($user->role === $role)>{{ \App\Models\User::roleLabelMap()[$role] ?? $role }}</option>
                      @endforeach
                    </select>
                    <button type="submit" class="admin-button admin-button--compact">อนุมัติ</button>
                  </form>
                  <form action="{{ route('admin.users.reject', $user) }}" method="post">
                    @csrf
                    <button type="submit" class="admin-button admin-button--compact admin-button--danger" onclick="return confirm('ปฏิเสธและลบผู้ใช้ {{ $user->name }}?\nการดำเนินการนี้ไม่สามารถยกเลิกได้')">ปฏิเสธ / ลบ</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="admin-muted">ยังไม่มีผู้ใช้ในระบบ</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <script>
    (() => {
      const addToggle = document.getElementById("users-add-toggle");
      const addPanel = document.getElementById("users-add-panel");
      const firstInput = document.getElementById("user-name");

      if (!addToggle || !addPanel) return;

      addToggle.addEventListener("click", () => {
        const isHidden = addPanel.hidden;
        addPanel.hidden = !isHidden;

        if (isHidden && firstInput) {
          firstInput.focus();
        }
      });
    })();
  </script>
@endsection
