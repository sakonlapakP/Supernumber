@extends('layouts.app')

@section('title', 'Supernumber | ปรับปรุงเว็บไซต์')
@section('meta_description', 'หน้ากำลังอยู่ระหว่างปรับปรุง โปรดกลับมาใหม่อีกครั้ง')
@section('og_title', 'Supernumber | ปรับปรุงเว็บไซต์')
@section('og_description', 'หน้ากำลังอยู่ระหว่างปรับปรุง โปรดกลับมาใหม่อีกครั้ง')
@section('canonical', url('/under-construction'))
@section('og_url', url('/under-construction'))
@section('theme_color', '#2a2321')
@section('body_class', 'under-construction-page')
@section('hide_header', true)
@section('hide_footer', true)

@section('content')
  <section class="under-construction">
    <div class="under-construction__glow under-construction__glow--left"></div>
    <div class="under-construction__glow under-construction__glow--right"></div>

    <div class="container">
      <div class="under-construction__card">
        <p class="under-construction__eyebrow">SUPERNUMBER</p>
        <h1>เว็บไซต์กำลังปรับปรุง</h1>
        <p class="under-construction__lead">
          เว็บไซต์จะกลับมาใช้งานได้อีกครั้งในวันที่ 30 มีนาคม
          หากต้องการสอบถามข้อมูลเพิ่มเติม สามารถติดต่อทีมงานได้ตามช่องทางด้านล่าง
        </p>

        <div class="under-construction__status">
          <span class="under-construction__dot"></span>
          <span>พร้อมให้บริการอีกครั้งวันที่ 30 มีนาคม</span>
        </div>

        <div class="under-construction__contact">
          <a class="under-construction__contact-card" href="tel:0963232656">
            <span class="under-construction__contact-label">โทรศัพท์</span>
            <strong>096-323-2656</strong>
          </a>
          <a class="under-construction__contact-card" href="tel:0963232665">
            <span class="under-construction__contact-label">โทรศัพท์</span>
            <strong>096-323-2665</strong>
          </a>
          <a class="under-construction__contact-card under-construction__contact-card--line" href="https://line.me/ti/p/~supernumber" target="_blank" rel="noopener noreferrer">
            <span class="under-construction__contact-label">LINE</span>
            <strong>@supernumber</strong>
          </a>
        </div>
      </div>
    </div>
  </section>
@endsection
