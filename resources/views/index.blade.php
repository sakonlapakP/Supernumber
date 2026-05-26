@extends('layouts.app')

@php
  $homeBannerVersion  = @filemtime(public_path('images/home_banner.jpg')) ?: time();
  $homeBannerUrl      = asset('images/home_banner.jpg') . '?v=' . $homeBannerVersion;
  $homeBannerWebp     = asset('images/home_banner.webp') . '?v=' . $homeBannerVersion;
  $homeBannerMobWebp  = asset('images/home_banner_mobile.webp') . '?v=' . $homeBannerVersion;
  $homeBannerMobJpg   = asset('images/home_banner_mobile.jpg') . '?v=' . $homeBannerVersion;
@endphp

@section('title', 'Supernumber ศูนย์รวมเบอร์มงคลอันดับ 1')
@section('meta_description', 'Supernumber ศูนย์รวมเบอร์มงคลอันดับ 1 แค่เปลี่ยนเบอร์ชีวิตคุณก็เปลี่ยน วิเคราะห์เบอร์มือถือฟรี ช่วยเสริมพลังให้ทุกก้าวสำคัญเพิ่มโอกาสและปลดล็อกเส้นทางสำเร็จ')
@section('og_title', 'Supernumber ศูนย์รวมเบอร์มงคลอันดับ 1')
@section('og_description', 'Supernumber ศูนย์รวมเบอร์มงคลอันดับ 1 แค่เปลี่ยนเบอร์ชีวิตคุณก็เปลี่ยน วิเคราะห์เบอร์มือถือฟรี ช่วยเสริมพลังให้ทุกก้าวสำคัญเพิ่มโอกาสและปลดล็อกเส้นทางสำเร็จ')
@section('canonical', url('/'))
@section('og_url', url('/'))
@section('og_image', $homeBannerUrl)
@section('preload_image', $homeBannerWebp)
@section('preload_imagesrcset', $homeBannerMobWebp . ' 768w, ' . $homeBannerWebp . ' 1920w')
@section('preload_imagesizes', '100vw')
@section('body_class', 'home-scale-soft')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/article.css') }}?v={{ substr(md5_file(public_path('css/article.css')), 0, 8) }}" />
@endpush

