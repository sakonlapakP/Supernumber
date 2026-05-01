@extends('layouts.admin')

@section('title', 'Supernumber Admin | ตั้งค่าข้อความอัตโนมัติ')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>ตั้งค่าข้อความอัตโนมัติ</h1>
      <p class="admin-subtitle">จัดการข้อความ Template สำหรับหวย ทั้งบนเว็บไซต์, LINE และ Facebook</p>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <form class="admin-form" action="{{ route('admin.auto-messages.update') }}" method="post">
    @csrf
    
    <section class="admin-card admin-feature-card">
      <div class="admin-page-head" style="margin-bottom: 18px;">
        <div>
          <h2 style="margin: 0; font-size: 1.1rem;">Template สำหรับผลหวยรัฐบาล</h2>
        </div>
      </div>

      <div class="admin-field" style="margin-bottom: 24px;">
        <label for="lottery_msg_line">ข้อความแจ้งเตือนผลหวยใน LINE</label>
        <textarea
          id="lottery_msg_line"
          class="admin-input"
          name="lottery_msg_line"
          rows="8"
          placeholder="ใส่ Template ข้อความที่จะส่งเข้ากลุ่ม LINE"
        >{{ old('lottery_msg_line', $settings['LOTTERY_MSG_LINE'] ?? '') }}</textarea>
        <div class="admin-muted" style="margin: 8px 0 0; font-size: 0.85rem; line-height: 1.5;">
          <strong>ตัวแปรที่ใช้ได้:</strong><br>
          <code>{draw_date}</code> (วันที่ไทยแบบสั้น), <code>{thai_draw_date}</code> (วันที่ไทยแบบเต็ม), 
          <code>{first_prize}</code> (รางวัลที่ 1), <code>{front_three}</code> (เลขหน้า 3 ตัว), 
          <code>{back_three}</code> (เลขท้าย 3 ตัว), <code>{last_two}</code> (เลขท้าย 2 ตัว), 
          <code>{near_first}</code> (ข้างเคียงรางวัลที่ 1)
        </div>
      </div>

      <div class="admin-field" style="margin-bottom: 24px;">
        <label for="lottery_msg_fb">ข้อความโพสต์ Facebook (Caption)</label>
        <textarea
          id="lottery_msg_fb"
          class="admin-input"
          name="lottery_msg_fb"
          rows="6"
          placeholder="ใส่ Template สำหรับข้อความโพสต์ Facebook"
        >{{ old('lottery_msg_fb', $settings['LOTTERY_MSG_FB'] ?? '') }}</textarea>
        <div class="admin-muted" style="margin: 8px 0 0; font-size: 0.85rem; line-height: 1.5;">
          <strong>ตัวแปรที่ใช้ได้:</strong><br>
          <code>{title}</code> (หัวข้อบทความ), <code>{excerpt}</code> (สรุปเนื้อหา), 
          <code>{article_url}</code> (ลิงก์ไปยังบทความ), <code>{draw_date}</code>, <code>{first_prize}</code>, <code>{last_two}</code>
        </div>
      </div>

      <div class="admin-field">
        <label for="lottery_msg_footer">ข้อความปิดท้ายบทความ (Footer)</label>
        <textarea
          id="lottery_msg_footer"
          class="admin-input"
          name="lottery_msg_footer"
          rows="4"
          placeholder="ข้อความที่แสดงท้ายบทความหวย"
        >{{ old('lottery_msg_footer', $settings['LOTTERY_MSG_FOOTER'] ?? '') }}</textarea>
        <div class="admin-muted" style="margin: 8px 0 0; font-size: 0.85rem; line-height: 1.5;">
          <strong>ตัวแปรที่ใช้ได้:</strong><br>
          <code>{updated_at}</code> (เวลาอัปเดตล่าสุด น.), <code>{draw_date}</code>
        </div>
      </div>

    </section>

    <div style="margin-top: 24px;">
      <button type="submit" class="admin-button" style="width: 100%; font-size: 1.1rem; padding: 12px;">บันทึก Template ทั้งหมด</button>
    </div>
  </form>

  <section class="admin-card admin-feature-card" style="margin-top: 18px;">
    <div class="admin-page-head" style="margin-bottom: 18px;">
      <div>
        <h2 style="margin: 0; font-size: 1rem;">คำแนะนำเพิ่มเติม</h2>
      </div>
    </div>
    <div class="admin-muted" style="font-size: 0.9rem; line-height: 1.6;">
      - ตัวแปรในปีกกา <code>{...}</code> จะถูกแทนที่ด้วยข้อมูลจริงโดยอัตโนมัติ<br>
      - การเปลี่ยนแปลงจะมีผลทันทีในการส่งข้อความครั้งถัดไป<br>
      - หากต้องการตั้งค่า LINE Token หรือ Facebook Page ID กรุณาไปที่เมนู <strong>"ระบบ > ตั้งค่าการเชื่อมต่อ API"</strong>
    </div>
  </section>

@endsection
