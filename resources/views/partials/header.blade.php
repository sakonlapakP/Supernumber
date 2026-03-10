<header class="topbar">
  <div class="container nav-wrap">
    <a class="brand" aria-label="Supernumber" href="{{ route('home') }}">
      <div class="brand-mark">S</div>
      <div class="brand-text">
        <div class="brand-title">NUMBER</div>
        <div class="brand-sub">SUPERNUMBER</div>
      </div>
    </a>
    <nav class="nav" aria-label="เมนูหลัก">
      <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">หน้าหลัก</a>
      <a class="nav-link {{ request()->routeIs('numbers.index') ? 'is-active' : '' }}" href="{{ route('numbers.index') }}">เบอร์ทั้งหมด</a>
      <a class="nav-link {{ request()->routeIs('articles.*') ? 'is-active' : '' }}" href="{{ route('articles.index') }}">บทความ</a>
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
    <a href="{{ route('numbers.index') }}" class="nav-mobile__link">เบอร์ทั้งหมด</a>
    <a href="{{ route('articles.index') }}" class="nav-mobile__link">บทความ</a>
    <a href="#" class="nav-mobile__link">ติดต่อเรา</a>
  </div>
</header>