@section('seo_schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Organization",
  "name": "Supernumber",
  "url": "{{ url('/') }}",
  "logo": "{{ asset('images/logo.png') }}",
  "contactPoint": {
    "@@type": "ContactPoint",
    "telephone": "+66-96-323-2656",
    "contactType": "customer service"
  }
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "url": "{{ url('/') }}",
  "potentialAction": {
    "@@type": "SearchAction",
    "target": "{{ url('/numbers') }}?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
@endsection

@section('content')
  <style>
    body.home-scale-soft .numbers {
      padding-top: 20px !important;
    }

    @media (min-width: 992px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(4, 240px) !important;
        grid-auto-rows: auto !important;
        justify-content: center !important;
        align-items: stretch !important;
        gap: 16px !important;
      }

      body.home-scale-soft .hero-left {
        max-width: 900px !important;
      }
      body.home-scale-soft .hero-title {
        white-space: nowrap !important;
      }
      body.home-scale-soft .hero-form {
        max-width: 60% !important;
      }
    }

    /* Redesigned Home Search Section - In-lined for Instant Load */
    .home-search {
      margin-top: -40px; 
      position: relative;
      z-index: 10;
      padding-bottom: 10px;
    }

    .home-filter {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 40px;
      padding: 40px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 10px 40px rgba(45, 33, 24, 0.1), 0 2px 4px rgba(216, 163, 74, 0.1);
    }

    .home-filter__header {
      text-align: center;
      margin-bottom: 40px;
    }

    .home-filter__header h2 {
      font-size: 34px;
      color: #3b2f27;
      font-weight: 800;
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }

    .home-filter__header p {
      color: #7a6c62;
      font-size: 17px;
    }

    .home-filter__main {
      display: grid;
      grid-template-columns: 1fr auto 1.2fr;
      gap: 30px;
      align-items: flex-end;
      margin-bottom: 32px;
    }

    .home-filter__group {
      display: grid;
      gap: 12px;
    }

    .home-filter__label {
      font-size: 14px;
      font-weight: 700;
      color: #4a3e35;
      padding-left: 4px;
    }

    .home-filter__input-wrapper {
      position: relative;
    }

    .home-filter__input {
      width: 100%;
      height: 56px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.1);
      border-radius: 16px;
      padding: 0 20px;
      font-size: 16px;
      color: #3b2f27;
      transition: all 0.3s ease;
    }

    .home-filter__input:focus {
      outline: none;
      border-color: #d8a34a;
      box-shadow: 0 0 0 4px rgba(216, 163, 74, 0.1);
    }

    .home-filter__orb {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #f9f6f1, #f0e8db);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 800;
      color: #8a7a6c;
      border: 1px solid rgba(216, 163, 74, 0.2);
      margin-bottom: 4px;
    }

    .home-filter__position-row {
      display: flex;
      align-items: center;
      gap: 10px;
      height: 56px;
    }

    .home-filter__pos-prefix {
      width: 70px;
      height: 56px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.12);
      border-radius: 14px;
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      color: #3b2f27;
    }

    .home-filter__pos-digits {
      display: flex;
      align-items: center;
      gap: 5px;
      flex: 1;
    }

    .home-filter__pos-input {
      width: 100%;
      height: 56px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.12);
      border-radius: 14px;
      text-align: center;
      font-size: 18px;
      font-weight: 600;
      color: #3b2f27;
      transition: all 0.3s ease;
    }

    .home-filter__pos-input:focus {
      outline: none;
      border-color: #d8a34a;
      background: #fffefb;
    }

    .home-filter__pos-sep {
      width: 6px;
      height: 2px;
      background: #d8a34a;
      opacity: 0.3;
      margin: 0 2px;
    }

    .home-filter__footer {
      padding-top: 24px;
      border-top: 1px solid rgba(74, 62, 53, 0.08);
    }

    .home-filter__footer-controls {
      display: grid;
      grid-template-columns: 1fr 1.5fr auto;
      gap: 20px;
      align-items: center;
    }

    .home-filter__select-wrapper {
      display: grid;
      gap: 6px;
    }

    .home-filter__select-wrapper label {
      font-size: 12px;
      font-weight: 700;
      color: #8a7a6c;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding-left: 2px;
    }

    .home-filter__select-wrapper select {
      width: 100%;
      height: 48px;
      background: #fff;
      border: 1px solid rgba(73, 61, 52, 0.1);
      border-radius: 12px;
      padding: 0 16px;
      font-size: 15px;
      color: #3b2f27;
    }

    .home-filter__submit {
      height: 56px;
      padding: 0 48px;
      background: linear-gradient(135deg, #3b2f27, #201812);
      color: #fff;
      border: none;
      border-radius: 16px;
      font-size: 17px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 10px 25px rgba(32, 24, 18, 0.25);
      margin-top: 20px;
    }

    .home-filter__submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(32, 24, 18, 0.35);
      background: linear-gradient(135deg, #4d3e34, #2a2019);
    }

    @media (max-width: 1024px) {
      .home-filter__main {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .home-filter__orb {
        justify-self: center;
        margin: 0;
      }
    }

    @media (max-width: 768px) {
      .home-search {
        margin-top: -20px;
        padding-bottom: 30px;
      }
      .home-filter {
        padding: 20px 15px;
        border-radius: 25px;
      }
      .home-filter__header h2 {
        font-size: 24px;
      }
      .home-filter__header p {
        font-size: 14px;
      }
      .home-filter__footer-controls {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
      }
      .home-filter__submit {
        margin-top: 0;
        width: 100%;
        height: 48px;
        align-self: end;
      }
      .home-filter__position-row {
        gap: 6px;
      }
      .home-filter__pos-prefix {
        width: 60px;
        font-size: 14px;
      }
      .home-filter__pos-input {
        height: 50px;
        font-size: 16px;
      }
      
      /* Optimize Numbers Grid for Mobile */
      .home-card-grid {
        gap: 16px 12px !important;
        padding: 0 8px !important;
      }
      .home-card-item {
        padding: 12px !important;
      }
      .home-number-display {
        font-size: 18px !important;
      }
      .home-card-price {
        font-size: 13px !important;
      }

      /* Dynamic Fluid Sizing using clamp() and vw for Number Cards on Mobile */
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home {
        min-height: 165px !important;
        padding: 12px 10px !important;
        width: 100% !important;
        max-width: none !important;
      }
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-top {
        font-size: clamp(17px, 5.2vw, 21px) !important;
        padding: 12px 10px !important;
        letter-spacing: 0.04em !important;
        margin: 0 !important;
        line-height: 1 !important;
      }
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-network-main,
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-network-suffix {
        font-size: clamp(12px, 3.4vw, 13px) !important;
      }
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-meta-plan,
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-meta-price,
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-meta-plan *,
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-meta-price * {
        font-size: clamp(14px, 3.8vw, 16px) !important;
      }
      body.home-scale-soft .home-card-grid[data-view="grid"] .number-card--home .card-btn {
        font-size: clamp(12px, 3.6vw, 14px) !important;
        min-height: 36px !important;
        padding: 0 10px !important;
        border-radius: 12px !important;
        margin-top: 6px !important;
      }
    }

    @media (min-width: 986px) and (max-width: 1199px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(3, 240px) !important;
        grid-auto-rows: auto !important;
        justify-content: center !important;
        align-items: stretch !important;
        gap: 16px !important;
      }
    }

    @media (min-width: 681px) and (max-width: 985px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(2, 240px) !important;
        grid-auto-rows: auto !important;
        justify-content: center !important;
        align-items: stretch !important;
        gap: 16px !important;
      }
    }

    @media (max-width: 680px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
      }
    }

    @media (max-width: 420px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        gap: 8px !important;
      }
    }

    /* ===== HOME TOPICS SECTION ===== */
    .home-topics {
      padding: 32px 0 8px;
    }

    .home-topics__inner {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 28px;
      padding: 28px 28px 24px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 4px 24px rgba(45, 33, 24, 0.08);
    }

    .home-topics__header {
      margin-bottom: 20px;
    }

    .home-topics__header h2 {
      font-size: 20px;
      font-weight: 800;
      color: #3b2f27;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .home-topics__header h2::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 20px;
      background: linear-gradient(to bottom, #d8a34a, #9a6f2e);
      border-radius: 2px;
      flex-shrink: 0;
    }

    .home-topics__header p {
      font-size: 14px;
      color: #8a7a6c;
      padding-left: 12px;
    }

    .home-topics__grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    .home-topic-card {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.1);
      border-radius: 14px;
      padding: 14px 14px 14px 12px;
      text-decoration: none;
      color: inherit;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .home-topic-card:hover {
      border-color: #d8a34a;
      box-shadow: 0 4px 16px rgba(216, 163, 74, 0.2);
      transform: translateY(-1px);
      text-decoration: none;
      color: inherit;
    }

    .home-topic-card__icon {
      width: 40px;
      height: 40px;
      border-radius: 11px;
      background: #f9f6f1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
      border: 1px solid rgba(216, 163, 74, 0.15);
    }

    .home-topic-card__text {
      flex: 1;
      min-width: 0;
    }

    .home-topic-card__name {
      font-size: 13.5px;
      font-weight: 700;
      color: #3b2f27;
      line-height: 1.3;
    }

    .home-topic-card__arrow {
      font-size: 14px;
      color: #c5b09a;
      flex-shrink: 0;
      transition: color 0.2s, transform 0.2s;
    }

    .home-topic-card:hover .home-topic-card__arrow {
      color: #d8a34a;
      transform: translateX(2px);
    }

    @media (max-width: 640px) {
      .home-topics__inner {
        padding: 20px 16px;
        border-radius: 20px;
      }
      .home-topics__grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
      }
      .home-topic-card {
        padding: 12px 10px;
        gap: 8px;
      }
      .home-topic-card__icon {
        width: 36px;
        height: 36px;
        font-size: 18px;
      }
      .home-topic-card__name {
        font-size: 12.5px;
      }
    }

    /* ===== HOME LEGENDARY SECTION ===== */
    .home-legendary {
      padding: 24px 0 16px;
    }

    .home-legendary__inner {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 28px;
      padding: 32px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 4px 24px rgba(45, 33, 24, 0.08);
    }

    .home-legendary__header {
      margin-bottom: 24px;
      text-align: center;
    }

    .home-legendary__header h2 {
      font-size: 24px;
      font-weight: 800;
      color: #3b2f27;
      margin-bottom: 6px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
    }

    .home-legendary__header h2::before,
    .home-legendary__header h2::after {
      content: '';
      display: inline-block;
      width: 24px;
      height: 2px;
      background: linear-gradient(to right, transparent, #d8a34a);
      flex-shrink: 0;
    }
    
    .home-legendary__header h2::after {
      background: linear-gradient(to left, transparent, #d8a34a);
    }

    .home-legendary__header p {
      font-size: 14.5px;
      color: #8a7a6c;
      font-weight: 500;
    }

    .home-legendary__grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .legendary-card {
      position: relative;
      overflow: hidden;
      border-radius: 20px;
      padding: 28px 24px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 220px;
      text-decoration: none;
      transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .legendary-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 1;
    }

    .legendary-card:hover::before {
      opacity: 1;
    }

    .legendary-card:hover {
      transform: translateY(-4px);
      text-decoration: none;
    }

    /* Dragon Card Styles */
    .legendary-card--dragon {
      background: linear-gradient(135deg, #1f1a16 0%, #3a1c13 100%);
      box-shadow: 0 10px 30px rgba(58, 28, 19, 0.15);
      border: 1px solid rgba(216, 163, 74, 0.15);
    }

    .legendary-card--dragon:hover {
      box-shadow: 0 15px 35px rgba(58, 28, 19, 0.3), 0 0 15px rgba(216, 163, 74, 0.2);
      border-color: rgba(216, 163, 74, 0.4);
    }

    /* Swan Card Styles */
    .legendary-card--swan {
      background: linear-gradient(135deg, #fcfaf7 0%, #f6ebe1 100%);
      box-shadow: 0 10px 30px rgba(138, 122, 108, 0.1);
      border: 1px solid rgba(216, 163, 74, 0.1);
    }

    .legendary-card--swan:hover {
      box-shadow: 0 15px 35px rgba(138, 122, 108, 0.2), 0 0 15px rgba(216, 163, 74, 0.15);
      border-color: rgba(216, 163, 74, 0.3);
    }

    .legendary-card__content {
      position: relative;
      z-index: 2;
    }

    .legendary-card__badge-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .legendary-card__badge {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      padding: 4px 10px;
      border-radius: 20px;
    }

    .legendary-card--dragon .legendary-card__badge {
      background: rgba(216, 163, 74, 0.2);
      color: #e6bd73;
      border: 1px solid rgba(216, 163, 74, 0.3);
    }

    .legendary-card--swan .legendary-card__badge {
      background: rgba(216, 163, 74, 0.12);
      color: #9a6f2e;
      border: 1px solid rgba(216, 163, 74, 0.2);
    }

    .legendary-card__icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 38px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      border: 2px solid rgba(216, 163, 74, 0.35);
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .legendary-card--dragon .legendary-card__icon {
      background: linear-gradient(135deg, #e6bd73 0%, #9a6f2e 100%);
      border-color: rgba(230, 189, 115, 0.4);
      box-shadow: 0 8px 24px rgba(216, 163, 74, 0.25);
    }

    .legendary-card--swan .legendary-card__icon {
      background: linear-gradient(135deg, #fcfaf7 0%, #f6ebe1 100%);
      border-color: rgba(216, 163, 74, 0.3);
      box-shadow: 0 8px 24px rgba(138, 122, 108, 0.15);
    }

    .legendary-card:hover .legendary-card__icon {
      transform: scale(1.15) rotate(8deg);
    }

    .legendary-card__title {
      font-size: 22px;
      font-weight: 850;
      margin-bottom: 8px;
      line-height: 1.2;
    }

    .legendary-card--dragon .legendary-card__title {
      color: #f7e6c4;
      background: linear-gradient(to right, #ffffff, #e6bd73);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .legendary-card--swan .legendary-card__title {
      color: #3b2f27;
      background: linear-gradient(to right, #3b2f27, #7a583c);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .legendary-card__desc {
      font-size: 13.5px;
      line-height: 1.45;
      font-weight: 500;
    }

    .legendary-card--dragon .legendary-card__desc {
      color: #c9b8ad;
    }

    .legendary-card--swan .legendary-card__desc {
      color: #726255;
    }

    .legendary-card__numbers-preview {
      display: flex;
      gap: 6px;
      margin-top: 14px;
    }

    .legendary-card__number-pill {
      font-size: 12px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
    }

    .legendary-card--dragon .legendary-card__number-pill {
      background: rgba(255, 255, 255, 0.08);
      color: #e6bd73;
      border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .legendary-card--swan .legendary-card__number-pill {
      background: rgba(74, 62, 53, 0.05);
      color: #9a6f2e;
      border: 1px solid rgba(74, 62, 53, 0.03);
    }

    .legendary-card__action {
      margin-top: 24px;
      display: flex;
      align-items: center;
      font-size: 14px;
      font-weight: 700;
      gap: 8px;
      z-index: 2;
    }

    .legendary-card--dragon .legendary-card__action {
      color: #e6bd73;
    }

    .legendary-card--swan .legendary-card__action {
      color: #9a6f2e;
    }

    .legendary-card__arrow {
      transition: transform 0.2s ease;
    }

    .legendary-card:hover .legendary-card__arrow {
      transform: translateX(4px);
    }

    @media (max-width: 768px) {
      .home-legendary__inner {
        padding: 24px 16px;
        border-radius: 20px;
      }
      
      .home-legendary__header h2 {
        font-size: 20px;
      }

      .home-legendary__header h2::before,
      .home-legendary__header h2::after {
        width: 16px;
      }

      .home-legendary__grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .legendary-card {
        min-height: 190px;
        padding: 20px 18px;
      }

      .legendary-card__title {
        font-size: 19px;
      }
    }

    /* Home Video & Education Section */
    .home-video {
      padding: 60px 0;
      background: linear-gradient(180deg, #ffffff 0%, #faf8f5 100%);
      position: relative;
      overflow: hidden;
    }

    .home-video__inner {
      background: rgba(255, 255, 255, 0.65);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 32px;
      padding: 48px;
      border: 1px solid rgba(216, 163, 74, 0.15);
      box-shadow: 0 15px 45px rgba(74, 62, 53, 0.05), 0 4px 12px rgba(216, 163, 74, 0.05);
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 40px;
      align-items: center;
    }

    .home-video__content {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .home-video__badge {
      align-self: flex-start;
      background: rgba(216, 163, 74, 0.1);
      color: #9a6f2e;
      border: 1px solid rgba(216, 163, 74, 0.25);
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      padding: 6px 14px;
      border-radius: 20px;
    }

    .home-video__title {
      font-size: 32px;
      line-height: 1.3;
      font-weight: 850;
      color: #3b2f27;
      margin: 0;
    }

    .home-video__title span {
      background: linear-gradient(135deg, #b08236 0%, #d8a34a 50%, #9a6f2e 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .home-video__desc {
      font-size: 16px;
      line-height: 1.6;
      color: #5c4e43;
      margin: 0;
      font-weight: 500;
    }

    .home-video__features {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-top: 8px;
    }

    .home-video__feature-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14.5px;
      font-weight: 700;
      color: #4a3e35;
    }

    .home-video__feature-icon {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: rgba(216, 163, 74, 0.12);
      color: #d8a34a;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
    }

    .home-video__action {
      margin-top: 10px;
      align-self: flex-start;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #e6bd73 0%, #d8a34a 100%);
      color: #ffffff;
      padding: 14px 28px;
      border-radius: 14px;
      font-weight: 700;
      text-decoration: none;
      box-shadow: 0 8px 24px rgba(216, 163, 74, 0.3);
      transition: all 0.3s ease;
    }

    .home-video__action:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(216, 163, 74, 0.45);
      color: #ffffff;
      text-decoration: none;
    }

    /* Video Player Container */
    .home-video__player-wrapper {
      position: relative;
      border-radius: 24px;
      overflow: hidden;
      aspect-ratio: 16 / 9;
      background: #000;
      box-shadow: 0 20px 40px rgba(58, 28, 19, 0.15), 0 0 0 1px rgba(216, 163, 74, 0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .home-video__player-wrapper:hover {
      transform: scale(1.01);
      box-shadow: 0 25px 50px rgba(58, 28, 19, 0.22), 0 0 15px rgba(216, 163, 74, 0.3);
    }

    .home-video__facade {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 5;
    }

    .home-video__thumbnail {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.8s cubic-bezier(0.25, 1, 0.5, 1);
    }

    .home-video__facade:hover .home-video__thumbnail {
      transform: scale(1.05);
    }

    .home-video__overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.4) 100%);
      transition: opacity 0.3s ease;
    }

    .home-video__facade:hover .home-video__overlay {
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.15) 0%, rgba(0, 0, 0, 0.5) 100%);
    }

    /* Glowing Pulsing Play Button */
    .home-video__play-btn {
      position: relative;
      z-index: 10;
      width: 76px;
      height: 76px;
      border-radius: 50%;
      background: linear-gradient(135deg, #e6bd73 0%, #d8a34a 50%, #9a6f2e 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 24px;
      box-shadow: 0 10px 30px rgba(216, 163, 74, 0.5);
      border: 3px solid rgba(255, 255, 255, 0.9);
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .home-video__play-btn svg {
      width: 24px;
      height: 24px;
      fill: currentColor;
      transform: translateX(2px);
      transition: transform 0.3s ease;
    }

    .home-video__play-btn::before {
      content: '';
      position: absolute;
      top: -6px;
      left: -6px;
      right: -6px;
      bottom: -6px;
      border-radius: 50%;
      border: 1.5px solid rgba(216, 163, 74, 0.5);
      animation: homeVideoPulse 2s infinite;
      opacity: 0.8;
    }

    .home-video__play-btn::after {
      content: '';
      position: absolute;
      top: -12px;
      left: -12px;
      right: -12px;
      bottom: -12px;
      border-radius: 50%;
      border: 1px solid rgba(216, 163, 74, 0.25);
      animation: homeVideoPulse 2s infinite 0.6s;
      opacity: 0.5;
    }

    .home-video__facade:hover .home-video__play-btn {
      transform: scale(1.1);
      box-shadow: 0 15px 40px rgba(216, 163, 74, 0.7);
      background: linear-gradient(135deg, #ffffff 0%, #e6bd73 100%);
      color: #9a6f2e;
      border-color: #ffffff;
    }

    .home-video__facade:hover .home-video__play-btn svg {
      transform: translateX(2px) scale(1.1);
    }

    .home-video__iframe {
      width: 100%;
      height: 100%;
      border: none;
    }

    @keyframes homeVideoPulse {
      0% {
        transform: scale(1);
        opacity: 0.8;
      }
      70% {
        transform: scale(1.15);
        opacity: 0;
      }
      100% {
        transform: scale(1.15);
        opacity: 0;
      }
    }

    /* Responsive styling */
    @media (max-width: 991px) {
      .home-video__inner {
        grid-template-columns: 1fr;
        padding: 36px 24px;
        gap: 30px;
        border-radius: 24px;
      }

      .home-video__title {
        font-size: 26px;
      }
    }

    @media (max-width: 576px) {
      .home-video {
        padding: 40px 0;
      }

      .home-video__inner {
        padding: 24px 16px;
        gap: 24px;
        border-radius: 20px;
      }

      .home-video__title {
        font-size: 22px;
      }

      .home-video__desc {
        font-size: 14.5px;
      }

      .home-video__features {
        grid-template-columns: 1fr;
        gap: 12px;
      }

      .home-video__play-btn {
        width: 60px;
        height: 60px;
        font-size: 18px;
      }
      
      .home-video__play-btn svg {
        width: 18px;
        height: 18px;
      }
    }

    /* ===== HOME SECTION SLIDERS ===== */
    .home-number-group__slider {
      position: relative;
      padding: 0 52px;
    }

    .home-slider__arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 10;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.15);
      box-shadow: 0 4px 12px rgba(45, 33, 24, 0.12);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #3b2f27;
      transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.2s;
      padding: 0;
    }

    .home-slider__arrow--prev { left: 0; }
    .home-slider__arrow--next { right: 0; }

    .home-slider__arrow:hover:not(:disabled) {
      background: #d8a34a;
      color: #fff;
      border-color: #d8a34a;
      box-shadow: 0 6px 16px rgba(216, 163, 74, 0.35);
      transform: translateY(-50%) scale(1.05);
    }

    .home-slider__arrow:disabled {
      opacity: 0.25;
      cursor: default;
      pointer-events: none;
    }

    @media (max-width: 768px) {
      .home-number-group__slider {
        padding: 0 12px;
      }
      .home-slider__arrow {
        width: 32px;
        height: 32px;
        z-index: 100 !important;
        background: rgba(255, 255, 255, 0.88) !important;
        backdrop-filter: blur(6px) !important;
        -webkit-backdrop-filter: blur(6px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
      }
      .home-slider__arrow--prev {
        left: -4px !important;
      }
      .home-slider__arrow--next {
        right: -4px !important;
      }
    }

    /* ===== HOME ARTICLES SECTION ===== */
    .home-articles {
      padding: 0 0 48px;
    }

    .home-articles__inner {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 28px;
      padding: 32px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 4px 24px rgba(45, 33, 24, 0.08);
    }

    .home-articles__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      gap: 16px;
    }

    .home-articles__header-left h2 {
      font-size: 20px;
      font-weight: 800;
      color: #3b2f27;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .home-articles__header-left h2::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 20px;
      background: linear-gradient(to bottom, #d8a34a, #9a6f2e);
      border-radius: 2px;
      flex-shrink: 0;
    }

    .home-articles__header-left p {
      font-size: 14px;
      color: #8a7a6c;
      padding-left: 12px;
    }

    .home-articles__view-all {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 13.5px;
      font-weight: 700;
      color: #9a6f2e;
      text-decoration: none;
      border: 1.5px solid rgba(216, 163, 74, 0.35);
      border-radius: 10px;
      padding: 7px 14px;
      white-space: nowrap;
      flex-shrink: 0;
      transition: all 0.2s ease;
    }

    .home-articles__view-all:hover {
      background: rgba(216, 163, 74, 0.1);
      border-color: #d8a34a;
      color: #9a6f2e;
      text-decoration: none;
    }

    .home-articles__grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }

    .home-article-card {
      display: flex;
      flex-direction: column;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.08);
      border-radius: 16px;
      overflow: hidden;
      text-decoration: none;
      color: inherit;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .home-article-card:hover {
      border-color: rgba(216, 163, 74, 0.4);
      box-shadow: 0 6px 20px rgba(216, 163, 74, 0.15);
      transform: translateY(-2px);
      text-decoration: none;
      color: inherit;
    }

    .home-article-card__image {
      width: 100%;
      aspect-ratio: 16 / 9;
      object-fit: cover;
      display: block;
      background: #f3ede4;
    }

    .home-article-card__image-placeholder {
      width: 100%;
      aspect-ratio: 16 / 9;
      background: linear-gradient(135deg, #f9f6f1, #f0e8db);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
    }

    .home-article-card__body {
      flex: 1;
      display: flex;
      flex-direction: column;
      padding: 16px;
      gap: 6px;
    }

    .home-article-card__date {
      font-size: 11.5px;
      font-weight: 600;
      color: #b09070;
      letter-spacing: 0.02em;
    }

    .home-article-card__title {
      font-size: 14.5px;
      font-weight: 700;
      color: #3b2f27;
      line-height: 1.45;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .home-article-card__excerpt {
      font-size: 13px;
      color: #7a6c62;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      flex: 1;
    }

    .home-article-card__footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      margin-top: 6px;
      padding-top: 10px;
      border-top: 1px solid rgba(73, 61, 52, 0.06);
    }

    .home-article-card__read-more {
      font-size: 12.5px;
      font-weight: 700;
      color: #9a6f2e;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    @media (max-width: 900px) {
      .home-articles__grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 600px) {
      .home-articles {
        padding-bottom: 32px;
      }
      .home-articles__inner {
        padding: 20px 16px;
        border-radius: 20px;
      }
      .home-articles__grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }
    }
  </style>

  <!-- Hero Section -->
  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-media" aria-hidden="true">
      <picture>
        <source
          type="image/webp"
          media="(max-width: 768px)"
          srcset="{{ $homeBannerMobWebp }}"
        />
        <source
          type="image/webp"
          srcset="{{ $homeBannerWebp }}"
        />
        <source
          type="image/jpeg"
          media="(max-width: 768px)"
          srcset="{{ $homeBannerMobJpg }}"
        />
        <img
          class="hero-media__image"
          src="{{ $homeBannerUrl }}"
          alt="เบอร์มงคล Supernumber - เปลี่ยนเบอร์เปลี่ยนชีวิต"
          width="1920"
          height="450"
          fetchpriority="high"
          decoding="async"
        />
      </picture>
    </div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <div class="hero-left">
        <h1 class="hero-title" id="hero-title">
          Supernumber ศูนย์รวมเบอร์มงคลอันดับ 1
        </h1>
        <p class="hero-kicker">แค่เปลี่ยนเบอร์ชีวิตคุณก็เปลี่ยน</p>
        <p class="hero-subtitle">
          วิเคราะห์เบอร์มือถือฟรี ช่วยเสริมพลังให้ทุกก้าวสำคัญเพิ่มโอกาสและปลดล็อกเส้นทางสำเร็จ
        </p>
        <form class="hero-form" action="{{ route('evaluate') }}" method="get">
          <label class="hero-label" for="phone">กรอกเบอร์มือถือ</label>
          <div class="hero-input">
            <input
              id="phone"
              name="phone"
              type="tel"
              inputmode="numeric"
              autocomplete="tel-national"
              placeholder="0xx123456"
              pattern="[0-9]{10}"
              minlength="10"
              maxlength="10"
              value="{{ old('phone') }}"
              aria-describedby="phone-help{{ $errors->has('phone') ? ' phone-error' : '' }}"
              @error('phone') aria-invalid="true" @enderror
              required
            />
            <button type="submit">วิเคราะห์</button>
          </div>

          @error('phone')
            <p class="hero-error" id="phone-error">{{ $message }}</p>
          @enderror
        </form>
      </div>
    </div>
  </section>

  <!-- Search Section -->
  <section class="home-search" aria-labelledby="home-search-title">
    <div class="container container--narrow">
      <div class="home-filter">
        <div class="home-filter__header">
          <h2 id="home-search-title">ค้นหาเบอร์มงคล</h2>
          <p>ระบุตำแหน่งหรือชุดตัวเลขที่คุณต้องการ เพื่อความสำเร็จที่มากกว่า</p>
        </div>

        <form class="home-filter__form" action="{{ route('numbers.index') }}" method="get">
          <div class="home-filter__main">
            <!-- Sequence Search -->
            <div class="home-filter__group">
              <label class="home-filter__label" for="home-search-sequence">ค้นหาจากชุดตัวเลข</label>
              <div class="home-filter__input-wrapper">
                <i class="icon-search-small"></i>
                <input
                  id="home-search-sequence"
                  class="home-filter__input"
                  type="text"
                  name="q"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  value="{{ $search }}"
                  placeholder="เช่น 629"
                />
              </div>
            </div>

            <div class="home-filter__orb" role="separator" aria-label="หรือ"><span aria-hidden="true">หรือ</span></div>

            <div class="home-filter__group">
              <label class="home-filter__label" for="home-pos-prefix">ค้นหาตามตำแหน่ง</label>
              <div class="home-filter__position-row">
                <input
                  id="home-pos-prefix"
                  class="home-filter__pos-prefix"
                  type="text"
                  name="prefix"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="3"
                  value="{{ request('prefix') }}"
                  placeholder="0XX"
                />
                <span class="home-filter__pos-sep"></span>
                <div class="home-filter__pos-digits">
                  @foreach (range(4, 6) as $position)
                    <input
                      class="home-filter__pos-input"
                      type="text"
                      name="p{{ $position }}"
                      inputmode="numeric"
                      pattern="[0-9]*"
                      maxlength="1"
                      value="{{ request("p{$position}") }}"
                      aria-label="ตำแหน่ง {{ $position }}"
                    />
                  @endforeach
                  <span class="home-filter__pos-sep"></span>
                  @foreach (range(7, 10) as $position)
                    <input
                      class="home-filter__pos-input"
                      type="text"
                      name="p{{ $position }}"
                      inputmode="numeric"
                      pattern="[0-9]*"
                      maxlength="1"
                      value="{{ request("p{$position}") }}"
                      aria-label="ตำแหน่ง {{ $position }}"
                    />
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          <div class="home-filter__footer">
            <div class="home-filter__footer-controls">
              <div class="home-filter__select-wrapper">
                <label>ประเภทเบอร์</label>
                <select id="home-service-type" name="service_type">
                  <option value="">ทั้งหมด</option>
                  <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)>รายเดือน</option>
                  <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID)>เติมเงิน</option>
                </select>
              </div>
              <div class="home-filter__select-wrapper">
                <label>โปรโมชั่น / ราคาเบอร์</label>
                <select id="home-plan" name="plan">
                  <option value="">ทั้งหมด</option>
                  @foreach ($plans as $plan)
                    <option value="{{ $plan['value'] }}" @selected($selectedPlan === $plan['value'])>{{ $plan['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <button class="home-filter__submit" type="submit">ค้นหาเบอร์</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Category Topics Section -->
  <section class="home-topics" aria-labelledby="home-topics-title">
    <div class="container container--narrow">
      <div class="home-topics__inner">
        <div class="home-topics__header">
          <h2 id="home-topics-title">ค้นหาตามหมวดหมู่มงคล</h2>
          <p>เลือกหมวดที่คุณต้องการเสริมดวง</p>
        </div>
        <div class="home-topics__grid">
          @foreach (\App\Models\PhoneNumber::TOPIC_ICON_MAP as $topic => $icon)
            <a class="home-topic-card" href="{{ route('numbers.index', ['topic' => $topic]) }}">
              <div class="home-topic-card__icon">{{ $icon }}</div>
              <div class="home-topic-card__text">
                <div class="home-topic-card__name">{{ $topic }}</div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  <!-- Legendary Numbers Section (Dragon & Swan) -->
  <section class="home-legendary" style="display: none;" aria-labelledby="home-legendary-title">
    <div class="container container--narrow">
      <div class="home-legendary__inner">
        <div class="home-legendary__header">
          <h2 id="home-legendary-title">กลุ่มเบอร์ระดับตำนาน</h2>
          <p>เสริมพลังอำนาจ บารมี และเสน่ห์ทางการเงินขั้นสุดให้กับชีวิตคุณ</p>
        </div>
        <div class="home-legendary__grid">
          <!-- Dragon Numbers Card -->
          <a class="legendary-card legendary-card--dragon" href="{{ route('numbers.index', ['q' => '789']) }}">
            <div class="legendary-card__content">
              <div class="legendary-card__badge-row">
                <span class="legendary-card__badge">เบอร์มังกร</span>
                <div class="legendary-card__icon">🐉</div>
              </div>
              <h3 class="legendary-card__title">เบอร์มังกร (เลข 789)</h3>
              <p class="legendary-card__desc">เบอร์แห่งอำนาจ บารมี ความมั่งคั่งร่ำรวย ดึงดูดเงินก้อนโตและโอกาสทางธุรกิจขนาดใหญ่ เหมาะสำหรับผู้บริหาร เจ้าของกิจการ หรือผู้ต้องการความก้าวหน้าแบบก้าวกระโดด</p>
              <div class="legendary-card__numbers-preview">
                <span class="legendary-card__number-pill">789</span>
                <span class="legendary-card__number-pill">782</span>
                <span class="legendary-card__number-pill">879</span>
              </div>
            </div>
            <div class="legendary-card__action">
              <span>ค้นหาเบอร์มังกรทั้งหมด</span>
              <span class="legendary-card__arrow">→</span>
            </div>
          </a>

          <!-- Swan Numbers Card -->
          <a class="legendary-card legendary-card--swan" href="{{ route('numbers.index', ['q' => '289']) }}">
            <div class="legendary-card__content">
              <div class="legendary-card__badge-row">
                <span class="legendary-card__badge">เบอร์หงส์</span>
                <div class="legendary-card__icon">🦢</div>
              </div>
              <h3 class="legendary-card__title">เบอร์หงส์ (เลข 289)</h3>
              <p class="legendary-card__desc">เบอร์แห่งเสน่ห์ เมตตามหานิยม เงินทองไหลมาไม่ขาดสายและการอุปถัมภ์ค้ำชูที่ดีเยี่ยม เหมาะสำหรับนักขาย นักเจรจา เจ้าของธุรกิจร้านค้า หรือผู้ที่ทำงานประสานงานกับผู้คน</p>
              <div class="legendary-card__numbers-preview">
                <span class="legendary-card__number-pill">289</span>
                <span class="legendary-card__number-pill">282</span>
                <span class="legendary-card__number-pill">828</span>
              </div>
            </div>
            <div class="legendary-card__action">
              <span>ค้นหาเบอร์หงส์ทั้งหมด</span>
              <span class="legendary-card__arrow">→</span>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="numbers" aria-labelledby="numbers-title">
    @php
      $pageSize = 8;
      $maxItems = 80;
      $buildHomePayload = function ($numbers) use ($maxItems) {
          return $numbers->take($maxItems)->map(function ($number) {
              return [
                  'phone_number' => $number->phone_number,
                  'formatted_number' => $number->formatted_number,
                  'network_code' => $number->network_code,
                  'network_label' => $number->network_label,
                  'service_type_label' => $number->service_type_label,
                  'payment_label' => $number->payment_label,
                  'initial_payment_label' => $number->initial_payment_label,
                  'initial_payment_html' => $number->initial_payment_html,
                  'is_postpaid' => $number->is_postpaid,
                  'supported_topic_icons' => $number->supported_topic_icons,
                  'good_number_url' => route('evaluate', ['phone' => $number->phone_number]),
              ];
          })->values();
      };
      $prepaidPayload = $buildHomePayload($prepaidNumbers);
      $postpaidPayload = $buildHomePayload($postpaidNumbers);
      $initialPrepaidNumbers = $prepaidPayload->take($pageSize);
      $initialPostpaidNumbers = $postpaidPayload->take($pageSize);
      $hasHomeNumbers = $prepaidPayload->isNotEmpty() || $postpaidPayload->isNotEmpty();
    @endphp
    <div class="container">
      <div class="section-title numbers-catalog-title">
        <div class="numbers-catalog-title__content">
          <!-- <h2 id="numbers-title">เบอร์มงคลชีวิต</h2>
          <p>เบอร์มงคลที่คัดสรรมาเพื่อคุณ</p> -->
        </div>
        @if ($hasHomeNumbers)
          <div class="numbers-view-toggle" id="home-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผลหน้าแรก">
            <button class="numbers-view-toggle__button" type="button" data-view="list" aria-pressed="false">รายการ</button>
            <button class="numbers-view-toggle__button is-active" type="button" data-view="grid" aria-pressed="true">ตาราง</button>
          </div>
        @endif
      </div>

      @if ($hasHomeNumbers)
        <div class="home-number-groups" id="home-number-groups" data-view="grid">
          @if ($prepaidPayload->isNotEmpty())
            <section class="home-number-group home-number-group--prepaid" id="home-prepaid-section">
              <div class="home-number-group__head">
                <div class="home-number-group__copy">
                  <h3 class="home-number-group__title">เบอร์เติมเงินพร้อมใช้</h3>
                  <p class="home-number-group__hint">เบอร์เติมเงินสามารถย้ายค่ายได้</p>
                </div>
              </div>
              <div class="home-number-group__slider">
                <button class="home-slider__arrow home-slider__arrow--prev" id="home-prepaid-prev" type="button" aria-label="ก่อนหน้า" disabled>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="card-grid home-card-grid listing-card-grid" id="home-prepaid-grid" data-view="grid">
                @foreach ($initialPrepaidNumbers as $number)
                  <article class="number-card number-card--listing number-card--home">
                    <div class="card-left-group">
                      <div class="card-top">{{ $number['formatted_number'] }}</div>

                    @if (! empty($number['supported_topic_icons']))
                      @php
                        $topicIcons = collect($number['supported_topic_icons']);
                        $visibleTopicIcons = $topicIcons->take(4);
                        $hasMoreTopicIcons = $topicIcons->count() > 4;
                      @endphp
                      <div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">
                        @foreach ($visibleTopicIcons as $topic)
                          <span class="card-topic-icon" title="{{ $topic['topic'] }}" aria-label="{{ $topic['topic'] }}">{{ $topic['icon'] }}</span>
                        @endforeach
                        @if ($hasMoreTopicIcons)
                          <span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดที่ช่วยเพิ่มเติม">+</span>
                        @endif
                      </div>
                    @endif
                    </div>
                    <div class="card-body">
                      <div class="card-meta-stack">
                        <span class="card-tier card-tier--network"><span class="card-network-main" data-network="{{ strtolower($number['network_code']) }}">{{ $number['network_label'] }}</span><span class="card-network-suffix">{{ $number['service_type_label'] }}</span></span>
                        @if (! $number['is_postpaid'])
                          <span class="card-meta-plan">{{ $number['payment_label'] }}</span>
                        @endif
                        @if ($number['is_postpaid'])
                          <span class="card-meta-price">{!! $number['initial_payment_html'] !!}</span>
                        @endif
                      </div>
                    </div>
                    <a class="card-btn card-btn--buy" href="{{ $number['good_number_url'] }}">สั่งซื้อ</a>
                  </article>
                @endforeach
                </div>
                <button class="home-slider__arrow home-slider__arrow--next" id="home-prepaid-next" type="button" aria-label="ถัดไป">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
              </div>
            </section>
          @endif

          @if ($postpaidPayload->isNotEmpty())
            <section class="home-number-group home-number-group--postpaid" id="home-postpaid-section">
              <div class="home-number-group__head">
                <div class="home-number-group__copy">
                  <h3 class="home-number-group__title">เบอร์รายเดือนแนะนำ</h3>
                  <p class="home-number-group__hint"> สัญญา 12 เดือน</p>
                </div>
              </div>
              <div class="home-number-group__slider">
                <button class="home-slider__arrow home-slider__arrow--prev" id="home-postpaid-prev" type="button" aria-label="ก่อนหน้า" disabled>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="card-grid home-card-grid listing-card-grid" id="home-postpaid-grid" data-view="grid">
                @foreach ($initialPostpaidNumbers as $number)
                  <article class="number-card number-card--listing number-card--home">
                    <div class="card-left-group">
                      <div class="card-top">{{ $number['formatted_number'] }}</div>
                      @if (! empty($number['supported_topic_icons']))
                        @php
                          $topicIcons = collect($number['supported_topic_icons']);
                          $visibleTopicIcons = $topicIcons->take(4);
                          $hasMoreTopicIcons = $topicIcons->count() > 4;
                        @endphp
                        <div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">
                          @foreach ($visibleTopicIcons as $topic)
                            <span class="card-topic-icon" title="{{ $topic['topic'] }}" aria-label="{{ $topic['topic'] }}">{{ $topic['icon'] }}</span>
                          @endforeach
                          @if ($hasMoreTopicIcons)
                            <span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดที่ช่วยเพิ่มเติม">+</span>
                          @endif
                        </div>
                      @endif
                    </div>
                    <div class="card-body">
                      <div class="card-meta-stack">
                        <span class="card-tier card-tier--network"><span class="card-network-main" data-network="{{ strtolower($number['network_code']) }}">{{ $number['network_label'] }}</span><span class="card-network-suffix">{{ $number['service_type_label'] }}</span></span>
                        @if (! $number['is_postpaid'])
                          <span class="card-meta-plan"><strong>ราคา {{ $number['payment_label'] }}</strong></span>
                        @endif
                        @if ($number['is_postpaid'])
                          <span class="card-meta-price">{!! $number['initial_payment_html'] !!}</span>
                        @endif
                      </div>
                    </div>
                    <a class="card-btn card-btn--buy" href="{{ $number['good_number_url'] }}">สั่งซื้อ</a>
                  </article>
                @endforeach
                </div>
                <button class="home-slider__arrow home-slider__arrow--next" id="home-postpaid-next" type="button" aria-label="ถัดไป">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
              </div>
            </section>
          @endif
        </div>

      @else
        <p class="numbers-empty">ยังไม่มีเบอร์พร้อมขายในระบบตอนนี้</p>
      @endif
    </div>
  </section>

  <!-- Educational Video Section -->
  <section class="home-video" aria-labelledby="home-video-title">
    <div class="container container--narrow">
      <div class="home-video__inner">
        <div class="home-video__content">
          <span class="home-video__badge">SUPERNUMBER ORIGINAL</span>
          <h2 class="home-video__title" id="home-video-title">
            เมื่อ <span>"ก๊อตจิ เทยเที่ยวไทย"</span> บุกพิสูจน์พลังตัวเลขเปลี่ยนชีวิต!
          </h2>
          <p class="home-video__desc">
            ร่วมฟังประสบการณ์จริงจาก "คุณก๊อตจิ-ทัชชกร" ที่บุกเข้ามาแชร์ความเชื่อในเรื่องศาสตร์ตัวเลขและการเปลี่ยนเบอร์มงคลกับ พี่จิมมี่ Supernumber เปลี่ยนแล้วชีวิตปัง การงานรุ่งเรือง และดวงชะตาเปลี่ยนไปในทิศทางที่ดีขึ้นอย่างไรบ้าง!
          </p>
          <div class="home-video__features">
            <div class="home-video__feature-item">
              <span class="home-video__feature-icon">✓</span>
              <span>แชร์ประสบการณ์เปลี่ยนเบอร์จริง</span>
            </div>
            <div class="home-video__feature-item">
              <span class="home-video__feature-icon">✓</span>
              <span>ศาสตร์พลังตัวเลขจากพี่จิมมี่</span>
            </div>
            <div class="home-video__feature-item">
              <span class="home-video__feature-icon">✓</span>
              <span>เจาะลึกอิทธิพลตัวเลขส่งเสริมชีวิต</span>
            </div>
            <div class="home-video__feature-item">
              <span class="home-video__feature-icon">✓</span>
              <span>แนวทางการเลือกเบอร์ให้เหมาะกับคุณ</span>
            </div>
          </div>
          <a class="home-video__action" href="{{ route('videos') }}">
            <span>ดู SUPERNUMBER ORIGINAL ทั้งหมด</span>
            <span class="home-video__arrow">→</span>
          </a>
        </div>
        <div class="home-video__player-container">
          <div class="home-video__player-wrapper" id="youtube-player-wrapper">
            <div class="home-video__facade" id="youtube-facade" data-video-id="kiJy2x8AdUA">
              <picture>
                <source srcset="https://img.youtube.com/vi/kiJy2x8AdUA/maxresdefault.jpg" type="image/jpeg">
                <img class="home-video__thumbnail" src="https://img.youtube.com/vi/kiJy2x8AdUA/hqdefault.jpg" alt="วิดีโอสัมภาษณ์ ก๊อตจิ เทยเที่ยวไทย เปลี่ยนเบอร์มงคลกับ Supernumber" width="640" height="360" loading="lazy" />
              </picture>
              <div class="home-video__overlay"></div>
              <button class="home-video__play-btn" aria-label="เล่นวิดีโอ">
                <svg viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Video Schema Markup for Google SEO Rich Snippets -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "VideoObject",
      "name": "ก๊อตจิ เทยเที่ยวไทย บุกออฟฟิศมาเล่าถึงความเชื่อในเรื่องตัวเลขและประสบการณ์จริงกับพี่จิมมี่ - Supernumber EP.13",
      "description": "รับชมเรื่องราวและประสบการณ์จริงการเปลี่ยนเบอร์มงคลของ คุณก๊อตจิ เทยเที่ยวไทย กับ พี่จิมมี่ Supernumber ที่ช่วยเสริมพลังอำนาจ บารมี การงาน และชีวิตให้ดีขึ้นอย่างเหลือเชื่อ",
      "thumbnailUrl": [
        "https://img.youtube.com/vi/kiJy2x8AdUA/maxresdefault.jpg",
        "https://img.youtube.com/vi/kiJy2x8AdUA/hqdefault.jpg"
      ],
      "uploadDate": "2026-05-25T09:50:00+07:00",
      "embedUrl": "https://www.youtube.com/embed/kiJy2x8AdUA",
      "interactionStatistic": {
        "@@type": "InteractionCounter",
        "interactionType": { "@@type": "https://schema.org/WatchAction" },
        "userInteractionCount": "10500"
      }
    }
    </script>
  </section>

  @if ($homeArticles->isNotEmpty())
  <!-- Articles Preview Section -->
  <section class="home-articles" aria-labelledby="home-articles-title">
    <div class="container container--narrow">
      <div class="home-articles__inner">
        <div class="home-articles__header">
          <div class="home-articles__header-left">
            <h2 id="home-articles-title">บทความแนะนำ</h2>
            <p>ความรู้เรื่องตัวเลข ดูดวง และเบอร์มงคล</p>
          </div>
          <a class="home-articles__view-all" href="{{ route('articles.index') }}">
            ดูทั้งหมด <span>→</span>
          </a>
        </div>
        <div class="article-grid">
          @foreach ($homeArticles as $article)
            @php
              $listingCoverPath = $article->cover_image_landscape_path ?: null;
            @endphp
            <article class="article-card">
              @if ($listingCoverPath)
                <a href="{{ route('articles.show', $article->slug) }}" class="article-card__cover-link" aria-label="อ่านบทความ {{ $article->title }}">
                  <img src="{{ asset('storage/' . $listingCoverPath) }}" alt="{{ $article->title }}" class="article-card__cover" loading="lazy" />
                </a>
              @endif
              <div class="article-card__body">
                <p class="article-card__meta">{{ optional(optional($article->published_at)->timezone('Asia/Bangkok'))->format('d/m/Y') }}</p>
                <h3 class="article-card__title">
                  <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
                </h3>
                @if ($article->excerpt)
                  <p class="article-card__excerpt">{{ $article->excerpt }}</p>
                @endif
                <a href="{{ route('articles.show', $article->slug) }}" class="article-card__link">อ่านต่อ</a>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </div>
  </section>
  @endif

  @if ($hasHomeNumbers)
    <script>
      (() => {
        const prepaidNumbers  = @json($prepaidPayload);
        const postpaidNumbers = @json($postpaidPayload);
        const pageSize = {{ $pageSize }};

        const prepaidGrid     = document.getElementById("home-prepaid-grid");
        const prepaidSection  = document.getElementById("home-prepaid-section");
        const postpaidGrid    = document.getElementById("home-postpaid-grid");
        const postpaidSection = document.getElementById("home-postpaid-section");
        const groups = document.getElementById("home-number-groups");
        const toggle = document.getElementById("home-view-toggle");

        const prepaidPrevBtn  = document.getElementById("home-prepaid-prev");
        const prepaidNextBtn  = document.getElementById("home-prepaid-next");
        const postpaidPrevBtn = document.getElementById("home-postpaid-prev");
        const postpaidNextBtn = document.getElementById("home-postpaid-next");

        if ((!prepaidGrid && !postpaidGrid) || !toggle) return;

        const prepaidTotalPages  = Math.max(1, Math.ceil(prepaidNumbers.length / pageSize));
        const postpaidTotalPages = Math.max(1, Math.ceil(postpaidNumbers.length / pageSize));

        let prepaidPage  = 1;
        let postpaidPage = 1;

        const escapeHtml = (value) =>
          String(value ?? "").replace(/[&<>"']/g, (char) => {
            switch (char) {
              case "&": return "&amp;";
              case "<": return "&lt;";
              case ">": return "&gt;";
              case '"': return "&quot;";
              case "'": return "&#39;";
              default:  return char;
            }
          });

        const renderCard = (number) => `
          <article class="number-card number-card--listing number-card--home">
            <div class="card-left-group">
              <div class="card-top">${escapeHtml(number.formatted_number)}</div>
              ${Array.isArray(number.supported_topic_icons) && number.supported_topic_icons.length
                ? `<div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">${number.supported_topic_icons.slice(0, 4).map((t) => `<span class="card-topic-icon" title="${escapeHtml(t.topic)}" aria-label="${escapeHtml(t.topic)}">${escapeHtml(t.icon)}</span>`).join("")}${number.supported_topic_icons.length > 4 ? `<span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดเพิ่มเติม">+</span>` : ""}</div>`
                : ""}
            </div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main" data-network="${escapeHtml(String(number.network_code || "").toLowerCase())}">${escapeHtml(number.network_label)}</span><span class="card-network-suffix">${escapeHtml(number.service_type_label)}</span></span>
                ${!number.is_postpaid ? `<span class="card-meta-plan">${escapeHtml(number.payment_label)}</span>` : ""}
                ${number.is_postpaid  ? `<span class="card-meta-price">${number.initial_payment_html}</span>` : ""}
              </div>
            </div>
            <a class="card-btn card-btn--buy" href="${escapeHtml(number.good_number_url)}">สั่งซื้อ</a>
          </article>
        `;

        const renderSection = (grid, section, numbers, page, totalPages, prevBtn, nextBtn) => {
          const p     = Math.min(totalPages, Math.max(1, page));
          const start = (p - 1) * pageSize;
          const items = numbers.slice(start, start + pageSize);

          if (grid)    grid.innerHTML = items.map(renderCard).join("");
          if (section) section.hidden = items.length === 0;
          if (prevBtn) prevBtn.disabled = p <= 1;
          if (nextBtn) nextBtn.disabled = p >= totalPages;

          return p;
        };

        const renderPrepaid  = (page) => { prepaidPage  = renderSection(prepaidGrid,  prepaidSection,  prepaidNumbers,  page, prepaidTotalPages,  prepaidPrevBtn,  prepaidNextBtn);  };
        const renderPostpaid = (page) => { postpaidPage = renderSection(postpaidGrid, postpaidSection, postpaidNumbers, page, postpaidTotalPages, postpaidPrevBtn, postpaidNextBtn); };

        if (prepaidPrevBtn)  prepaidPrevBtn.addEventListener("click",  () => renderPrepaid(prepaidPage - 1));
        if (prepaidNextBtn)  prepaidNextBtn.addEventListener("click",  () => renderPrepaid(prepaidPage + 1));
        if (postpaidPrevBtn) postpaidPrevBtn.addEventListener("click", () => renderPostpaid(postpaidPage - 1));
        if (postpaidNextBtn) postpaidNextBtn.addEventListener("click", () => renderPostpaid(postpaidPage + 1));

        const buttons = Array.from(toggle.querySelectorAll("[data-view]"));
        const grids   = [prepaidGrid, postpaidGrid].filter(Boolean);

        const applyView = (view) => {
          const v = view === "list" ? "list" : "grid";
          if (groups) groups.dataset.view = v;
          grids.forEach((g)   => { g.dataset.view = v; });
          buttons.forEach((b) => {
            const active = b.dataset.view === v;
            b.classList.toggle("is-active", active);
            b.setAttribute("aria-pressed", active ? "true" : "false");
          });
        };

        applyView("grid");

        toggle.addEventListener("click", (e) => {
          const t = e.target.closest("[data-view]");
          if (t) applyView(t.dataset.view);
        });

        renderPrepaid(1);
        renderPostpaid(1);
      })();
    </script>
  @endif

  <script>
    (() => {
      const facade = document.getElementById("youtube-facade");
      const wrapper = document.getElementById("youtube-player-wrapper");

      if (!facade || !wrapper) return;

      facade.addEventListener("click", () => {
        const videoId = facade.dataset.videoId;
        if (!videoId) return;

        // Create the iframe dynamically
        const iframe = document.createElement("iframe");
        iframe.className = "home-video__iframe";
        iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`;
        iframe.title = "YouTube video player";
        iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share";
        iframe.allowFullscreen = true;

        // Replace content of the wrapper with the iframe
        wrapper.innerHTML = "";
        wrapper.appendChild(iframe);
      });
    })();
  </script>

  <script>
    (() => {
      const phoneInput = document.getElementById("phone");

      if (!phoneInput) return;

      const syncPhoneValue = () => {
        const digits = (phoneInput.value || "").replace(/\D+/g, "").slice(0, 10);
        phoneInput.value = digits;
        phoneInput.setCustomValidity(
          digits.length === 10 ? "" : "กรุณากรอกเบอร์มือถือให้ครบ 10 หลัก"
        );
      };

      phoneInput.addEventListener("input", syncPhoneValue);
      phoneInput.addEventListener("blur", syncPhoneValue);
      phoneInput.addEventListener("invalid", syncPhoneValue);

      syncPhoneValue();
    })();
  </script>

  <script>
    (() => {
      const serviceTypeSelect = document.getElementById("home-service-type");
      const planSelect = document.getElementById("home-plan");
      const planOptionsByServiceType = @json($plansByServiceType);
      const serviceTypePostpaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID);
      const serviceTypePrepaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_PREPAID);
      const labels = {
        [serviceTypePostpaid]: "โปรรายเดือน",
        [serviceTypePrepaid]: "ราคาเบอร์",
        all: "โปรโมชั่น / ราคาเบอร์",
      };

      if (!serviceTypeSelect || !planSelect) return;

      const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (char) => {
          switch (char) {
            case "&": return "&amp;";
            case "<": return "&lt;";
            case ">": return "&gt;";
            case '"': return "&quot;";
            case "'": return "&#39;";
            default: return char;
          }
        });

      const resolveOptionKey = (value) => {
        if (value === serviceTypePostpaid || value === serviceTypePrepaid) {
          return value;
        }
        return "all";
      };

      const renderPlanOptions = (serviceType) => {
        const optionKey = resolveOptionKey(serviceType);
        const options = planOptionsByServiceType[optionKey] ?? [];
        const currentValue = planSelect.value;
        const placeholderLabel = labels[optionKey] || labels.all;

        const renderedOptions = options
          .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
          .join("");

        planSelect.innerHTML = `<option value="">${placeholderLabel}</option>${renderedOptions}`;

        const hasCurrentValue = options.some((option) => option.value === currentValue);
        planSelect.value = hasCurrentValue ? currentValue : "";
        
        // Disable if "all" is selected
        planSelect.disabled = (optionKey === "all");
        planSelect.style.opacity = (optionKey === "all") ? "0.6" : "1";
        planSelect.style.cursor = (optionKey === "all") ? "not-allowed" : "pointer";

        // Update label above the select if needed
        const labelEl = planSelect.previousElementSibling;
        if (labelEl && labelEl.tagName === 'LABEL') {
            labelEl.textContent = placeholderLabel;
        }
      };

      renderPlanOptions(serviceTypeSelect.value);

      serviceTypeSelect.addEventListener("change", () => {
        renderPlanOptions(serviceTypeSelect.value);
      });
    })();
  </script>
@endsection
