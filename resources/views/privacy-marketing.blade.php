@extends('layouts.app')

@section('title', 'ความยินยอมเพื่อการตลาดและโปรโมชั่น - Supernumber')

@section('content')
<div class="paper-page-wrapper">
    <div class="paper-page-container">
        <!-- Paper Document -->
        <article class="paper-document">
            <!-- Document Header -->
            <header class="doc-header">
                <div class="doc-logo-area">
                    <img src="{{ asset('images/logo.png') }}" alt="Supernumber Logo" style="filter: grayscale(100%); opacity: 0.8;">
                </div>
                <h1 class="doc-title-main">นโยบายความเป็นส่วนตัวด้านกิจกรรมทางการตลาดและการแจ้งข่าวสาร</h1>
                <h2 class="doc-title-sub">(Marketing and Communications Privacy Policy)</h2>
                <div class="doc-hr"></div>
                <p class="doc-company-name">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                <div class="doc-date">ฉบับวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</div>
            </header>

            <!-- Document Content -->
            <div class="doc-content">
                <h3 class="section-title">นโยบายความเป็นส่วนตัวด้านกิจกรรมทางการตลาดและการแจ้งข่าวสาร</h3>
                
                <p class="intro-text">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) ตระหนักถึงความสําคัญของการสื่อสารที่มีคุณภาพและเป็นประโยชน์ต่อท่าน บริษัทฯ จึงใคร่ขอความยินยอมเพื่อส่งข้อมูลข่าวสารเกี่ยวกับผลิตภัณฑ์เบอร์มงคล การส่งเสริมการขาย และสิทธิพิเศษที่คัดสรรมาโดยเฉพาะ ดังนี้:
                </p>

                <div class="doc-sections">
                    <section class="doc-section">
                        <h4 class="sub-section-title">1. ประเภทข้อมูลและสิทธิประโยชน์ที่จะได้รับ</h4>
                        <p class="section-desc">หากท่านให้ความยินยอม บริษัทฯ จะแจ้งข่าวสารที่เป็นประโยชน์ต่อท่าน ได้แก่:</p>
                        <ul class="data-list">
                            <li><span class="label">● การแจ้งเตือนเบอร์เข้าใหม่ (Priority Notification):</span> สิทธิการเข้าถึงหมายเลขพรีเมียมมาใหม่ก่อนเปิดตัวต่อสาธารณะ</li>
                            <li><span class="label">● ข้อเสนอเชิงพาณิชย์:</span> โค้ดส่วนลดและสิทธิการร่วมแคมเปญชำระเงินในราคาพิเศษสำหรับสมาชิก</li>
                            <li><span class="label">● ข้อมูลวิชาพยากรณ์:</span> ข้อมูลความมูลาเตลูและดวงชะตาที่เกี่ยวข้องกับตัวเลขในชีวิตประจำวัน</li>
                        </ul>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">2. ช่องทางและการรักษาจรรยาบรรณในการสื่อสาร</h4>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 20px 0;">
                            <div style="padding: 15px; background: #f9f9f9; border: 1px solid #eee; text-align: center; font-size: 12px; font-weight: 700;">อีเมล</div>
                            <div style="padding: 15px; background: #f9f9f9; border: 1px solid #eee; text-align: center; font-size: 12px; font-weight: 700;">เอสเอ็มเอส</div>
                            <div style="padding: 15px; background: #f9f9f9; border: 1px solid #eee; text-align: center; font-size: 12px; font-weight: 700;">LINE App</div>
                            <div style="padding: 15px; background: #f9f9f9; border: 1px solid #eee; text-align: center; font-size: 12px; font-weight: 700;">Social Media</div>
                        </div>
                        <p style="font-style: italic; font-size: 14px; color: #666;">บริษัทฯ จะดำเนินการสื่อสารภายใต้จรรยาบรรณการตลาด ไม่ส่งข้อมูลที่เป็นการรบกวน (Spam) และจะไม่นำข้อมูลของท่านไปขายต่อให้แก่บุคคลภายนอกโดยเด็ดขาด</p>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">3. สิทธิในการเพิกถอนความยินยอม</h4>
                        <p class="section-desc">ท่านมีสิทธิในการขอยกเลิกการรับข้อมูลข่าวสารทางการตลาดได้ทุกเวลา (Opt-out) โดยไม่มีเงื่อนไขและไม่มีค่าใช้จ่าย ผ่านช่องทางลิงก์ที่แนบไปในท้ายจดหมายข่าว หรือติดต่อเจ้าหน้าที่ลูกค้าสัมพันธ์ของเราโดยตรง</p>
                    </section>
                </div>

            </div>
        </article>

        <!-- Footer Nav -->
        <nav class="doc-footer-nav">
            <a href="{{ route('privacy.development') }}" class="nav-back">&larr; กลับหน้าที่ 2</a>
            <a href="{{ route('home') }}" class="nav-next" style="background-color: #000;">กลับหน้าหลัก</a>
        </nav>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,700;1,400&display=swap');
    
    .paper-page-wrapper {
        background-color: #f5f5f5;
        padding: 60px 20px;
        min-height: 100vh;
        font-family: 'Sarabun', sans-serif;
        color: #333;
    }
    
    .paper-page-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .paper-document {
        background-color: #fff;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        padding: 80px 60px;
        position: relative;
        border: 1px solid #ddd;
    }
    
    .doc-header {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .doc-logo-area img {
        height: 50px;
        margin-bottom: 25px;
    }
    
    .doc-title-main {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 10px 0;
        color: #000;
    }
    
    .doc-title-sub {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 20px 0;
        color: #000;
    }
    
    .doc-hr {
        width: 100px;
        height: 2px;
        background-color: #000;
        margin: 20px auto;
    }
    
    .doc-company-name {
        font-weight: 700;
        font-size: 18px;
    }
    
    .doc-date {
        text-align: right;
        font-size: 14px;
        color: #666;
        margin-top: 40px;
    }
    
    .doc-content {
        text-align: justify;
    }
    
    .section-title {
        color: #2a5d34;
        font-weight: 700;
        font-size: 18px;
        border-left: 5px solid #2a5d34;
        padding-left: 15px;
        margin-bottom: 25px;
        text-transform: uppercase;
    }
    
    .sub-section-title {
        font-weight: 700;
        font-size: 18px;
        color: #000;
        margin-bottom: 15px;
        text-decoration: underline;
        text-underline-offset: 8px;
        text-decoration-color: #2a5d34;
    }
    
    .doc-sections {
        display: flex;
        flex-direction: column;
        gap: 40px;
    }
    
    .data-list {
        list-style: none;
        padding-left: 20px;
        margin-top: 15px;
    }
    
    .data-list li {
        margin-bottom: 10px;
    }
    
    .data-list .label {
        font-weight: 700;
        color: #000;
    }
    
    .consent-box {
        margin-top: 60px;
        padding: 40px;
        background-color: #f9fafb;
        border: 2px dashed #ddd;
        text-align: center;
        font-size: 14px;
        color: #555;
    }
    
    .consent-title {
        font-weight: 700;
        color: #000;
        font-size: 16px;
        margin-bottom: 10px;
    }
    
    .doc-footer-nav {
        display: flex;
        justify-content: space-between;
        margin-top: 40px;
    }
    
    .nav-back {
        color: #666;
        text-decoration: none;
    }
    
    .nav-next {
        background-color: #2a5d34;
        color: #fff;
        padding: 12px 30px;
        text-decoration: none;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    @media (max-width: 768px) {
        .paper-document {
            padding: 40px 20px;
        }
    }
</style>
@endsection
