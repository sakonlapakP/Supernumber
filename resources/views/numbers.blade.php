@extends('layouts.app')

@php
  $seoTitle = 'ขายเบอร์มงคลออนไลน์ เบอร์มงคลราคาถูก';
  if ($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID) {
      $seoTitle = 'ขายเบอร์มงคล แบบเติมเงิน ราคาพิเศษ';
  } elseif ($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID) {
      $seoTitle = 'ขายเบอร์มงคล แบบรายเดือน โปรโมชั่นคุ้มค่า';
  }
  if ($selectedPlan) {
      $seoTitle .= ' ' . $selectedPlan;
  }
  if ($search) {
      $seoTitle .= ' ชุดเลข ' . $search;
  }
  $seoTitle .= ' | Supernumber';
@endphp

@section('title', $seoTitle)
@section('meta_description', 'รวมเบอร์พร้อมขายทั้งหมด ค้นหาเบอร์ตามตำแหน่งที่ต้องการและเลือกโปรโมชั่นที่เหมาะกับคุณ')
@section('og_title', $seoTitle)
@section('og_description', 'รวมเบอร์พร้อมขายทั้งหมด ค้นหาเบอร์ตามตำแหน่งที่ต้องการและเลือกโปรโมชั่นที่เหมาะกับคุณ')
@section('canonical', url('/numbers'))
@section('og_url', url('/numbers'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))
@section('body_class', 'numbers-scale-soft')

@section('seo_schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ItemList",
  "itemListElement": [
    @if(isset($numbers))
    @foreach($numbers->take(10) as $index => $number)
    {
      "@@type": "ListItem",
      "position": {{ $index + 1 }},
      "url": "{{ url('/numbers') }}",
      "name": "เบอร์มงคล {{ $number->phone }}"
    }{{ !$loop->last ? ',' : '' }}
    @endforeach
    @endif
  ]
}
</script>
@endsection

