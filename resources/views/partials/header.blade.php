<header class="topbar">
  <div class="container nav-wrap">
    <div class="brand" aria-label="Supernumber">
      <div class="brand-mark">S</div>
      <div class="brand-text">
        <div class="brand-title">NUMBER</div>
        <div class="brand-sub">SUPERNUMBER</div>
      </div>
    </div>
    <nav class="nav" aria-label="เมนูหลัก">
      <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">หน้าหลัก</a>
      <a class="nav-link" href="#">เบอร์ทั้งหมด</a>
      <a class="nav-link" href="#">คำทำนายละเอียด</a>
      <a class="nav-link" href="#">เบอร์ดีสำหรับผู้หญิง</a>
      <a class="nav-link" href="#">เลือกเบอร์ให้เหมาะกับคุณ</a>
      <a class="nav-link" href="#">ติดต่อเรา</a>
    </nav>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>
  <div id="mobile-menu" class="nav-mobile" aria-label="เมนูสำหรับมือถือ">
    <a href="{{ route('home') }}" class="nav-mobile__link">หน้าหลัก</a>
    <a href="#" class="nav-mobile__link">เบอร์ทั้งหมด</a>
    <a href="#" class="nav-mobile__link">คำทำนายละเอียด</a>
    <a href="#" class="nav-mobile__link">เบอร์ดีสำหรับผู้หญิง</a>
    <a href="#" class="nav-mobile__link">เลือกเบอร์ให้เหมาะกับคุณ</a>
    <a href="#" class="nav-mobile__link">ติดต่อเรา</a>
  </div>
</header>
