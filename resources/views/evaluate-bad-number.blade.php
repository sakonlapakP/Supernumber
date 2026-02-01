@extends('layouts.app')

@section('title', 'Supernumber | วิเคราะห์เบอร์ควรระวัง')
@section('meta_description', 'ผลการวิเคราะห์เบอร์ที่มีคู่เลขควรระวังระดับสูง พร้อมคำแนะนำเพื่อหลีกเลี่ยงความเสี่ยง')
@section('og_title', 'Supernumber | วิเคราะห์เบอร์ควรระวัง')
@section('og_description', 'ผลการวิเคราะห์เบอร์ที่มีคู่เลขควรระวังระดับสูง พร้อมคำแนะนำเพื่อหลีกเลี่ยงความเสี่ยง')
@section('canonical', url('/evaluateBadNumber'))
@section('og_url', url('/evaluateBadNumber'))
@section('og_image', asset('images/evaluate_banner.jpg'))
@section('preload_image', asset('images/evaluate_banner.jpg'))

@section('content')
  @php
    $rawPhone = request('phone');
    $phone = preg_replace('/[^0-9]/', '', $rawPhone ?? '');
    if ($phone === '') {
        $phone = '0641234567';
    }
  @endphp
  <section class="evaluate-hero evaluate-hero--danger" aria-labelledby="evaluate-title">
    <div class="evaluate-overlay"></div>
    <div class="container evaluate-hero__content">
      <div class="evaluate-hero__text">
        <p class="evaluate-kicker">รายงานความเสี่ยง</p>
        <h1 id="evaluate-title">ผลการวิเคราะห์เบอร์ที่ต้องระวังเป็นพิเศษ</h1>
        <p>
          ระบบตรวจพบคู่เลขที่มีความเสี่ยงสูง แนะนำให้อ่านคำเตือนให้ครบและปรึกษาทีม Supernumber
        </p>
      </div>
    </div>
  </section>

  <section class="evaluate-results evaluate-results--danger">
    <div class="container">
      <div class="evaluate-summary">
        <div class="summary-number summary-number--danger">
          <span>หมายเลขมือถือ</span>
          <strong>{{ $phone }}</strong>
          <small>ระดับความเสี่ยง: สูง</small>
        </div>
        <div class="summary-body">
          <h2>สัญญาณเตือนสำคัญ</h2>
          <p>
            เบอร์นี้มีพลังงานด้านอารมณ์และความเครียดสูง อาจส่งผลต่อการตัดสินใจ การสื่อสาร และสุขภาพจิต
            เมื่ออยู่ในสถานการณ์กดดัน หากใช้งานต่อเนื่องควรมีการจัดสมดุลอย่างจริงจัง
          </p>
          <div class="summary-tags">
            <span class="badge badge-alert">ควรระวังสูง</span>
            <span class="badge badge-alert">หลีกเลี่ยงการใช้ยาวนาน</span>
            <span class="badge badge-neutral">แนะนำปรับสมดุล</span>
          </div>
        </div>
      </div>

      <div class="danger-banner">
        <div class="danger-icon">!</div>
        <div>
          <h3>คำเตือนระดับสูง</h3>
          <p>
            คู่เลขบางชุดมีแนวโน้มกระตุ้นความใจร้อน อารมณ์แปรปรวน และการตัดสินใจผิดพลาด
            ควรหลีกเลี่ยงการใช้ในช่วงเวลาที่ต้องการความนิ่งและความชัดเจน
          </p>
        </div>
      </div>

      <div class="pair-grid">
        <article class="pair-card is-danger">
          <div class="pair-score">12</div>
          <h4>เลขควรระวัง</h4>
          <p>ใจร้อน หุนหันพลันแล่น ส่งผลต่อการตัดสินใจและความสัมพันธ์</p>
        </article>
        <article class="pair-card is-good">
          <div class="pair-score">23</div>
          <h4>เลขส่งเสริม</h4>
          <p>ช่วยดึงดูดโอกาสใหม่ ลดแรงเสียดทานจากคู่เลขอันตราย</p>
        </article>
        <article class="pair-card is-danger">
          <div class="pair-score">34</div>
          <h4>เลขควรระวัง</h4>
          <p>กดดันทางอารมณ์สูง ส่งผลให้สื่อสารผิดพลาดและอ่อนล้า</p>
        </article>
        <article class="pair-card is-good">
          <div class="pair-score">45</div>
          <h4>เลขส่งเสริม</h4>
          <p>เพิ่มความน่าเชื่อถือ ช่วยประคองพลังด้านงานและภาพลักษณ์</p>
        </article>
        <article class="pair-card is-good">
          <div class="pair-score">56</div>
          <h4>เลขส่งเสริม</h4>
          <p>เพิ่มความสุขและความสัมพันธ์ที่ดี ช่วยลดแรงกดดันภายใน</p>
        </article>
        <article class="pair-card is-danger">
          <div class="pair-score">67</div>
          <h4>เลขควรระวัง</h4>
          <p>ความเครียดสะสมสูง เสี่ยงต่อความผิดพลาดซ้ำ ๆ หากไม่พักผ่อนให้พอ</p>
        </article>
      </div>

      <section class="recommend-section" aria-labelledby="recommend-title">
        <h2 id="recommend-title">เบอร์แนะนำเพื่อปรับสมดุล</h2>
        <div class="recommend-grid">
          <article class="number-card">
            <div class="card-top">0646495945</div>
            <div class="card-body">
              <div class="card-tier card-tier--platinum">Platinum</div>
              <p>โปรโมชั่น 4G+ Super Smart</p>
              <span>1499 ขึ้นไป</span>
            </div>
            <button class="card-btn">ดูความหมาย</button>
          </article>
          <article class="number-card">
            <div class="card-top">0645164549</div>
            <div class="card-body">
              <div class="card-tier card-tier--silver">Silver</div>
              <p>โปรโมชั่น 4G+ Super Smart</p>
              <span>699 ขึ้นไป</span>
            </div>
            <button class="card-btn">ดูความหมาย</button>
          </article>
          <article class="number-card">
            <div class="card-top">0645953639</div>
            <div class="card-body">
              <div class="card-tier card-tier--platinum">Platinum</div>
              <p>โปรโมชั่น 4G+ Super Smart</p>
              <span>1499 ขึ้นไป</span>
            </div>
            <button class="card-btn">ดูความหมาย</button>
          </article>
          <article class="number-card">
            <div class="card-top">0645636463</div>
            <div class="card-body">
              <div class="card-tier card-tier--gold">Gold</div>
              <p>โปรโมชั่น 4G+ Super Smart</p>
              <span>1099 ขึ้นไป</span>
            </div>
            <button class="card-btn">ดูความหมาย</button>
          </article>
        </div>
      </section>

      <div class="evaluate-cta evaluate-cta--danger">
        <div>
          <h3>ต้องการให้ผู้เชี่ยวชาญช่วยวิเคราะห์แบบละเอียด?</h3>
          <p>ให้ทีม Supernumber คัดเบอร์ใหม่ที่เหมาะกับคุณ ลดความเสี่ยงและเพิ่มโอกาสในชีวิต</p>
        </div>
        <a class="cta-btn" href="{{ route('home') }}">กลับไปเลือกเบอร์</a>
      </div>
    </div>
  </section>
@endsection
