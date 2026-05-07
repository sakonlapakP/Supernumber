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
            อีเมล*
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

          <div class="estimate-submit-container">
            <button class="estimate-submit" type="submit">ส่งข้อมูลเพื่อวิเคราะห์เบอร์ที่เหมาะกับคุณ</button>
          </div>
        </form>
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

    /* New Premium Styles */
    .estimate-card {
        max-width: 800px;
        margin: 0 auto;
        background: #fff;
        padding: clamp(24px, 5vw, 50px);
        border-radius: 32px;
        box-shadow: 0 20px 60px rgba(47, 38, 31, 0.08);
        border: 1px solid rgba(47, 38, 31, 0.03);
    }
    
    .estimate-form {
        display: grid;
        gap: 24px;
    }

    .estimate-form__intro {
        text-align: center;
        margin-bottom: 20px;
    }

    .estimate-form input, 
    .estimate-form select {
        border-radius: 14px !important;
        border: 1.5px solid rgba(74, 61, 50, 0.1) !important;
        background: #fcfcfb !important;
        padding: 14px 18px !important;
        font-size: 16px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .estimate-form input:focus, 
    .estimate-form select:focus {
        border-color: #d8a34a !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(216, 163, 74, 0.1) !important;
        transform: translateY(-1px);
    }

    .estimate-submit-container {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .estimate-submit {
        width: 100%;
        max-width: 400px;
        min-height: 56px !important;
        border-radius: 16px !important;
        font-size: 18px !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 10px 25px rgba(216, 163, 74, 0.3);
        transition: all 0.3s ease !important;
    }

    .estimate-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(216, 163, 74, 0.4);
        filter: brightness(1.1);
    }

    .estimate-head h2 {
        font-size: clamp(24px, 3vw, 36px) !important;
        font-weight: 800 !important;
        color: #2f261f;
        margin-bottom: 8px;
        text-align: center;
    }
    
    .estimate-head p {
        text-align: center;
        margin-bottom: 30px;
    }

    /* SEO Content Styles */
    .estimate-seo-content {
        margin-top: 60px;
        margin-bottom: 80px;
    }
    .estimate-seo-card {
        background: #fff;
        padding: 50px;
        border-radius: 32px;
        border: 1px solid rgba(0,0,0,0.04);
        box-shadow: 0 15px 45px rgba(0,0,0,0.03);
    }
    .estimate-seo-card h2 {
        font-size: 32px;
        color: #2a2321;
        margin-bottom: 24px;
        font-weight: 800;
        text-align: center;
    }
    .estimate-seo-card p {
        font-size: 18px;
        color: #5b5048;
        line-height: 1.8;
        text-align: center;
        max-width: 700px;
        margin: 0 auto 40px;
    }
    .seo-feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }
    .seo-feature-item {
        background: #fcfcfb;
        padding: 30px;
        border-radius: 24px;
        transition: all 0.3s ease;
    }
    .seo-feature-item:hover {
        background: #fff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transform: translateY(-5px);
    }
    .seo-feature-item h3 {
        font-size: 22px;
        color: #8b5a1f;
        margin-bottom: 12px;
        font-weight: 700;
    }
    .seo-feature-item p {
        font-size: 16px;
        text-align: left;
        margin: 0;
    }
    .seo-expert-note {
        margin-top: 50px;
        padding: 30px;
        background: #fff9f0;
        border-left: 5px solid #d8a34a;
        border-radius: 8px 24px 24px 8px;
    }
    .seo-expert-note p {
        font-size: 17px;
        color: #6d5d50;
        text-align: left;
        margin: 0 !important;
    }
    @media (max-width: 768px) {
        .estimate-seo-card {
            padding: 30px 20px;
        }
        .estimate-seo-card h2 {
            font-size: 26px;
        }
        .estimate-grid--2 {
            grid-template-columns: 1fr;
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
