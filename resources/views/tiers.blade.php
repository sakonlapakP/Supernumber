@extends('layouts.app')

@section('title', 'Supernumber | Bronze Gold Platinum')
@section('meta_description', 'ปรับระดับเบอร์มงคลเป็น Bronze, Gold, Platinum เพื่อให้ดูเข้าใจง่ายและดูดีขึ้น')
@section('og_title', 'Supernumber | Bronze Gold Platinum')
@section('og_description', 'ตัวอย่างการแบ่งระดับเบอร์เป็น Bronze, Gold, Platinum พร้อมโทนสีและสัญลักษณ์เฉพาะ')
@section('canonical', url('/tiers'))
@section('og_url', url('/tiers'))
@section('og_image', asset('images/home_banner.jpg'))

@section('content')
  <section class="tier-hero" aria-labelledby="tiers-title">
    <div class="tier-hero__overlay"></div>
    <div class="container tier-hero__content">
      <div class="tier-hero__text">
        <p class="tier-kicker">ระดับราคาใหม่</p>
        <h1 id="tiers-title">Bronze • Gold • Platinum</h1>
        <p>
          เปลี่ยนจากดาวเป็นระดับสีและสัญลักษณ์ เพื่อให้ดูพรีเมียมขึ้นและไม่ทำให้ระดับเริ่มต้นดูด้อยค่า
        </p>
        <div class="tier-legend">
          <span class="tier-legend__item tier-legend__item--silver">Bronze · 699+</span>
          <span class="tier-legend__item tier-legend__item--gold">Gold · 1199+</span>
          <span class="tier-legend__item tier-legend__item--platinum">Platinum · 1499+</span>
        </div>
      </div>
    </div>
  </section>

  <section class="tier-section">
    <div class="container">
      <div class="tier-grid">
        <article class="tier-card tier-card--silver">
          <div class="tier-medal">B</div>
          <div>
            <span class="tier-badge">Bronze</span>
            <h2 class="tier-name">ระดับคุ้มค่า</h2>
            <p class="tier-price">699 บาทขึ้นไป</p>
          </div>
          <ul class="tier-features">
            <li>เหมาะกับการเริ่มต้นและใช้งานทั่วไป</li>
            <li>คัดสรรเบอร์ที่ดูดีและเรียบง่าย</li>
            <li>งบประหยัดแต่ยังดูมีระดับ</li>
          </ul>
          <a class="tier-cta" href="{{ route('home') }}">ดูเบอร์ Bronze</a>
        </article>

        <article class="tier-card tier-card--gold">
          <div class="tier-medal">G</div>
          <div>
            <span class="tier-badge">Gold</span>
            <h2 class="tier-name">ระดับยอดนิยม</h2>
            <p class="tier-price">1199 บาทขึ้นไป</p>
          </div>
          <ul class="tier-features">
            <li>สมดุลทั้งความหมายและความสวยของเลข</li>
            <li>ภาพลักษณ์ดี เหมาะกับงานขาย/บริการ</li>
            <li>คุ้มค่าสำหรับคนอยากอัปเกรดเบอร์</li>
          </ul>
          <a class="tier-cta" href="{{ route('home') }}">ดูเบอร์ Gold</a>
        </article>

        <article class="tier-card tier-card--platinum">
          <div class="tier-medal">P</div>
          <div>
            <span class="tier-badge">Platinum</span>
            <h2 class="tier-name">ระดับพรีเมียม</h2>
            <p class="tier-price">1499 บาทขึ้นไป</p>
          </div>
          <ul class="tier-features">
            <li>เลขสวยเด่นและมีพลังส่งเสริมสูง</li>
            <li>เหมาะกับผู้บริหาร/ธุรกิจ/ภาพลักษณ์</li>
            <li>เน้นความพรีเมียมและความมั่นใจ</li>
          </ul>
          <a class="tier-cta" href="{{ route('home') }}">ดูเบอร์ Platinum</a>
        </article>
      </div>
    </div>
  </section>
@endsection