@section('content')
  <style>
    /* Redesigned Search Section Styling */
    .home-filter {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 40px;
      padding: 40px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 10px 40px rgba(45, 33, 24, 0.1), 0 2px 4px rgba(216, 163, 74, 0.1);
      margin-bottom: 40px;
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
      grid-template-columns: 1fr 1fr 1.5fr auto;
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
    }

    .home-filter__submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(32, 24, 18, 0.35);
      background: linear-gradient(135deg, #4d3e34, #2a2019);
    }

    @media (min-width: 992px) {
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"]:not(.is-default-split) {
        grid-template-columns: repeat(4, 240px) !important;
        justify-content: center !important;
        gap: 12px !important;
      }

      body.numbers-scale-soft .numbers-hero__text {
        max-width: 900px !important;
      }
      body.numbers-scale-soft .numbers-hero__text h1 {
        white-space: nowrap !important;
      }
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
      .home-filter {
        padding: 20px 15px;
        border-radius: 25px;
        margin-bottom: 30px;
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

      /* Match card number sizing and spacing to the Homepage layout */
      .numbers-catalog-grid {
        gap: 10px !important;
        padding: 0 5px !important;
      }

      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog {
        min-height: unset !important;
        padding: 10px 8px !important;
        width: 100% !important;
        max-width: none !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-top,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-top {
        font-size: clamp(15px, 4.8vw, 18px) !important;
        padding: 8px 6px !important;
        letter-spacing: 0.02em !important;
        margin: 0 !important;
        line-height: 1 !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-network-main,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-network-suffix,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-network-main,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-network-suffix {
        font-size: clamp(10px, 3.2vw, 12px) !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-meta-plan,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-meta-price,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-meta-plan *,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-meta-price *,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-meta-plan,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-meta-price,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-meta-plan *,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-meta-price * {
        font-size: clamp(11.5px, 3.5vw, 13.5px) !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--home .card-btn,
      body.numbers-scale-soft .numbers-catalog-grid[data-view="grid"] .number-card--catalog .card-btn {
        font-size: clamp(11.5px, 3.5vw, 13px) !important;
        min-height: 32px !important;
        padding: 0 8px !important;
        border-radius: 10px !important;
        margin-top: 6px !important;
      }
    }

    .topic-active-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #f9f6f1;
      border: 1.5px solid rgba(216, 163, 74, 0.4);
      border-radius: 24px;
      padding: 7px 14px 7px 10px;
      font-size: 14px;
      font-weight: 700;
      color: #3b2f27;
      margin-bottom: 12px;
    }

    .topic-active-badge__icon {
      font-size: 18px;
      line-height: 1;
    }

    .topic-active-badge__clear {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      background: rgba(73, 61, 52, 0.12);
      border-radius: 50%;
      font-size: 12px;
      color: #7a6c62;
      text-decoration: none;
      margin-left: 2px;
      flex-shrink: 0;
      transition: background 0.2s, color 0.2s;
    }

    .topic-active-badge__clear:hover {
      background: #d8a34a;
      color: white;
    }

    /* ===== NUMBERS TOPICS SECTION (HOMEPAGE DESIGN) ===== */
    .numbers-topics-box {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 28px;
      padding: 28px 28px 24px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 4px 24px rgba(45, 33, 24, 0.08);
      margin-bottom: 30px;
    }

    .numbers-topics__header {
      margin-bottom: 20px;
    }

    .numbers-topics__header h2 {
      font-size: 20px;
      font-weight: 800;
      color: #3b2f27;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .numbers-topics__header h2::before {
      content: '';
      display: inline-block;
      width: 4px;
      height: 20px;
      background: linear-gradient(to bottom, #d8a34a, #9a6f2e);
      border-radius: 2px;
      flex-shrink: 0;
    }

    .numbers-topics__header p {
      font-size: 14px;
      color: #8a7a6c;
      padding-left: 12px;
    }

    .numbers-topics__grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    .numbers-topic-card {
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

    .numbers-topic-card:hover {
      border-color: #d8a34a;
      box-shadow: 0 4px 16px rgba(216, 163, 74, 0.2);
      transform: translateY(-1px);
      text-decoration: none;
      color: inherit;
    }

    .numbers-topic-card.is-active {
      background: linear-gradient(135deg, #fffcf6, #fff6e0);
      border-color: #d8a34a;
      box-shadow: 0 4px 16px rgba(216, 163, 74, 0.25);
    }

    .numbers-topic-card.is-active .numbers-topic-card__icon {
      background: linear-gradient(135deg, #e4bd65, #f5d98a);
      border-color: #c3912f;
    }

    .numbers-topic-card.is-active .numbers-topic-card__arrow {
      color: #d8a34a;
      transform: translateX(2px);
    }

    .numbers-topic-card__icon {
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

    .numbers-topic-card__text {
      flex: 1;
      min-width: 0;
    }

    .numbers-topic-card__name {
      font-size: 13.5px;
      font-weight: 700;
      color: #3b2f27;
      line-height: 1.3;
    }

    .numbers-topic-card__arrow {
      font-size: 14px;
      color: #c5b09a;
      flex-shrink: 0;
      transition: color 0.2s, transform 0.2s;
    }

    .numbers-topic-card:hover .numbers-topic-card__arrow {
      color: #d8a34a;
      transform: translateX(2px);
    }

    @media (max-width: 820px) {
      .numbers-topics__grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 640px) {
      .numbers-topics-box {
        padding: 20px 16px;
        border-radius: 20px;
      }
      .numbers-topics__grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
      }
      .numbers-topic-card {
        padding: 10px 8px;
        gap: 6px;
      }
      .numbers-topic-card__icon {
        width: 30px;
        height: 30px;
        font-size: 16px;
      }
      .numbers-topic-card__name {
        font-size: 11.5px;
      }
      .numbers-topic-card__arrow {
        display: none;
      }
    }

    @media (max-width: 560px) {
      .numbers-topics__grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
      }
      .numbers-topic-card__name {
        font-size: 11px;
      }
    }

    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] {
      display: grid !important;
      grid-template-columns: 1fr !important;
      gap: 14px !important;
      padding: 0 !important;
      max-width: 820px !important;
      margin-inline: auto !important;
      width: 100% !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list].is-default-split {
      grid-template-columns: 1fr !important;
    }
    @media (max-width: 700px) {
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] {
        grid-template-columns: 1fr !important;
        max-width: 100% !important;
      }
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home {
      display: grid !important;
      grid-template-columns: auto 1fr auto !important;
      grid-template-areas: none !important;
      align-items: center !important;
      padding: 14px 20px !important;
      border-radius: 16px !important;
      min-height: 80px !important;
      height: auto !important;
      background: #fff !important;
      box-shadow: 0 10px 30px rgba(45, 33, 24, 0.08) !important;
      border: 1px solid rgba(0, 0, 0, 0.02) !important;
      margin: 0 !important;
      gap: 16px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-left-group {
      grid-area: auto !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: flex-start !important;
      gap: 4px !important;
      min-width: 140px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-top {
      font-size: 20px !important;
      font-weight: 800 !important;
      line-height: 1.2 !important;
      margin: 0 !important;
      padding: 0 !important;
      background: none !important;
      color: #1a1612 !important;
      box-shadow: none !important;
      white-space: nowrap !important;
      border-radius: 0 !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-topic-icons {
      margin: 0 !important;
      gap: 6px !important;
      display: flex !important;
      padding: 0 !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-topic-icon {
      width: 22px !important;
      height: 22px !important;
      font-size: 11px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-body {
      grid-area: auto !important;
      display: flex !important;
      justify-content: center !important;
      align-items: center !important;
      padding: 0 !important;
      margin: 0 !important;
      min-width: 0 !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-stack {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      gap: 4px !important;
      line-height: 1.2 !important;
      white-space: nowrap !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-tier--network {
      background: none !important;
      border: none !important;
      padding: 0 !important;
      margin: 0 !important;
      font-weight: 800 !important;
      font-size: 15px !important;
      line-height: 1.2 !important;
      display: flex !important;
      gap: 4px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-network-main,
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-network-suffix {
      font-size: 15px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-network-suffix {
      color: #2c2521 !important;
      -webkit-text-fill-color: #2c2521 !important;
      margin-left: 3px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-plan,
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-price {
      font-size: 15px !important;
      font-weight: 400 !important;
      color: #4b382a !important;
      line-height: 1.2 !important;
      margin: 2px 0 0 0 !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-plan *,
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-price * {
      font-weight: 400 !important;
      font-size: 15px !important;
    }
    body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-btn {
      grid-area: auto !important;
      width: 100px !important;
      min-height: 32px !important;
      height: 32px !important;
      font-size: 14px !important;
      font-weight: 800 !important;
      border-radius: 999px !important;
      background: #e1b155 !important;
      color: #4b382a !important;
      padding: 0 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      margin: 0 !important;
      text-decoration: none !important;
      flex-shrink: 0 !important;
    }

    /* Stacked Grid Layout on Mobile/Narrow screens (<= 600px) */
    @media (max-width: 600px) {
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home {
        grid-template-columns: 1fr auto !important;
        grid-template-areas: 
          "left btn"
          "body btn" !important;
        gap: 6px 12px !important;
        padding: 12px 14px !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-left-group {
        grid-area: left !important;
        min-width: unset !important;
        gap: 2px !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-top {
        font-size: clamp(16px, 4.8vw, 19px) !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-topic-icons {
        gap: 6px !important;
        margin-top: 2px !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-topic-icon {
        width: 18px !important;
        height: 18px !important;
        font-size: 10px !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-body {
        grid-area: body !important;
        justify-content: flex-start !important;
        align-items: flex-start !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-stack {
        align-items: flex-start !important;
        gap: 2px !important;
        white-space: normal !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-tier--network {
        font-size: clamp(12px, 3.5vw, 13.5px) !important;
        flex-wrap: wrap !important;
        gap: 2px 4px !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-network-main,
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-network-suffix {
        font-size: clamp(12px, 3.5vw, 13.5px) !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-network-suffix {
        margin-left: 0 !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-plan,
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-price {
        font-size: clamp(11px, 3.2vw, 12.5px) !important;
        margin: 0 !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-plan *,
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-meta-price * {
        font-size: clamp(11px, 3.2vw, 12.5px) !important;
      }
      body.numbers-scale-soft .numbers-catalog-grid[data-view=list] .number-card--home .card-btn {
        grid-area: btn !important;
        width: 80px !important;
        height: 34px !important;
        min-height: 34px !important;
        font-size: clamp(12px, 3.5vw, 13px) !important;
      }
    }

    /* ===== NUMBERS PAGE SLIDERS ===== */
    .numbers-slider-sections {
      display: flex;
      flex-direction: column;
      gap: 40px;
      margin-bottom: 8px;
    }

    .numbers-slider-section .home-number-group__head {
      margin-bottom: 20px;
    }

    .numbers-slider-section .home-number-group__title {
      font-size: 22px;
      color: #1a1612;
      font-weight: 800;
    }

    .numbers-slider-section .home-number-group__hint {
      font-size: 14px;
      color: #7a6c62;
    }

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
        padding: 0 38px;
      }
      .home-slider__arrow {
        width: 32px;
        height: 32px;
      }
    }

    /* ซ่อน card ใน slider (specificity เทียบเท่า cardNumber.css จึง override !important ได้) */
    body.numbers-scale-soft .numbers-catalog-grid .number-card--home.slider-hidden,
    body.numbers-scale-soft .numbers-catalog-grid .number-card--listing.slider-hidden {
      display: none !important;
    }
  </style>
  @php
    $selectedView = request('view') === 'list' ? 'list' : 'grid';
  @endphp
  <section class="numbers-hero" aria-labelledby="numbers-hero-title">
    <div class="numbers-hero-overlay"></div>
    <div class="container numbers-hero__content">
      <div class="numbers-hero__text">
        <h1 id="numbers-hero-title">คลังเบอร์มงคลคุณภาพ เลือกเบอร์ที่ใช่สำหรับคุณ</h1>
        <p class="hero-kicker">ค้นหาเบอร์มงคล</p>
        <p>เลือกเบอร์ที่ตรงใจจากคลังเบอร์คุณภาพ พร้อมตัวกรองที่ช่วยค้นหาได้ไวขึ้น</p>
      </div>
    </div>
  </section>

  <section class="numbers-catalog-page" aria-labelledby="numbers-catalog-title">
    <div class="container numbers-catalog-shell">
      <div class="numbers-catalog-toolbar">
        <form class="numbers-filter-form" action="{{ route('numbers.index') }}" method="get">
          <input id="numbers-view-input" type="hidden" name="view" value="{{ $selectedView }}">
          @foreach ($selectedTopics as $topic)
            <input type="hidden" name="topic[]" value="{{ $topic }}">
          @endforeach
          
          <div class="home-filter">
            <div class="home-filter__main">
              <!-- Sequence Search -->
              <div class="home-filter__group">
                <label class="home-filter__label" for="numbers-search-sequence">ค้นหาจากชุดตัวเลข</label>
                <div class="home-filter__input-wrapper">
                  <i class="icon-search-small"></i>
                  <input
                    id="numbers-search-sequence"
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

              <!-- Position Search -->
              <div class="home-filter__group">
                <label class="home-filter__label" for="numbers-prefix">ค้นหาตามตำแหน่ง</label>
                <div class="home-filter__position-row">
                  <input
                    id="numbers-prefix"
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
                  <select id="numbers-service-type" name="service_type">
                    <option value="">ทั้งหมด</option>
                    <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)>รายเดือน</option>
                    <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID)>เติมเงิน</option>
                  </select>
                </div>
                <div class="home-filter__select-wrapper">
                  <label>เครือข่าย</label>
                  <select id="numbers-network" name="network">
                    <option value="">ทั้งหมด</option>
                    <option value="{{ \App\Models\PhoneNumber::NETWORK_AIS }}" @selected($selectedNetwork === \App\Models\PhoneNumber::NETWORK_AIS)>AIS</option>
                    <option value="{{ \App\Models\PhoneNumber::NETWORK_TRUE_DTAC }}" @selected($selectedNetwork === \App\Models\PhoneNumber::NETWORK_TRUE_DTAC)>TRUE-DTAC</option>
                  </select>
                </div>
                <div class="home-filter__select-wrapper">
                  <label>โปรโมชั่น / ราคาเบอร์</label>
                  <select id="numbers-plan" name="plan">
                    <option value="">ทั้งหมด</option>
                    @foreach ($plans as $plan)
                      <option value="{{ $plan['value'] }}" @selected($selectedPlan === $plan['value'])>{{ $plan['label'] }}</option>
                    @endforeach
                  </select>
                </div>
                <button class="home-filter__submit" type="submit">ค้นหาเบอร์</button>
              </div>
            </div>
          </div>
        </form>
      </div>

      @if ($positionPattern)
        <p class="numbers-filter-hint">รูปแบบที่ค้นหา: {{ $positionPattern }}</p>
      @endif

      @php
        $baseParams = array_filter(request()->except('topic', 'page'));
        $buildNumbersTopicUrl = static function (array $params): string {
          $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
          $query = preg_replace('/topic%5B\d+%5D=/', 'topic%5B%5D=', $query);

          return route('numbers.index') . ($query ? '?' . $query : '');
        };
      @endphp
      <div class="numbers-topics-box">
        <div class="numbers-topics__header">
          <h2>เลือกหมวดที่เบอร์นี้ส่งเสริม</h2>
        </div>
        <nav class="numbers-topics__grid" aria-label="กรองตามหมวดหมู่มงคล">
          @foreach (\App\Models\PhoneNumber::TOPIC_ICON_MAP as $topic => $icon)
            @php
              $isTopicSelected = in_array($topic, $selectedTopics, true);
              $nextTopics = $isTopicSelected
                ? array_values(array_diff($selectedTopics, [$topic]))
                : array_values(array_unique([...$selectedTopics, $topic]));
              $topicParams = $baseParams;
              if ($nextTopics !== []) {
                $topicParams['topic'] = $nextTopics;
              }
            @endphp
            <a class="numbers-topic-card {{ $isTopicSelected ? 'is-active' : '' }}"
               href="{{ $buildNumbersTopicUrl($topicParams) }}"
               aria-pressed="{{ $isTopicSelected ? 'true' : 'false' }}">
              <div class="numbers-topic-card__icon">{{ $icon }}</div>
              <div class="numbers-topic-card__text">
                <div class="numbers-topic-card__name">{{ $topic }}</div>
              </div>

            </a>
          @endforeach
        </nav>
      </div>

      <div class="section-title numbers-catalog-title">
        <div class="numbers-catalog-title__content">
          @if ($selectedTopics !== [])
            <h2 id="numbers-catalog-title">กรองเบอร์ตามหมวด {{ implode(', ', $selectedTopics) }}</h2>
          @else
            <h2 id="numbers-catalog-title">เบอร์ทั้งหมด</h2>
          @endif
          @if (!$isDefaultSplitLayout)
            <p>
              แสดง
              {{ $numbers->count() ? $numbers->firstItem() . '-' . $numbers->lastItem() : '0' }}
              จาก {{ number_format($numbers->total()) }} เบอร์
            </p>
          @else
            <p>{{ number_format($numbers->total()) }} เบอร์พร้อมขาย</p>
          @endif
        </div>
        <div class="numbers-view-toggle" id="numbers-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผล">
          <button class="numbers-view-toggle__button {{ $selectedView === 'list' ? 'is-active' : '' }}" type="button" data-view="list" aria-pressed="{{ $selectedView === 'list' ? 'true' : 'false' }}">รายการ</button>
          <button class="numbers-view-toggle__button {{ $selectedView === 'grid' ? 'is-active' : '' }}" type="button" data-view="grid" aria-pressed="{{ $selectedView === 'grid' ? 'true' : 'false' }}">ตาราง</button>
        </div>
      </div>

      @if ($isDefaultSplitLayout)
        <div class="numbers-slider-sections" id="numbers-catalog-grid" data-view="{{ $selectedView }}">

          {{-- Prepaid slider section --}}
          @if ($defaultPrepaidNumbers->isNotEmpty())
            <section class="numbers-slider-section" id="numbers-prepaid-section">
              <div class="home-number-group__head">
                <div class="home-number-group__copy">
                  <h3 class="home-number-group__title">เบอร์เติมเงินพร้อมใช้</h3>
                  <p class="home-number-group__hint">เบอร์เติมเงินสามารถย้ายค่ายได้</p>
                </div>
              </div>
              <div class="home-number-group__slider">
                <button class="home-slider__arrow home-slider__arrow--prev" id="numbers-prepaid-prev" type="button" aria-label="ก่อนหน้า" disabled>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="numbers-catalog-grid listing-card-grid" id="numbers-prepaid-grid" data-view="{{ $selectedView }}">
                  @foreach ($defaultPrepaidNumbers as $number)
                    <article class="number-card number-card--listing number-card--home">
                      <div class="card-left-group">
                        <div class="card-top">{{ $number->formatted_number }}</div>
                        @if ($number->supported_topic_icons !== [])
                          @php
                            $topicIcons = collect($number->supported_topic_icons);
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
                          <span class="card-tier card-tier--network"><span class="card-network-main" data-network="{{ strtolower($number->network_code) }}">{{ $number->network_label }}</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                          @if ($number->is_prepaid)
                            <span class="card-meta-plan">{{ $number->payment_label }}</span>
                          @endif
                          @if ($number->is_postpaid)
                            <span class="card-meta-price">{!! $number->initial_payment_html !!}</span>
                          @endif
                        </div>
                      </div>
                      <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
                    </article>
                  @endforeach
                </div>
                <button class="home-slider__arrow home-slider__arrow--next" id="numbers-prepaid-next" type="button" aria-label="ถัดไป">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
              </div>
            </section>
          @endif

          {{-- Postpaid slider section --}}
          @if ($defaultPostpaidNumbers->isNotEmpty())
            <section class="numbers-slider-section" id="numbers-postpaid-section">
              <div class="home-number-group__head">
                <div class="home-number-group__copy">
                  <h3 class="home-number-group__title">เบอร์รายเดือนแนะนำ</h3>
                  <p class="home-number-group__hint">สัญญา 12 เดือน</p>
                </div>
              </div>
              <div class="home-number-group__slider">
                <button class="home-slider__arrow home-slider__arrow--prev" id="numbers-postpaid-prev" type="button" aria-label="ก่อนหน้า" disabled>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="numbers-catalog-grid listing-card-grid" id="numbers-postpaid-grid" data-view="{{ $selectedView }}">
                  @foreach ($defaultPostpaidNumbers as $number)
                    <article class="number-card number-card--listing number-card--home">
                      <div class="card-left-group">
                        <div class="card-top">{{ $number->formatted_number }}</div>
                        @if ($number->supported_topic_icons !== [])
                          @php
                            $topicIcons = collect($number->supported_topic_icons);
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
                          <span class="card-tier card-tier--network"><span class="card-network-main" data-network="{{ strtolower($number->network_code) }}">{{ $number->network_label }}</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                          @if ($number->is_prepaid)
                            <span class="card-meta-plan">{{ $number->payment_label }}</span>
                          @endif
                          @if ($number->is_postpaid)
                            <span class="card-meta-price">{!! $number->initial_payment_html !!}</span>
                          @endif
                        </div>
                      </div>
                      <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
                    </article>
                  @endforeach
                </div>
                <button class="home-slider__arrow home-slider__arrow--next" id="numbers-postpaid-next" type="button" aria-label="ถัดไป">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
              </div>
            </section>
          @endif

        </div>
      @else
        <div class="numbers-catalog-grid listing-card-grid" id="numbers-catalog-grid" data-view="{{ $selectedView }}">
          @forelse ($numbers as $number)
            <article class="number-card number-card--listing number-card--home">
              <div class="card-left-group">
                <div class="card-top">{{ $number->formatted_number }}</div>
                @if ($number->supported_topic_icons !== [])
                  @php
                    $topicIcons = collect($number->supported_topic_icons);
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
                  <span class="card-tier card-tier--network"><span class="card-network-main" data-network="{{ strtolower($number->network_code) }}">{{ $number->network_label }}</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                  @if ($number->is_prepaid)
                    <span class="card-meta-plan">{{ $number->payment_label }}</span>
                  @endif
                  @if ($number->is_postpaid)
                    <span class="card-meta-price">{!! $number->initial_payment_html !!}</span>
                  @endif
                </div>
              </div>
              <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
            </article>
          @empty
            <p class="numbers-empty">ไม่พบบัญชีเบอร์ตามเงื่อนไขที่ค้นหา</p>
          @endforelse
        </div>
      @endif

      @if ($numbers->hasPages() && !$isDefaultSplitLayout)
        @php
          $startPage = max(1, $numbers->currentPage() - 2);
          $endPage = min($numbers->lastPage(), $numbers->currentPage() + 2);
        @endphp
        <nav class="numbers-pagination" aria-label="เปลี่ยนหน้ารายการเบอร์">
          @if ($numbers->onFirstPage())
            <span class="numbers-pagination__link is-disabled">ก่อนหน้า</span>
          @else
            <a class="numbers-pagination__link" href="{{ $numbers->previousPageUrl() }}">ก่อนหน้า</a>
          @endif

          @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $numbers->currentPage())
              <span class="numbers-pagination__link is-active">{{ $page }}</span>
            @else
              <a class="numbers-pagination__link" href="{{ $numbers->url($page) }}">{{ $page }}</a>
            @endif
          @endfor

          @if ($numbers->hasMorePages())
            <a class="numbers-pagination__link" href="{{ $numbers->nextPageUrl() }}">ถัดไป</a>
          @else
            <span class="numbers-pagination__link is-disabled">ถัดไป</span>
          @endif
        </nav>
      @endif
    </div>
  </section>

  <script>
    (() => {
      const prefixField = document.querySelector(".numbers-position-prefix");
      const digitFields = Array.from(document.querySelectorAll(".numbers-position-digit"));

      if (!prefixField || digitFields.length === 0) return;

      const fields = [prefixField, ...digitFields];

      const fieldLimit = (field) => Number.parseInt(field.getAttribute("maxlength") || "1", 10);

      const focusField = (index) => {
        if (index < 0 || index >= fields.length) return;
        fields[index].focus();
        fields[index].select();
      };

      const fillFromIndex = (startIndex, digits) => {
        let offset = 0;

        for (let index = startIndex; index < fields.length; index += 1) {
          const field = fields[index];
          const limit = fieldLimit(field);
          field.value = digits.slice(offset, offset + limit);
          offset += limit;

          if (offset >= digits.length) {
            if (field.value.length >= limit) {
              focusField(index + 1);
            }
            return;
          }
        }

        fields.at(-1)?.focus();
      };

      fields.forEach((field, index) => {
        field.addEventListener("input", () => {
          const digits = field.value.replace(/\D/g, "");
          const limit = fieldLimit(field);

          if (digits.length <= limit) {
            field.value = digits.slice(0, limit);

            if (field.value.length === limit) {
              focusField(index + 1);
            }
            return;
          }

          fillFromIndex(index, digits);
        });

        field.addEventListener("keydown", (event) => {
          const atStart = field.selectionStart === 0 && field.selectionEnd === 0;
          const atEnd = field.selectionStart === field.value.length && field.selectionEnd === field.value.length;

          if (event.key === "Backspace" && field.value === "" && atStart && index > 0) {
            event.preventDefault();
            const previousField = fields[index - 1];
            previousField.value = "";
            previousField.focus();
          }

          if (event.key === "ArrowLeft" && atStart && index > 0) {
            event.preventDefault();
            focusField(index - 1);
          }

          if (event.key === "ArrowRight" && atEnd && index < fields.length - 1) {
            event.preventDefault();
            focusField(index + 1);
          }
        });

        field.addEventListener("paste", (event) => {
          event.preventDefault();
          const pasted = (event.clipboardData || window.clipboardData)
            .getData("text")
            .replace(/\D/g, "");

          if (pasted === "") return;

          fillFromIndex(index, pasted);
        });
      });
    })();

    (() => {
      const serviceTypeSelect = document.getElementById("numbers-service-type");
      const planSelect = document.getElementById("numbers-plan");
      const planOptionsByServiceType = @json($planOptionsByServiceType);
      const serviceTypePostpaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID);
      const serviceTypePrepaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_PREPAID);


      if (!serviceTypeSelect || !planSelect) return;

      const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (char) => {
          switch (char) {
            case "&":
              return "&amp;";
            case "<":
              return "&lt;";
            case ">":
              return "&gt;";
            case '"':
              return "&quot;";
            case "'":
              return "&#39;";
            default:
              return char;
          }
        });

      const resolveOptionKey = (value) => {
        if (value === serviceTypePostpaid || value === serviceTypePrepaid) {
          return value;
        }

        return "all";
      };

      const labels = {
        [serviceTypePostpaid]: "โปรรายเดือน",
        [serviceTypePrepaid]: "ราคาเบอร์",
        all: "โปรโมชั่น / ราคาเบอร์",
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

    (() => {
      const catalogRoot = document.getElementById("numbers-catalog-grid");
      const toggle = document.getElementById("numbers-view-toggle");
      const viewInput = document.getElementById("numbers-view-input");

      if (!catalogRoot || !toggle) return;

      const buttons = Array.from(toggle.querySelectorAll("[data-view]"));
      const catalogGrids = catalogRoot.classList.contains("numbers-catalog-grid")
        ? [catalogRoot]
        : Array.from(catalogRoot.querySelectorAll(".numbers-catalog-grid"));
      const paginationLinks = Array.from(document.querySelectorAll(".numbers-pagination a.numbers-pagination__link"));

      const updateUrlState = (view) => {
        const url = new URL(window.location.href);
        url.searchParams.set("view", view);
        window.history.replaceState({}, "", url);
      };

      const updatePaginationLinks = (view) => {
        paginationLinks.forEach((link) => {
          const url = new URL(link.href, window.location.origin);
          url.searchParams.set("view", view);
          link.href = url.toString();
        });
      };

      const applyView = (view) => {
        const normalizedView = view === "list" ? "list" : "grid";
        catalogRoot.dataset.view = normalizedView;
        catalogGrids.forEach((grid) => {
          grid.dataset.view = normalizedView;
        });
        if (viewInput) {
          viewInput.value = normalizedView;
        }
        updatePaginationLinks(normalizedView);
        updateUrlState(normalizedView);

        buttons.forEach((button) => {
          const isActive = button.dataset.view === normalizedView;
          button.classList.toggle("is-active", isActive);
          button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
      };

      applyView(@json($selectedView));

      toggle.addEventListener("click", (event) => {
        const target = event.target.closest("[data-view]");
        if (!target) return;

        applyView(target.dataset.view);
      });
    })();

    @if ($isDefaultSplitLayout)
    (() => {
      const PAGE_SIZE = 16;

      const setupSlider = (gridId, prevId, nextId) => {
        const grid    = document.getElementById(gridId);
        const prevBtn = document.getElementById(prevId);
        const nextBtn = document.getElementById(nextId);
        if (!grid) return;

        const allCards   = Array.from(grid.querySelectorAll(".number-card"));
        const totalPages = Math.max(1, Math.ceil(allCards.length / PAGE_SIZE));
        let   page       = 1;

        const render = (p) => {
          page = Math.min(totalPages, Math.max(1, p));
          const start = (page - 1) * PAGE_SIZE;
          allCards.forEach((card, i) => {
            card.classList.toggle("slider-hidden", i < start || i >= start + PAGE_SIZE);
          });
          if (prevBtn) prevBtn.disabled = page <= 1;
          if (nextBtn) nextBtn.disabled = page >= totalPages;
        };

        render(1);
        if (prevBtn) prevBtn.addEventListener("click", () => render(page - 1));
        if (nextBtn) nextBtn.addEventListener("click", () => render(page + 1));
      };

      setupSlider("numbers-prepaid-grid",  "numbers-prepaid-prev",  "numbers-prepaid-next");
      setupSlider("numbers-postpaid-grid", "numbers-postpaid-prev", "numbers-postpaid-next");
    })();
    @endif
  </script>
@endsection

@push('scripts')
  @if ($search !== '' || $selectedPlan !== '' || $selectedServiceType !== '' || $positionPattern !== null)
    <script>
      (() => {
        if (!window.SupernumberAnalytics) return;

        window.SupernumberAnalytics.track("search", {
          search_context: "numbers_catalog",
          has_sequence_query: @json($search !== ''),
          has_position_pattern: @json($positionPattern !== null),
          has_plan_filter: @json($selectedPlan !== ''),
          service_type: @json($selectedServiceType !== '' ? $selectedServiceType : 'all'),
          results_count: {{ (int) $numbers->total() }},
        });
      })();
    </script>
  @endif
@endpush
