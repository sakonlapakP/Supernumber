@extends('layouts.app')

@section('title', 'Supernumber | เลือกเบอร์ให้เหมาะกับคุณ')
@section('meta_description', 'ระบบเลือกเบอร์ให้เหมาะกับคุณ กรอกข้อมูลพื้นฐานเพื่อรับคำแนะนำเบอร์ที่ตรงกับเป้าหมายการใช้งาน')
@section('og_title', 'Supernumber | เลือกเบอร์ให้เหมาะกับคุณ')
@section('og_description', 'กรอกข้อมูลเพื่อวิเคราะห์และเลือกเบอร์ที่เหมาะกับคุณแบบเข้าใจง่าย')
@section('canonical', url('/estimate'))
@section('og_url', url('/estimate'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))

@section('content')
  <section class="estimate-hero" aria-labelledby="estimate-title">
    <div class="estimate-hero__overlay"></div>
    <div class="container estimate-hero__content">
      <div class="estimate-hero__text">
        <p class="hero-kicker">ระบบช่วยเลือกเบอร์</p>
        <h1 id="estimate-title">เลือกเบอร์ให้เหมาะกับคุณ</h1>
        <p>กรอกข้อมูลพื้นฐานเพื่อรับคำแนะนำเบอร์ที่เหมาะกับงาน การเงิน และเป้าหมายชีวิตของคุณ</p>
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
                  <option value="sales" @selected(old('work_type') === 'sales')>งานขาย / เจรจา</option>
                  <option value="service" @selected(old('work_type') === 'service')>งานบริการ / ดูแลลูกค้า</option>
                  <option value="office" @selected(old('work_type') === 'office')>งานออฟฟิศ / บริหาร</option>
                  <option value="online" @selected(old('work_type') === 'online')>งานออนไลน์ / คอนเทนต์</option>
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
                <option value="work" @selected(old('goal') === 'work')>เน้นการงาน / โอกาสใหม่</option>
                <option value="money" @selected(old('goal') === 'money')>เน้นการเงิน / ปิดการขาย</option>
                <option value="love" @selected(old('goal') === 'love')>เน้นความรัก / ความสัมพันธ์</option>
                <option value="balance" @selected(old('goal') === 'balance')>เน้นสมดุลชีวิต</option>
              </select>
            </label>

            <button class="estimate-submit" type="submit">ทำนายดวง</button>
          </form>

          <aside class="estimate-video">
            <h3>Video : แนะนำวิธีการใช้งานระบบ ENS บนเว็บ</h3>
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
