@extends('layouts.app')

@section('title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('meta_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('canonical', url('/'))
@section('og_url', url('/'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))

@section('content')
  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <div class="hero-left">
        <p class="hero-kicker">แค่เปลี่ยนเบอร์ชีวิตคุณก็เปลี่ยน</p>
        <h1 class="hero-title" id="hero-title">
          เบอร์มงคลไม่ต้องซื้อ ใคร ๆ ก็เป็นเจ้าของได้ที่นี่...ที่เดียว
        </h1>
        <p class="hero-subtitle">
          ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จ
        </p>
        <form class="hero-form" action="{{ route('evaluate') }}" method="get">
          <label class="hero-label" for="phone">กรอกเบอร์มือถือ</label>
          <div class="hero-input">
            <input id="phone" name="phone" type="tel" placeholder="0XX-XXX-XXXX" />
            <button type="submit">วิเคราะห์</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="numbers" aria-labelledby="numbers-title">
    <div class="container">
      <div class="section-title">
        <h2 id="numbers-title">เบอร์มงคลชีวิต</h2>
        <p>คัดสรรเบอร์เด่นพร้อมพลังงานเหมาะกับคุณ</p>
      </div>
      <div class="card-grid">
        <article class="number-card">
          <div class="card-top">0644626561</div>
          <div class="card-body">
                <div class="card-tier card-tier--platinum">Platinum</div>
            <p>Max Speed Unlimited</p>
            <span>1499 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0646369891</div>
          <div class="card-body">
                <div class="card-tier card-tier--gold">Gold</div>
            <p>Max Speed Unlimited</p>
            <span>1099 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0645414493</div>
          <div class="card-body">
                <div class="card-tier card-tier--silver">Silver</div>
            <p>Max Speed Unlimited</p>
            <span>699 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0646299236</div>
          <div class="card-body">
                <div class="card-tier card-tier--gold">Gold</div>
            <p>Max Speed Unlimited</p>
            <span>1099 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0648289287</div>
          <div class="card-body">
                <div class="card-tier card-tier--silver">Silver</div>
            <p>Max Speed Unlimited</p>
            <span>699 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0645166194</div>
          <div class="card-body">
                <div class="card-tier card-tier--platinum">Platinum</div>
            <p>Max Speed Unlimited</p>
            <span>1499 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0645356454</div>
          <div class="card-body">
                <div class="card-tier card-tier--platinum">Platinum</div>
            <p>Max Speed Unlimited</p>
            <span>1499 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
        <article class="number-card">
          <div class="card-top">0645453919</div>
          <div class="card-body">
                <div class="card-tier card-tier--platinum">Platinum</div>
            <p>Max Speed Unlimited</p>
            <span>1499 จัดไป</span>
          </div>
          <button class="card-btn">ดูความหมาย</button>
        </article>
      </div>
    </div>
  </section>
@endsection
