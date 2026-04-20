@extends('layouts.app')

@section('title', 'นโยบายเพื่อการวิจัยและพัฒนาสินค้า - Supernumber')

@section('content')
<div class="paper-page-wrapper">
    <div class="paper-page-container">
        <!-- Sidebar Menu (Comseven Style) -->
        <aside class="doc-sidebar">
            <a href="{{ route('privacy.personal') }}" class="sidebar-item">
                <div class="sidebar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span class="sidebar-text">นโยบายความเป็นส่วนตัวสำหรับลูกค้า</span>
            </a>
            <a href="{{ route('privacy.development') }}" class="sidebar-item active">
                <div class="sidebar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"></path></svg>
                </div>
                <span class="sidebar-text">นโยบายเพื่อการวิจัยและพัฒนาสินค้า</span>
            </a>
            <a href="{{ route('privacy.marketing') }}" class="sidebar-item">
                <div class="sidebar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5zM15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                </div>
                <span class="sidebar-text">นโยบายความเป็นส่วนตัวด้านการตลาด</span>
            </a>
        </aside>

        <!-- Paper Document Area -->
        <div class="paper-content-area">
            <article class="paper-document">
                <!-- Document Header -->
                <header class="doc-header">
                    <h1 class="doc-title-main">นโยบายการคุ้มครองข้อมูลส่วนบุคคลเพื่อการวิจัยและพัฒนา</h1>
                    <h2 class="doc-title-sub">(Product and Service Development Privacy Policy)</h2>
                    <div class="doc-hr"></div>
                    <p class="doc-company-name">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                    <div class="doc-date">ฉบับวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</div>
                </header>

                <!-- Document Content -->
                <div class="doc-content">
                    <h3 class="section-title">นโยบายการคุ้มครองข้อมูลส่วนบุคคลเพื่อการวิจัยและพัฒนา</h3>
                    
                    <p class="intro-text">
                        บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) มีความมุ่งมั่นที่จะนำเทคโนโลยีและศาสตร์พยากรณ์ตัวเลขมาประยุกต์ใช้เพื่อสร้างประสบการณ์ที่ดีที่สุดแก่ท่าน บริษัทฯ จึงมีความจำเป็นต้องประมวลผลข้อมูลบางส่วนเพื่อวัตถุประสงค์ในการวิจัยและพัฒนาสินค้าและบริการ (Research and Development) ภายใต้มาตรฐานความปลอดภัยสูงสุด ดังนี้:
                    </p>

                    <div class="doc-sections">
                        <section class="doc-section">
                            <h4 class="sub-section-title">1. ประเภทข้อมูลและเทคนิคการประมวลผล</h4>
                            <p class="section-desc">เพื่อให้เป็นไปตามหลักการลดการใช้ข้อมูล (Data Minimization) บริษัทฯ จะใช้วิธีการดังต่อไปนี้:</p>
                            <ul class="data-list">
                                <li><span class="label">(ก) การทำให้ข้อมูลเป็นนามแฝง (Pseudonymization):</span> การนำข้อมูลพฤติกรรมการค้นหาเบอร์มาแปลงรหัสเพื่อให้ไม่สามารถระบุถึงตัวบุคคลได้ในระหว่างขั้นตอนการวิจัย</li>
                                <li><span class="label">(ข) ข้อมูลทางสถิติการใช้งาน:</span> สถิติการเข้าถึงหมวดหมู่เบอร์มงคลต่างๆ ความนิยมของกลุ่มตัวเลขแยกตามกลุ่มความต้องการ</li>
                            </ul>
                        </section>

                        <section class="doc-section">
                            <h4 class="sub-section-title">2. วัตถุประสงค์และประโยชน์ที่ท่านจะได้รับ</h4>
                            <div class="feature-box" style="border-left: 2px solid #eee; padding-left: 20px; margin-top: 20px;">
                                <div style="margin-bottom: 25px;">
                                    <p style="font-weight: 700; color: #000; margin-bottom: 5px;">2.1 การพัฒนาความแม่นยำของระบบพยากรณ์ (AI Accuracy)</p>
                                    <p style="font-size: 14px; color: #666;">เพื่อวิเคราะห์ว่ารูปแบบตัวเลขใดส่งผลดีต่อสถิติความพึงพอใจของผู้ใช้ และนำไปปรับปรุงอัลกอริทึมการแนะนำเบอร์มงคลให้สอดคล้องกับความต้องการของท่านมากขึ้น</p>
                                </div>
                                <div>
                                    <p style="font-weight: 700; color: #000; margin-bottom: 5px;">2.2 การเพิ่มประสิทธิภาพความมั่นคงปลอดภัยไซเบอร์</p>
                                    <p style="font-size: 14px; color: #666;">เพื่อตรวจสอบและวิเคราะห์พฤติกรรมผิดปกติในการเข้าถึงระบบ ช่วยป้องกันการรั่วไหลของข้อมูลและเสริมสร้างเกราะคุ้มกันความเป็นส่วนตัวให้แข็งแเกร่งยิ่งขึ้น</p>
                                </div>
                            </div>
                        </section>

                        <section class="doc-section">
                            <h4 class="sub-section-title">3. มาตรการจำกัดสิทธิการเข้าถึงข้อมูลเพื่อการวิจัย</h4>
                            <p class="section-desc">ข้อมูลเพื่อการวิจัยและพัฒนาจะถูกแยกเก็บไว้ในระบบฐานข้อมูลที่แยกอิสระ (Sandbox Environment) โดยผู้ที่มีสิทธิเข้าถึงข้อมูลจะเป็นเพียงเจ้าหน้าที่เฉพาะกลุ่ม (Data Science & Engineer Team) ซึ่งผ่านการตรวจสอบประวัติและลงนามในสัญญาปกปิดความลับเท่านั้น</p>
                        </section>
                        
                        <section class="doc-section">
                            <h4 class="sub-section-title">4. สิทธิในการคัดค้าน</h4>
                            <p class="section-desc">ท่านมีสิทธิในการปฏิเสธการให้ข้อมูลเพื่อการวิจัยและพัฒนาได้ตลอดเวลา โดยการเพิกถอนความยินยอมนี้จะไม่ส่งผลกระทบต่อสิทธิในการเลือกซื้อหรือใช้บริการหลักของบริษัทฯ</p>
                        </section>
                    </div>
                </div>
            </article>

            <!-- Footer Nav -->
            <nav class="doc-footer-nav">
                <a href="{{ route('privacy.personal') }}" class="nav-back">&larr; ย้อนกลับ: นโยบายลูกค้า</a>
                <a href="{{ route('privacy.marketing') }}" class="nav-next">ถัดไป: นโยบายการตลาด &rarr;</a>
            </nav>
        </div>
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
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 40px;
        align-items: start;
    }
    
    /* Sidebar Styles */
    .doc-sidebar {
        display: flex;
        flex-direction: column;
        gap: 15px;
        position: sticky;
        top: 40px;
    }
    
    .sidebar-item {
        background-color: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #333;
        display: flex;
        gap: 15px;
        align-items: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    
    .sidebar-item:hover {
        transform: translateX(5px);
        border-color: #2a5d34;
        color: #2a5d34;
    }
    
    .sidebar-item.active {
        background-color: #2a5d34;
        color: #fff;
        border-color: #2a5d34;
        box-shadow: 0 8px 20px rgba(42, 93, 52, 0.2);
    }
    
    .sidebar-icon {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sidebar-icon svg {
        width: 20px;
        height: 20px;
    }
    
    .sidebar-text {
        font-size: 14px;
        font-weight: 700;
        line-height: 1.4;
    }
    
    /* Paper Document Area */
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
    
    .data-list, .bullet-list {
        list-style: none;
        padding-left: 20px;
        margin-top: 15px;
    }
    
    .data-list li, .bullet-list li {
        margin-bottom: 10px;
    }
    
    .data-list .label {
        font-weight: 700;
        color: #000;
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
    
    @media (max-width: 1024px) {
        .paper-page-container {
            grid-template-columns: 1fr;
        }
        .doc-sidebar {
            position: static;
            flex-direction: row;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        .sidebar-item {
            min-width: 280px;
        }
    }
    
    @media (max-width: 768px) {
        .paper-document {
            padding: 40px 20px;
        }
    }
</style>
@endsection
