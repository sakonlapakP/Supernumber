<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <div class="brand brand--footer" aria-label="Supernumber">
        <div class="brand-mark">S</div>
        <div class="brand-text">
          <div class="brand-title">NUMBER</div>
          <div class="brand-sub">SUPERNUMBER</div>
        </div>
      </div>
      <p class="footer-note">
        ทีม Supernumber พร้อมช่วยดูแลทุกคำถามเกี่ยวกับเบอร์มงคล เพื่อให้คุณมั่นใจในทุกการตัดสินใจ
      </p>
    </div>
    <div class="footer-links">
      <h3>เมนูด่วน</h3>
      <ul>
        <li><a href="{{ route('home') }}">หน้าหลัก</a></li>
        <li><a href="{{ route('numbers.index') }}">เบอร์ทั้งหมด</a></li>
        <li><a href="{{ route('estimate') }}">เลือกเบอร์ให้คุณ</a></li>
        <li><a href="{{ route('contact') }}">ติดต่อเรา</a></li>
      </ul>
    </div>
    <div class="footer-links">
      <h3>หมวดหมู่ยอดนิยม</h3>
      <ul>
        <li><a href="{{ route('numbers.index', ['service_type' => 'prepaid']) }}">เบอร์มงคล เติมเงิน</a></li>
        <li><a href="{{ route('numbers.index', ['service_type' => 'postpaid']) }}">เบอร์มงคล รายเดือน</a></li>
        <li><a href="{{ route('numbers.index', ['q' => '789']) }}">เบอร์มังกร (เลข 789)</a></li>
        <li><a href="{{ route('numbers.index', ['q' => '289']) }}">เบอร์หงส์ (เลข 289)</a></li>
        <li><a href="{{ route('articles.index') }}">บทความความรู้เบอร์มงคล</a></li>
      </ul>
    </div>
    <div class="footer-links">
      <h3>ข้อมูลและนโยบาย</h3>
      <ul>
        <li><a href="#">วิธีการสั่งซื้อ</a></li>
        <li><a href="#">คำถามที่พบบ่อย</a></li>
        <li><a href="#">นโยบายความเป็นส่วนตัว</a></li>
        <li><a href="#">ข้อกำหนดและเงื่อนไข</a></li>
        <li><a href="#">แจ้งปัญหาการใช้งาน</a></li>
      </ul>
    </div>
    <div class="footer-contact">
      <h3>ติดต่อเรา</h3>
      <p>
        โทรศัพท์: <a href="tel:0963232656">096-323-2656</a> ,
        <a href="tel:0963232665">096-323-2665</a>
      </p>
      <p>อีเมล: <a href="mailto:contact@supernumber.co.th">contact@supernumber.co.th</a></p>
      <p>เวลาทำการ: จันทร์ - ศุกร์ (09:00 - 18:00 น.)</p>
      <p>ที่อยู่: 1414 ถนน พระราม 4 แขวง คลองเตย เขตคลองเตย กรุงเทพมหานคร</p>
      <p class="footer-copy">www.supernumber.co.th All rights reserved. | <a href="{{ url('/sitemap.xml') }}" style="color: inherit; text-decoration: underline; opacity: 0.7;">Sitemap</a></p>
    </div>
    <div class="footer-social">
      <h3>ติดตามเรา</h3>
      <a class="social-pill line" href="https://line.me/ti/p/~supernumber" target="_blank" rel="noopener noreferrer">
        <span class="social-icon">LINE</span>
        <span>@supernumber</span>
      </a>
      <a class="social-pill facebook" href="https://facebook.com/supernumber915" target="_blank" rel="noopener noreferrer">
        <span class="social-icon">f</span>
        <span>supernumber915</span>
      </a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container footer-bottom__content">
      <span>© {{ date('Y') }} Supernumber. All rights reserved.</span>
      <span>บริการให้คำปรึกษาเบอร์มงคลโดยผู้เชี่ยวชาญ</span>
    </div>
  </div>
</footer>