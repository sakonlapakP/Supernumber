@extends('layouts.app')

@section('title', 'เลือกเบอร์มงคลให้เหมาะกับคุณ วิเคราะห์ตามวันเกิดและอาชีพ | Supernumber')
@section('meta_description', 'ระบบช่วยเลือกเบอร์มงคลที่ใช่สำหรับคุณโดยเฉพาะ วิเคราะห์ตามข้อมูลส่วนบุคคล วันเกิด อาชีพ และเป้าหมายชีวิต เพื่อแนะนำเบอร์ที่เสริมพลังได้ตรงจุดที่สุด')
@section('og_title', 'เลือกเบอร์มงคลให้เหมาะกับคุณ วิเคราะห์ตามวันเกิดและอาชีพ | Supernumber')
@section('og_description', 'ค้นหาเบอร์มงคลที่ใช่สำหรับคุณ วิเคราะห์แม่นยำตามหลักสถิติและข้อมูลส่วนบุคคล')
@section('canonical', url('/estimate'))
@section('og_url', url('/estimate'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))

@section('seo_schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BreadcrumbList",
  "itemListElement": [{
    "@@type": "ListItem",
    "position": 1,
    "name": "หน้าหลัก",
    "item": "{{ url('/') }}"
  },{
    "@@type": "ListItem",
    "position": 2,
    "name": "เลือกเบอร์ให้เหมาะกับคุณ",
    "item": "{{ url('/estimate') }}"
  }]
}
</script>
@endsection

@php
  $workTypeOptions = \App\Models\EstimateLead::workTypeLabels();
  $goalOptions = \App\Models\EstimateLead::goalLabels();
@endphp

