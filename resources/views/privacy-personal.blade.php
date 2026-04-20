@extends('layouts.app')

@section('title', 'ความยินยอมในการให้ข้อมูลส่วนบุคคล - Supernumber')

@section('content')
<div class="paper-page-wrapper">
    <div class="paper-page-container">
        <!-- Paper Document -->
        <article class="paper-document">
            <!-- Document Header -->
            <header class="doc-header">
                <div class="doc-logo-area">
                    <img src="{{ asset('images/supernumber-document-logo.png') }}" alt="Supernumber Logo" style="filter: grayscale(100%); opacity: 0.8;">
                </div>
                <h1 class="doc-title-main">นโยบายการรักษาความมั่นคงปลอดภัยข้อมูลส่วนบุคคล</h1>
                <h2 class="doc-title-sub">และความเป็นส่วนตัวสำหรับลูกค้า (Privacy Policy)</h2>
                <div class="doc-hr"></div>
                <p class="doc-company-name">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                <div class="doc-date">ฉบับวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</div>
            </header>

            <!-- Document Content -->
            <div class="doc-content">
                <h3 class="section-title">ประกาศนโยบายความเป็นส่วนตัว (Privacy Notice)</h3>
                
                <p class="intro-text">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (ต่อไปนี้จะเรียกว่า "บริษัทฯ") ในฐานะ "ผู้ควบคุมข้อมูลส่วนบุคคล" ตระหนักถึงความรับผิดชอบในการคุ้มครองข้อมูลส่วนบุคคลของท่าน บริษัทฯ จึงได้จัดทำประกาศนโยบายความเป็นส่วนตัวนี้ขึ้น เพื่อชี้แจงถึงวิธีการประมวลผลข้อมูลส่วนบุคคลของท่านในส่วนที่เกี่ยวเนื่องกับการใช้บริการจัดหาและสั่งจองหมายเลขโทรศัพท์มงคล รวมถึงการเข้าถึงเว็บไซต์ของเรา
                </p>

                <div class="doc-sections">
                    <section class="doc-section">
                        <h4 class="sub-section-title">1. ประเภทข้อมูลส่วนบุคคลที่เราเก็บรวบรวม</h4>
                        <p class="section-desc">บริษัทฯ จะดำเนินการเก็บรวบรวมข้อมูลส่วนบุคคลของท่านเท่าที่จำเป็นภายใต้วัตถุประสงค์ที่กำหนด ดังนี้:</p>
                        <ul class="data-list">
                            <li><span class="label"> (ก) ข้อมูลรายละเอียดส่วนตัว (Identity Data):</span> ชื่อ, นามสกุล, วันเดือนปีเกิด, เพศ และข้อมูลระบุตัวตนอื่นๆ เพื่อใช้ในการวิเคราะห์และทำนายพื้นฐานและชะตาชีวิต เพื่อการแนะนำหมายเลขโทรศัพท์มงคลที่เหมาะสมที่สุดสำหรับท่าน</li>
                            <li><span class="label"> (ข) ข้อมูลติดต่อ (Contact Data):</span> ที่อยู่สำหรับการจัดส่งซิมการ์ด, หมายเลขโทรศัพท์ที่ติดต่อได้, ที่อยู่อิเล็กทรอนิกส์ (Email)</li>
                            <li><span class="label"> (ค) ข้อมูลธุรกรรม (Transaction Data):</span> รายละเอียดการสั่งจองเบอร์มงคล, รายละเอียดการชำระเงิน และประวัติการรับบริการ</li>
                            <li><span class="label"> (ง) ข้อมูลทางเทคนิคและพฤติกรรม (Technical and Usage Data):</span> หมายเลข IP, ประวัติการใช้เว็บ และระบบรักษาความปลอดภัยเบื้องต้น</li>
                        </ul>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">2. วัตถุประสงค์และฐานทางกฎหมายในการประมวลผลข้อมูล</h4>
                        <div class="table-container">
                            <table class="doc-table">
                                <thead>
                                    <tr>
                                        <th>วัตถุประสงค์</th>
                                        <th>ฐานทางกฎหมายในการจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>วิเคราะห์ทำนายพื้นฐานดวงชะตาเพื่อจัดหาและแนะนำหมายเลขมงคลที่เหมาะสม</td>
                                        <td>ฐานความยินยอม (Consent) / ฐานสัญญา</td>
                                    </tr>
                                    <tr>
                                        <td>ดำเนินการสั่งจอง จัดทำสัญญา และส่งมอบหมายเลขโทรศัพท์มือถือ</td>
                                        <td>ฐานสัญญา (Contractual Basis)</td>
                                    </tr>
                                    <tr>
                                        <td>การระบุตัวตนสมาชิกและการรักษาความปลอดภัยของระบบเว็บไซต์</td>
                                        <td>ฐานประโยชน์อันชอบธรรม (Legitimate Interest)</td>
                                    </tr>
                                    <tr>
                                        <td>การปฏิบัติตามกฎหมายคอมพิวเตอร์และกฎหมายที่เกี่ยวข้องของสำนักงาน กสทช.</td>
                                        <td>ฐานการปฏิบัติตามกฎหมาย (Legal Obligation)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">4. การขอความยินยอมและผลกระทบจากการถอนความยินยอม</h4>
                        <p class="section-desc">ในกรณีที่เราประมวลผลข้อมูลโดยอาศัยความยินยอม ท่านมีสิทธิถอนความยินยอมได้ทุกเมื่อ โดยไม่กระทบต่อการประมวลผลที่เกิดขึ้นก่อนหน้า:</p>
                        <ul class="bullet-list">
                            <li>หากท่านถอนความยินยอมหรือปฏิเสธไม่ให้ข้อมูลบางส่วน อาจส่งผลให้บริษัทฯ ไม่สามารถให้บริการวิเคราะห์พยากรณ์เบอร์มงคล หรือดำเนินการตามคำสั่งซื้อให้บรรลุวัตถุประสงค์ได้</li>
                            <li><strong>กรณีผู้เยาว์:</strong> หากท่านอายุไม่ครบ 20 ปีบริบูรณ์ หรือเป็นผู้ไร้ความสามารถ/เสมือนไร้ความสามารถ โปรดแจ้งรายละเอียด "ผู้ใช้อำนาจปกครองหรือผู้แทนโดยชอบธรรม" ให้เราทราบเพื่อดำเนินการขอความยินยอมอย่างถูกต้องตามกฎหมาย</li>
                        </ul>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">5. ระยะเวลาในการเก็บรักษาข้อมูลส่วนบุคคล</h4>
                        <p class="section-desc">บริษัทฯ จะเก็บรักษาข้อมูลของท่านในระยะเวลาที่จำเป็นตามวัตถุประสงค์:</p>
                        <ul class="bullet-list">
                            <li>เราจะเก็บข้อมูลไว้ตามระยะเวลาที่คาดหมายได้ตามมาตรฐานการให้บริการ หรือตามที่กฎหมายกำหนด (เช่น กฎหมายภาษีอากร กฎหมายคอมพิวเตอร์)</li>
                            <li>บริษัทฯ มีระบบตรวจสอบเพื่อลบหรือทำลายข้อมูลเมื่อพ้นกำหนดระยะเวลา หรือเมื่อข้อมูลนั้นไม่มีความจำเป็นอีกต่อไป</li>
                            <li>กรณีท่านยกเลิกความยินยอม เราจะเก็บข้อมูลไว้เพียงเท่าที่จำเป็นสำหรับบันทึกประวัติ (Log) เพื่ออ้างอิงการใช้สิทธิในอนาคตเท่านั้น</li>
                        </ul>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">6. การเปิดเผยข้อมูลส่วนบุคคลให้บุคคลอื่น</h4>
                        <p class="section-desc">เพื่อบรรลุวัตถุประสงค์ข้างต้น เราอาจแบ่งปันข้อมูลของท่านให้แก่บุคคลภายนอก ดังนี้:</p>
                        <ul class="bullet-list">
                            <li><strong>กลุ่มพันธมิตรและนิติบุคคลอื่น:</strong> เช่น ผู้แทนจำหน่าย, ผู้ให้บริการขนส่ง (ไปรษณีย์/Kerry/Flash), ผู้ให้บริการทางการเงินและธนาคารเพื่อการชำระเงิน</li>
                            <li><strong>ผู้ให้บริการทางด้านเทคโนโลยี:</strong> ระบบคลาวด์ (Cloud Computing), บริการส่ง SMS มงคล, บริการวิเคราะห์ข้อมูลสถิติ (Data Analytics), และที่ปรึกษาด้านระบบไอที</li>
                            <li><strong>หน่วยงานของรัฐ:</strong> กรมสรรพากร, สำนักงานตำรวจแห่งชาติ หรือหน่วยงานอื่นๆ ที่มีอำนาจตามกฎหมาย</li>
                        </ul>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">7. การส่งหรือโอนข้อมูลไปต่างประเทศ</h4>
                        <p class="section-desc">บริษัทฯ อาจเก็บข้อมูลของท่านบนระบบคลาวด์หรือใช้ซอฟต์แวร์ในรูปแบบ SaaS/PaaS จากผู้ให้บริการต่างประเทศที่มีชื่อเสียงระดับสากล โดยเราจะกำหนดให้มีมาตรการคุ้มครองความปลอดภัยที่เหมาะสม และไม่อนุญาตให้บุคคลที่ไม่เกี่ยวข้องเข้าถึงข้อมูลได้โดยเด็ดขาด</p>
                    </section>

                    <section class="doc-section">
                        <h4 class="sub-section-title">8. มาตรการความปลอดภัยและสิทธิของท่าน</h4>
                        <p class="section-desc">ความปลอดภัยของท่านคือสิ่งสำคัญที่สุด เรานำมาตรฐานความปลอดภัยทางเทคนิค (Technical Measures) และการบริหารจัดการ (Organizational Measures) มาใช้เพื่อป้องกันการสูญหาย การเข้าถึง หรือการใช้ข้อมูลโดยมิชอบ:</p>
                        
                        <div class="rights-area" style="margin-top: 20px;">
                            <p style="font-weight: 700; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px;">สิทธิเกี่ยวกับข้อมูลส่วนบุคคลของท่าน (8 ประการ):</p>
                            <div style="font-size: 13px; line-height: 1.8;">
                                <strong>1. การเข้าถึง:</strong> ขอสำเนาข้อมูลส่วนบุคคลที่เราเก็บรักษาไว้ | 
                                <strong>2. การแก้ไข:</strong> ขอให้ปรับปรุงข้อมูลให้ถูกต้องเป็นปัจจุบัน | 
                                <strong>3. การโอนย้าย:</strong> ขอรับข้อมูลในรูปแบบอิเล็กทรอนิกส์เพื่อส่งต่อ | 
                                <strong>4. การคัดค้าน:</strong> คัดค้านการเก็บหรือใช้ข้อมูลในบางกรณี | 
                                <strong>5. การระงับการใช้:</strong> ขอให้หยุดใช้ข้อมูลชั่วคราว | 
                                <strong>6. การถอนความยินยอม:</strong> ยกเลิกความยินยอมที่เคยให้ไว้ | 
                                <strong>7. การลบ/ทำลาย:</strong> ขอให้ลบข้อมูลเมื่อหมดความจำเป็น | 
                                <strong>8. การร้องเรียน:</strong> ร้องเรียนต่อหน่วยงานรัฐหากพบการละเมิด
                            </div>
                        </div>
                        <p style="font-size: 12px; color: #666; margin-top: 15px;">* บริษัทฯ จะพิจารณาและแจ้งผลคำร้องขอใช้สิทธิของท่านภายใน 30 วันนับแต่วันที่ได้รับคำร้อง</p>
                    </section>
                </div>

            </div>
        </article>

        <!-- Footer Nav -->
        <nav class="doc-footer-nav">
            <a href="{{ route('home') }}" class="nav-back">&larr; กลับหน้าหลัก</a>
            <a href="{{ route('privacy.development') }}" class="nav-next">ฉบับที่ 2: พัฒนาสินค้า &rarr;</a>
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
    
    .data-list, .bullet-list {
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
    
    .doc-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 14px;
    }
    
    .doc-table th, .doc-table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }
    
    .doc-table th {
        background-color: #f9f9f9;
        font-weight: 700;
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
