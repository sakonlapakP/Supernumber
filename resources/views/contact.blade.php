@extends('layouts.app')

@section('title', 'Supernumber | ติดต่อเรา')
@section('meta_description', 'ติดต่อทีม Supernumber เพื่อสอบถามเรื่องเบอร์มงคล การสั่งซื้อ การเลือกแพ็กเกจ และรับคำแนะนำจากทีมงาน')
@section('og_title', 'Supernumber | ติดต่อเรา')
@section('og_description', 'ช่องทางติดต่อ Supernumber ทั้งโทรศัพท์ LINE Facebook อีเมล และข้อมูลเวลาทำการ')
@section('canonical', url('/contact-us'))
@section('og_url', url('/contact-us'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))

@section('content')
  <section class="contact-hero" aria-labelledby="contact-title">
    <div class="contact-hero__overlay"></div>
    <div class="container contact-hero__content">
      <div class="contact-hero__text">
        <p class="hero-kicker">Contact Supernumber</p>
        <h1 id="contact-title">พูดคุยกับทีมงานได้หลายช่องทาง</h1>
        <p>สอบถามเรื่องเบอร์มงคล แพ็กเกจ การสั่งซื้อ หรือขอคำแนะนำจากทีมงานได้โดยตรง เราพร้อมช่วยให้คุณเลือกเบอร์ได้ง่ายและมั่นใจขึ้น</p>
        <div class="contact-hero__actions">
          <a href="https://line.me/ti/p/~supernumber" target="_blank" rel="noopener noreferrer">แชตทาง LINE</a>
          <a class="contact-hero__action--soft" href="tel:0963232656">โทรหาเรา</a>
        </div>
      </div>
    </div>
  </section>

  <section class="contact-page">
    <div class="container contact-shell">
      @if (session('contact_status_message'))
        <div class="contact-alert contact-alert--success">{{ session('contact_status_message') }}</div>
      @endif

      @if ($errors->any())
        <div class="contact-alert contact-alert--error">{{ $errors->first() }}</div>
      @endif

      <div class="contact-main-grid">
        <section class="contact-form-section" aria-labelledby="contact-form-title">
          <div class="contact-form-card">
            <div class="contact-form-card__head">
              <div>
                <p class="contact-card__eyebrow">ส่งข้อความถึงทีมงาน</p>
                <h2 id="contact-form-title">ให้เราติดต่อกลับหาคุณ</h2>
              </div>
              <p>กรอกชื่อ เบอร์ติดต่อ และข้อความที่ต้องการสอบถาม ทีมงานจะนำข้อมูลนี้เข้าระบบหลังบ้านเพื่อประสานงานต่อ</p>
            </div>

            <form class="contact-form" action="{{ route('contact.store') }}" method="post">
              @csrf
              <div class="contact-form__grid">
                <label class="contact-form__field">
                  ชื่อ*
                  <input type="text" name="name" value="{{ old('name') }}" maxlength="120" placeholder="ชื่อผู้ติดต่อ">
                </label>

                <label class="contact-form__field">
                  เบอร์ติดต่อ*
                  <input type="text" name="phone" value="{{ old('phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="เช่น 0812345678">
                </label>
              </div>

              <label class="contact-form__field">
                ข้อความที่ต้องการพิมพ์*
                <textarea name="message" rows="6" maxlength="2000" placeholder="แจ้งรายละเอียดที่ต้องการสอบถามได้ที่นี่">{{ old('message') }}</textarea>
              </label>

              <div class="contact-form__actions">
                <button type="submit">ส่งข้อความ</button>
                <p>ข้อมูลจะถูกบันทึกไว้ในระบบหลังบ้านเพื่อให้ทีมงานติดตามต่อ</p>
              </div>
            </form>
          </div>
        </section>

        <section class="contact-location-section" aria-labelledby="contact-location-title">
          <article class="contact-location-card">
            <div class="contact-location-details">
              <p class="contact-card__eyebrow">ที่ตั้งสำนักงาน</p>
     
              <h2 id="contact-location-title">แผนที่และรายละเอียดติดต่อ</h2>
                                  <div class="contact-location-map">
              <div class="contact-map contact-map--large">
                <iframe
                  src="https://maps.google.com/maps?q=13.7195906,100.5563751&z=17&output=embed"
                  title="แผนที่ Supernumber"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  allowfullscreen
                ></iframe>
              </div>
            </div>
              <p class="contact-address">1414 ถนนพระราม 4 แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110</p>

              <a class="contact-map-link" href="https://maps.app.goo.gl/Lwe3KfLYt3PKhug38" target="_blank" rel="noopener noreferrer">เปิดใน Google Maps</a>
                          

             

            </div>

 
          </article>
        </section>
      </div>
    </div>
  </section>
@endsection