@section('content')
  <section class="estimate-hero" aria-labelledby="estimate-title">
    <div class="estimate-hero__overlay"></div>
    <div class="container estimate-hero__content">
      <div class="estimate-hero__text">
        <h1 id="estimate-title">เลือกเบอร์ให้เหมาะกับคุณ</h1>
        <p class="hero-kicker">ระบบวิเคราะห์และแนะนำเบอร์มงคลอัจฉริยะ</p>
        <p>กรอกข้อมูลพื้นฐานเพื่อรับคำแนะนำเบอร์ที่เหมาะกับงาน การเงิน และเป้าหมายชีวิตของคุณโดยเฉพาะ</p>
      </div>
    </div>
  </section>

  <section class="estimate-page">
    <div class="container estimate-shell">
      <div class="estimate-head">
        <h2>ระบบวิเคราะห์เลือกเบอร์ที่เหมาะกับคุณ</h2>
        <p>กรุณากรอกข้อมูลข้างล่างเพื่อวิเคราะห์</p>
      </div>

      @if (session('estimate_status_message'))
        <div class="estimate-alert estimate-alert--success">{{ session('estimate_status_message') }}</div>
      @endif

      @if ($errors->any())
        <div class="estimate-alert estimate-alert--error">{{ $errors->first() }}</div>
      @endif

      <div class="estimate-card">
        <div class="estimate-layout">
          <form class="estimate-form" action="{{ route('estimate.store') }}" method="post">
            @csrf
            <div class="estimate-form__intro">
              <p>ข้อมูลสำหรับคัดเบอร์</p>
              <h3>กรอกข้อมูลเพื่อให้ทีมงานแนะนำเบอร์ที่เหมาะกับคุณ</h3>
            </div>

            <div class="estimate-grid estimate-grid--2">
              <label>
                ชื่อ*
                <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="ชื่อ">
              </label>
              <label>
                นามสกุล*
                <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="นามสกุล">
              </label>
            </div>

            <div class="estimate-grid estimate-grid--2">
              <label>
                เพศ*
                <select name="gender">
                  <option value="">-- เลือกเพศ --</option>
                  <option value="male" @selected(old('gender') === 'male')>ชาย</option>
                  <option value="female" @selected(old('gender') === 'female')>หญิง</option>
                </select>
              </label>
              <label>
                วันเกิด
                <input type="date" name="birthday" value="{{ old('birthday') }}">
              </label>
            </div>

            <div class="estimate-grid estimate-grid--2">
              <label>
                ลักษณะงานหลักที่ทำ*
                <select name="work_type">
                  <option value="">-- เลือกลักษณะงานที่ทำ --</option>
                  @foreach ($workTypeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('work_type') === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </label>
              <label>
                เบอร์ปัจจุบัน
                <input type="text" name="current_phone" value="{{ old('current_phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="เบอร์ปัจจุบัน">
              </label>
            </div>

            <label>
              เบอร์ที่ใช้งานมากที่สุด*
              <input type="text" name="main_phone" value="{{ old('main_phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="เช่น 0812345678">
            </label>

            <label>
              อีเมล์*
              <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
            </label>

            <label>
              วัตถุประสงค์ในการเปลี่ยนเบอร์*
              <select name="goal">
                <option value="">-- เลือกวัตถุประสงค์ในการเปลี่ยนเบอร์ --</option>
                @foreach ($goalOptions as $value => $label)
                  <option value="{{ $value }}" @selected(old('goal') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </label>

            <button class="estimate-submit" type="submit">ทำนายดวง</button>
          </form>

          <aside class="estimate-video">
            <div class="estimate-video__copy">
              <p>วิดีโอแนะนำ</p>
              <h3>วิธีใช้งานระบบ ENS บนเว็บ</h3>
            </div>
            <div class="estimate-video__frame">
              <iframe
                src="https://www.youtube.com/embed/M7lc1UVf-VE"
                title="ENS guide"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
              ></iframe>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </section>

  <section class="estimate-seo-content container">
    <div class="estimate-seo-card">
      <h2>ทำไมต้องใช้ระบบเลือกเบอร์มงคลอัจฉริยะ?</h2>
      <p>การเลือกเบอร์โทรศัพท์ไม่ใช่แค่เรื่องของความสวยงาม แต่เป็นเรื่องของ <strong>"พลังงานตัวเลข"</strong> ที่ส่งผลต่อชีวิตในทุกๆ ด้าน ระบบของเราถูกออกแบบมาเพื่อช่วยให้คุณค้นพบเบอร์ที่ใช่ที่สุด โดยวิเคราะห์จากข้อมูลส่วนบุคคลที่สำคัญ:</p>
      
      <div class="seo-feature-grid">
        <div class="seo-feature-item">
          <h3>วิเคราะห์ตามวันเกิด</h3>
          <p>เพราะตัวเลขที่เหมาะสมของแต่ละคนไม่เหมือนกัน เราจึงคัดกรองเบอร์ที่เสริมดวงตามวันเกิดของคุณโดยเฉพาะ</p>
        </div>
        <div class="seo-feature-item">
          <h3>คัดตามลักษณะงาน</h3>
          <p>ไม่ว่าคุณจะเป็นนักขาย ผู้บริหาร หรือทำงานอิสระ เราจะแนะนำชุดตัวเลขที่ส่งเสริมความก้าวหน้าในสายอาชีพนั้นๆ</p>
        </div>
        <div class="seo-feature-item">
          <h3>ตอบโจทย์เป้าหมายชีวิต</h3>
          <p>ไม่ว่าจะเน้นการงาน การเงิน ความรัก หรือสุขภาพ ระบบจะเลือกกลุ่มเลขที่ตรงกับความต้องการของคุณ</p>
        </div>
      </div>
      
      <div class="seo-expert-note">
        <p><strong>คำแนะนำจากผู้เชี่ยวชาญ:</strong> การเปลี่ยนเบอร์มงคลควรทำควบคู่ไปกับการตั้งเป้าหมายชีวิตที่ชัดเจน เพื่อให้พลังงานจากตัวเลขช่วยส่งเสริมและผลักดันให้คุณไปถึงเป้าหมายได้รวดเร็วยิ่งขึ้น</p>
      </div>
    </div>
  </section>

  <style>
    @media (min-width: 992px) {
        .estimate-hero__text h1 {
            white-space: nowrap !important;
            max-width: none !important;
        }
    }

    /* SEO Content Styles */
    .estimate-seo-content {
        margin-top: 40px;
        margin-bottom: 60px;
    }
    .estimate-seo-card {
        background: #fff;
        padding: 40px;
        border-radius: 24px;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }
    .estimate-seo-card h2 {
        font-size: 32px;
        color: #2a2321;
        margin-bottom: 20px;
        font-weight: 700;
    }
    .estimate-seo-card p {
        font-size: 18px;
        color: #5b5048;
        line-height: 1.8;
    }
    .seo-feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    .seo-feature-item h3 {
        font-size: 22px;
        color: #8b5a1f;
        margin-bottom: 12px;
        font-weight: 600;
    }
    .seo-feature-item p {
        font-size: 16px;
    }
    .seo-expert-note {
        margin-top: 40px;
        padding: 24px;
        background: #fff8eb;
        border-left: 4px solid #d8a34a;
        border-radius: 4px 16px 16px 4px;
    }
    .seo-expert-note p {
        font-size: 16px;
        margin: 0;
    }
    @media (max-width: 768px) {
        .estimate-seo-card {
            padding: 30px 20px;
        }
        .estimate-seo-card h2 {
            font-size: 24px;
        }
    }
  </style>
@endsection

@push('scripts')
  @if (session('estimate_status_message'))
    <script>
      (() => {
        if (!window.SupernumberAnalytics) return;

        window.SupernumberAnalytics.track("generate_lead", {
          lead_type: "estimate_request",
          form_name: "estimate",
        });
      })();
    </script>
  @endif
@endpush
