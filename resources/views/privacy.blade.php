@extends('layouts.app')

@section('title', 'ศูนย์รวมนโยบายความเป็นส่วนตัว - Supernumber')

@section('content')
<div class="paper-page-wrapper">
    <div class="paper-page-container">
        <!-- Sidebar Menu (Unified Privacy Center) -->
        <aside class="doc-sidebar">
            <button class="sidebar-item active" data-tab="personal">
                <div class="sidebar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <span class="sidebar-text">นโยบายความเป็นส่วนตัวสำหรับลูกค้า</span>
            </button>
            <button class="sidebar-item" data-tab="development">
                <div class="sidebar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"></path></svg>
                </div>
                <span class="sidebar-text">นโยบายเพื่อการวิจัยและพัฒนาสินค้า</span>
            </button>
            <button class="sidebar-item" data-tab="marketing">
                <div class="sidebar-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5L6 9H2v6h4l5 4V5zM15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
                </div>
                <span class="sidebar-text">นโยบายความเป็นส่วนตัวด้านการตลาด</span>
            </button>
        </aside>

        <!-- Paper Document Area -->
        <div class="paper-content-area">
            
            <!-- SECTION 1: PERSONAL (FULL CONTENT RESTORED) -->
            <article class="paper-document doc-section-content active" id="personal">
                <header class="doc-header">
                    <h1 class="doc-title-main">นโยบายการรักษาความมั่นคงปลอดภัยข้อมูลส่วนบุคคล</h1>
                    <h2 class="doc-title-sub">และความเป็นส่วนตัวสำหรับลูกค้า (Privacy Policy)</h2>
                    <div class="doc-hr"></div>
                    <p class="doc-company-name">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                    <div class="doc-date">อัปเดตล่าสุดวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</div>
                </header>

                <div class="doc-content">
                    <h3 class="section-title">ประกาศนโยบายความเป็นส่วนตัว (Privacy Notice)</h3>
                    <p class="intro-text">
                        บริษัท ซุปเปอร์นัมเบอร์ จำกัด (ต่อไปนี้จะเรียกว่า "บริษัทฯ") ในฐานะ "ผู้ควบคุมข้อมูลส่วนบุคคล" ตระหนักถึงความรับผิดชอบในการคุ้มครองข้อมูลส่วนบุคคลของท่าน บริษัทฯ จึงได้จัดทำประกาศนโยบายความเป็นส่วนตัวนี้ขึ้น เพื่อชี้แจงถึงวิธีการประมวลผลข้อมูลส่วนบุคคลของท่านในส่วนที่เกี่ยวเนื่องกับการใช้บริการจัดหาและสั่งจองหมายเลขโทรศัพท์มงคล รวมถึงการเข้าถึงเว็บไซต์ของเรา
                    </p>

                    <div class="doc-sections">
                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">1. ประเภทข้อมูลส่วนบุคคลที่เราเก็บรวบรวม</h4>
                            <p class="section-desc">บริษัทฯ จะดำเนินการเก็บรวบรวมข้อมูลส่วนบุคคลของท่านเท่าที่จำเป็นภายใต้วัตถุประสงค์ที่กำหนด ดังนี้:</p>
                            <ul class="data-list">
                                <li><span class="label"> (ก) ข้อมูลรายละเอียดส่วนตัว (Identity Data):</span> ชื่อ, นามสกุล, วันเดือนปีเกิด, เพศ และข้อมูลระบุตัวตนอื่นๆ เพื่อใช้ในการวิเคราะห์และทำนายพื้นฐานและชะตาชีวิต เพื่อการแนะนำหมายเลขโทรศัพท์มงคลที่เหมาะสมที่สุดสำหรับท่าน</li>
                                <li><span class="label"> (ข) ข้อมูลติดต่อ (Contact Data):</span> ที่อยู่สำหรับการจัดส่งซิมการ์ด, หมายเลขโทรศัพท์ที่ติดต่อได้, ที่อยู่อิเล็กทรอนิกส์ (Email)</li>
                                <li><span class="label"> (ค) ข้อมูลธุรกรรม (Transaction Data):</span> รายละเอียดการสั่งจองเบอร์มงคล, รายละเอียดการชำระเงิน และประวัติการรับบริการ</li>
                                <li><span class="label"> (ง) ข้อมูลทางเทคนิคและพฤติกรรม (Technical and Usage Data):</span> หมายเลข IP, ประวัติการใช้เว็บ และระบบรักษาความปลอดภัยเบื้องต้น</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
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

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">4. การขอความยินยอมและผลกระทับจากการถอนความยินยอม</h4>
                            <p class="section-desc">ในกรณีที่เราประมวลผลข้อมูลโดยอาศัยความยินยอม ท่านมีสิทธิถอนความยินยอมได้ทุกเมื่อ โดยไม่กระทบต่อการประมวลผลที่เกิดขึ้นก่อนหน้า:</p>
                            <ul class="bullet-list">
                                <li>หากท่านถอนความยินยอมหรือปฏิเสธไม่ให้ข้อมูลบางส่วน อาจส่งผลให้บริษัทฯ ไม่สามารถให้บริการวิเคราะห์พยากรณ์เบอร์มงคล หรือดำเนินการตามคำสั่งซื้อให้บรรลุวัตถุประสงค์ได้</li>
                                <li><strong>กรณีผู้เยาว์:</strong> หากท่านอายุไม่ครบ 20 ปีบริบูรณ์ หรือเป็นผู้ไร้ความสามารถ/เสมือนไร้ความสามารถ โปรดแจ้งรายละเอียด "ผู้ใช้อำนาจปกครองหรือผู้แทนโดยชอบธรรม" ให้เราทราบเพื่อดำเนินการขอความยินยอมอย่างถูกต้องตามกฎหมาย</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">5. ระยะเวลาในการเก็บรักษาข้อมูลส่วนบุคคล</h4>
                            <p class="section-desc">บริษัทฯ จะเก็บรักษาข้อมูลของท่านในระยะเวลาที่จำเป็นตามวัตถุประสงค์:</p>
                            <ul class="bullet-list">
                                <li>เราจะเก็บข้อมูลไว้ตามระยะเวลาที่คาดหมายได้ตามมาตรฐานการให้บริการ หรือตามที่กฎหมายกำหนด (เช่น กฎหมายภาษีอากร กฎหมายคอมพิวเตอร์)</li>
                                <li>บริษัทฯ มีระบบตรวจสอบเพื่อลบหรือทำลายข้อมูลเมื่อพ้นกำหนดระยะเวลา หรือเมื่อข้อมูลนั้นไม่มีความจำเป็นอีกต่อไป</li>
                                <li>กรณีท่านยกเลิกความยินยอม เราจะเก็บข้อมูลไว้เพียงเท่าที่จำเป็นสำหรับบันทึกประวัติ (Log) เพื่ออ้างอิงการใช้สิทธิในอนาคตเท่านั้น</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">6. การเปิดเผยข้อมูลส่วนบุคคลให้บุคคลอื่น</h4>
                            <p class="section-desc">เพื่อบรรลุวัตถุประสงค์ข้างต้น เราอาจแบ่งปันข้อมูลของท่านให้แก่บุคคลภายนอก ดังนี้:</p>
                            <ul class="bullet-list">
                                <li><strong>กลุ่มพันธมิตรและนิติบุคคลอื่น:</strong> เช่น ผู้แทนจำหน่าย, ผู้ให้บริการขนส่ง (ไปรษณีย์/Kerry/Flash), ผู้ให้บริการทางการเงินและธนาคารเพื่อการชำระเงิน</li>
                                <li><strong>ผู้ให้บริการทางด้านเทคโนโลยี:</strong> ระบบคลาวด์ (Cloud Computing), บริการส่ง SMS มงคล, บริการวิเคราะห์ข้อมูลสถิติ (Data Analytics), และที่ปรึกษาด้านระบบไอที</li>
                                <li><strong>หน่วยงานของรัฐ:</strong> กรมสรรพากร, สำนักงานตำรวจแห่งชาติ หรือหน่วยงานอื่นๆ ที่มีอำนาจตามกฎหมาย</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">7. การส่งหรือโอนข้อมูลไปต่างประเทศ</h4>
                            <p class="section-desc">บริษัทฯ อาจเก็บข้อมูลของท่านบนระบบคลาวด์หรือใช้ซอฟต์แวร์ในรูปแบบ SaaS/PaaS จากผู้ให้บริการต่างประเทศที่มีชื่อเสียงระดับสากล โดยเราจะกำหนดให้มีมาตรการคุ้มครองความปลอดภัยที่เหมาะสม และไม่อนุญาตให้บุคคลที่ไม่เกี่ยวข้องเข้าถึงข้อมูลได้โดยเด็ดขาด</p>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">8. มาตรการความปลอดภัยและสิทธิของท่าน</h4>
                            <p class="section-desc">ความปลอดภัยของท่านคือสิ่งสำคัญที่สุด เรานำมาตรฐานความปลอดภัยทางเทคนิค (Technical Measures) และการบริหารจัดการ (Organizational Measures) มาใช้เพื่อป้องกันการสูญหาย การเข้าถึง หรือการใช้ข้อมูลโดยมิชอบ:</p>
                            
                            <div class="rights-area">
                                <p style="font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;">สิทธิเกี่ยวกับข้อมูลส่วนบุคคลของท่าน (8 ประการ):</p>
                                <div class="rights-grid">
                                    <span><strong>1. การเข้าถึง:</strong> ขอสำเนาข้อมูลส่วนบุคคล</span>
                                    <span><strong>2. การแก้ไข:</strong> ปรับปรุงข้อมูลให้ถูกต้อง</span>
                                    <span><strong>3. การโอนย้าย:</strong> ขอรับข้อมูลรูปแบบอิเล็กทรอนิกส์</span>
                                    <span><strong>4. การคัดค้าน:</strong> คัดค้านการเก็บหรือใช้ข้อมูล</span>
                                    <span><strong>5. การระงับการใช้:</strong> หยุดใช้ข้อมูลชั่วคราว</span>
                                    <span><strong>6. การถอนความยินยอม:</strong> ยกเลิกความยินยอม</span>
                                    <span><strong>7. การลบ/ทำลาย:</strong> ขอให้ลบเมื่อหมดความจำเป็น</span>
                                    <span><strong>8. การร้องเรียน:</strong> ร้องเรียนต่อหน่สยงานรัฐ</span>
                                </div>
                            </div>
                            <p style="font-size: 12px; color: #666; margin-top: 15px;">* บริษัทฯ จะพิจารณาและแจ้งผลคำร้องขอใช้สิทธิของท่านภายใน 30 วันนับแต่วันที่ได้รับคำร้อง</p>
                        </section>
                    </div>
                </div>
            </article>

            <!-- SECTION 2: DEVELOPMENT (FULL CONTENT RESTORED) -->
            <article class="paper-document doc-section-content" id="development">
                <header class="doc-header">
                    <h1 class="doc-title-main">นโยบายการคุ้มครองข้อมูลส่วนบุคคลเพื่อการวิจัยและพัฒนา</h1>
                    <h2 class="doc-title-sub">(Product and Service Development Privacy Policy)</h2>
                    <div class="doc-hr"></div>
                    <p class="doc-company-name">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                    <div class="doc-date">อัปเดตล่าสุดวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</div>
                </header>

                <div class="doc-content">
                    <h3 class="section-title">นโยบายเพื่อการวิจัยและพัฒนาสินค้าหรือบริการ</h3>
                    <p class="intro-text">
                        บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) มุ่งมั่นที่จะนำเทคโนโลยีและศาสตร์พยากรณ์ตัวเลขมาประยุกต์ใช้เพื่อสร้างประสบการณ์ที่ดีที่สุดแก่ท่าน เราจึงมีความจำเป็นต้องประมวลผลข้อมูลบางส่วนเพื่อการวิจัยและพัฒนา ภายใต้มาตรฐานความปลอดภัยสูงสุด ดังนี้:
                    </p>

                    <div class="doc-sections">
                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">1. ประเภทข้อมูลและเทคนิคการประมวลผล</h4>
                            <p class="section-desc">เพื่อให้เป็นไปตามหลักการลดการใช้ข้อมูล (Data Minimization) บริษัทฯ จะใช้วิธีการดังต่อไปนี้:</p>
                            <ul class="bullet-list">
                                <li><span class="label">(ก) การทำให้ข้อมูลเป็นนามแฝง (Pseudonymization):</span> การนำข้อมูลพฤติกรรมการค้นหาเบอร์มาแปลงรหัสเพื่อให้ไม่สามารถระบุถึงตัวบุคคลได้ในระหว่างขั้นตอนการวิจัย</li>
                                <li><span class="label">(ข) ข้อมูลทางสถิติการใช้งาน:</span> สถิติการเข้าถึงหมวดหมู่เบอร์มงคลต่างๆ ความนิยมของกลุ่มตัวเลขแยกตามกลุ่มความต้องการ</li>
                            </ul>
                        </section>
                        
                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">2. วัตถุประสงค์และประโยชน์ที่ท่านจะได้รับ</h4>
                            <div class="feature-box">
                                <div class="feature-item">
                                    <p class="feature-title">การพัฒนาความแม่นยำของระบบพยากรณ์ (AI Accuracy)</p>
                                    <p class="feature-desc">วิเคราะห์รูปแบบตัวเลขเพื่อปรับปรุงอัลกอริทึมการแนะนำเบอร์มงคลให้สอดคล้องกับความต้องการของท่านมากขึ้น</p>
                                </div>
                                <div class="feature-item">
                                    <p class="feature-title">การเพิ่มประสิทธิภาพความมั่นคงปลอดภัยไซเบอร์</p>
                                    <p class="feature-desc">วิเคราะห์พฤติกรรมผิดปกติเพื่อป้องกันการรั่วไหลของข้อมูลและเสริมสร้างเกราะคุ้มกันความเป็นส่วนตัว</p>
                                </div>
                            </div>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">3. มาตรการจำกัดสิทธิการเข้าถึงข้อมูลเพื่อการวิจัย</h4>
                            <p class="section-desc">ข้อมูลเพื่อการวิจัยและพัฒนาจะถูกแยกเก็บไว้ในระบบฐานข้อมูลที่แยกอิสระ (Sandbox Environment) โดยผู้ที่มีสิทธิเข้าถึงข้อมูลจะเป็นเพียงเจ้าหน้าที่เฉพาะกลุ่ม (Data Science & Engineer Team) ซึ่งผ่านการตรวจสอบประวัติและลงนามในสัญญาปกปิดความลับเท่านั้น</p>
                        </section>
                        
                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">4. การขอความยินยอมและผลกระทับจากการถอนความยินยอม</h4>
                            <p class="section-desc">ในกรณีที่เราประมวลผลข้อมูลโดยอาศัยความยินยอม ท่านมีสิทธิถอนความยินยอมได้ทุกเมื่อ โดยไม่กระทบต่อการประมวลผลที่เกิดขึ้นก่อนหน้า:</p>
                            <ul class="bullet-list">
                                <li>หากท่านถอนความยินยอมหรือปฏิเสธไม่ให้ข้อมูลบางส่วน อาจส่งผลให้บริษัทฯ ไม่สามารถให้บริการวิเคราะห์พยากรณ์เบอร์มงคล หรือดำเนินการตามคำสั่งซื้อให้บรรลุวัตถุประสงค์ได้</li>
                                <li><strong>กรณีผู้เยาว์:</strong> หากท่านอายุไม่ครบ 20 ปีบริบูรณ์ หรือเป็นผู้ไร้ความสามารถ/เสมือนไร้ความสามารถ โปรดแจ้งรายละเอียด "ผู้ใช้อำนาจปกครองหรือผู้แทนโดยชอบธรรม" ให้เราทราบเพื่อดำเนินการขอความยินยอมอย่างถูกต้องตามกฎหมาย</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">5. ระยะเวลาในการเก็บรักษาข้อมูลส่วนบุคคล</h4>
                            <p class="section-desc">บริษัทฯ จะเก็บรักษาข้อมูลของท่านในระยะเวลาที่จำเป็นตามวัตถุประสงค์:</p>
                            <ul class="bullet-list">
                                <li>เราจะเก็บข้อมูลไว้ตามระยะเวลาที่คาดหมายได้ตามมาตรฐานการให้บริการ หรือตามที่กฎหมายกำหนด (เช่น กฎหมายภาษีอากร กฎหมายคอมพิวเตอร์)</li>
                                <li>บริษัทฯ มีระบบตรวจสอบเพื่อลบหรือทำลายข้อมูลเมื่อพ้นกำหนดระยะเวลา หรือเมื่อข้อมูลนั้นไม่มีความจำเป็นอีกต่อไป</li>
                                <li>กรณีท่านยกเลิกความยินยอม เราจะเก็บข้อมูลไว้เพียงเท่าที่จำเป็นสำหรับบันทึกประวัติ (Log) เพื่ออ้างอิงการใช้สิทธิในอนาคตเท่านั้น</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">6. การเปิดเผยข้อมูลส่วนบุคคลให้บุคคลอื่น</h4>
                            <p class="section-desc">เพื่อบรรลุวัตถุประสงค์ข้างต้น เราอาจแบ่งปันข้อมูลของท่านให้แก่บุคคลภายนอก ดังนี้:</p>
                            <ul class="bullet-list">
                                <li><strong>กลุ่มพันธมิตรและนิติบุคคลอื่น:</strong> เช่น ผู้แทนจำหน่าย, ผู้ให้บริการขนส่ง (ไปรษณีย์/Kerry/Flash), ผู้ให้บริการทางการเงินและธนาคารเพื่อการชำระเงิน</li>
                                <li><strong>ผู้ให้บริการทางด้านเทคโนโลยี:</strong> ระบบคลาวด์ (Cloud Computing), บริการส่ง SMS มงคล, บริการวิเคราะห์ข้อมูลสถิติ (Data Analytics), และที่ปรึกษาด้านระบบไอที</li>
                                <li><strong>หน่วยงานของรัฐ:</strong> กรมสรรพากร, สำนักงานตำรวจแห่งชาติ หรือหน่วยงานอื่นๆ ที่มีอำนาจตามกฎหมาย</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">7. การส่งหรือโอนข้อมูลไปต่างประเทศ</h4>
                            <p class="section-desc">บริษัทฯ อาจเก็บข้อมูลของท่านบนระบบคลาวด์หรือใช้ซอฟต์แวร์ในรูปแบบ SaaS/PaaS จากผู้ให้บริการต่างประเทศที่มีชื่อเสียงระดับสากล โดยเราจะกำหนดให้มีมาตรการคุ้มครองความปลอดภัยที่เหมาะสม และไม่อนุญาตให้บุคคลที่ไม่เกี่ยวข้องเข้าถึงข้อมูลได้โดยเด็ดขาด</p>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">8. มาตรการความปลอดภัยและสิทธิของท่าน</h4>
                            <p class="section-desc">ความปลอดภัยของท่านคือสิ่งสำคัญที่สุด เรานำมาตรฐานความปลอดภัยทางเทคนิค (Technical Measures) และการบริหารจัดการ (Organizational Measures) มาใช้เพื่อป้องกันการสูญหาย การเข้าถึง หรือการใช้ข้อมูลโดยมิชอบ:</p>
                            
                            <div class="rights-area">
                                <p style="font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;">สิทธิเกี่ยวกับข้อมูลส่วนบุคคลของท่าน (8 ประการ):</p>
                                <div class="rights-grid">
                                    <span><strong>1. การเข้าถึง:</strong> ขอสำเนาข้อมูลส่วนบุคคล</span>
                                    <span><strong>2. การแก้ไข:</strong> ปรับปรุงข้อมูลให้ถูกต้อง</span>
                                    <span><strong>3. การโอนย้าย:</strong> ขอรับข้อมูลรูปแบบอิเล็กทรอนิกส์</span>
                                    <span><strong>4. การคัดค้าน:</strong> คัดค้านการเก็บหรือใช้ข้อมูล</span>
                                    <span><strong>5. การระงับการใช้:</strong> หยุดใช้ข้อมูลชั่วคราว</span>
                                    <span><strong>6. การถอนความยินยอม:</strong> ยกเลิกความยินยอม</span>
                                    <span><strong>7. การลบ/ทำลาย:</strong> ขอให้ลบเมื่อหมดความจำเป็น</span>
                                    <span><strong>8. การร้องเรียน:</strong> ร้องเรียนต่อหน่สยงานรัฐ</span>
                                </div>
                            </div>
                            <p style="font-size: 12px; color: #666; margin-top: 15px;">* บริษัทฯ จะพิจารณาและแจ้งผลคำร้องขอใช้สิทธิของท่านภายใน 30 วันนับแต่วันที่ได้รับคำร้อง</p>
                        </section>
                    </div>
                </div>
            </article>

            <!-- SECTION 3: MARKETING (FULL CONTENT RESTORED) -->
            <article class="paper-document doc-section-content" id="marketing">
                <header class="doc-header">
                    <h1 class="doc-title-main">นโยบายความเป็นส่วนตัวด้านกิจกรรมทางการตลาด</h1>
                    <h2 class="doc-title-sub">(Marketing and Communications Privacy Policy)</h2>
                    <div class="doc-hr"></div>
                    <p class="doc-company-name">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                    <div class="doc-date">อัปเดตล่าสุดวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</div>
                </header>

                <div class="doc-content">
                    <h3 class="section-title">นโยบายผลิตภัณฑ์และข่าวสาร</h3>
                    <p class="intro-text">
                        บริษัทฯ ใคร่ขอความยินยอมเพื่อส่งข้อมูลข่าวสารเกี่ยวกับผลิตภัณฑ์เบอร์มงคล การส่งเสริมการขาย และสิทธิพิเศษที่คัดสรรมาโดยเฉพาะ เพื่อให้ท่านไม่พลาดโอกาสสำคัญ ดังนี้:
                    </p>

                    <div class="doc-sections">
                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">1. สิทธิประโยชน์ที่จะได้รับ</h4>
                            <ul class="data-list">
                                <li><span class="label">● การแจ้งเตือนเบอร์เข้าใหม่:</span> สิทธิเข้าถึงเบอร์พรีเมียมก่อนเปิดตัวต่อสาธารณะ</li>
                                <li><span class="label">● ข้อเสนอพิเศษ:</span> โค้ดส่วนลดและแคมเปญพิเศษสำหรับสมาชิก</li>
                                <li><span class="label">● ข้อมูลมูลาเตลู:</span> เคล็ดลับดวงชะตาและตัวเลขมงคลประจำสัปดาห์</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">2. ช่องทางและการรักษาจรรยาบรรณทางการสื่อสาร</h4>
                            <div class="channels-grid">
                                <span>อีเมล</span>
                                <span>เอสเอ็มเอส (SMS)</span>
                                <span>LINE Official</span>
                                <span>โซเชียลมีเดีย</span>
                            </div>
                            <p class="section-desc" style="font-style: italic; font-size: 14px; margin-top: 15px;">บริษัทฯ จะดำเนินการสื่อสารภายใต้จรรยาบรรณการตลาด และจะไม่นำข้อมูลของท่านไปขายต่อให้แก่บุคคลภายนอกโดยเด็ดขาด</p>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">4. การขอความยินยอมและผลกระทับจากการถอนความยินยอม</h4>
                            <p class="section-desc">ในกรณีที่เราประมวลผลข้อมูลโดยอาศัยความยินยอม ท่านมีสิทธิถอนความยินยอมได้ทุกเมื่อ โดยไม่กระทบต่อการประมวลผลที่เกิดขึ้นก่อนหน้า:</p>
                            <ul class="bullet-list">
                                <li>หากท่านถอนความยินยอมหรือปฏิเสธไม่ให้ข้อมูลบางส่วน อาจส่งผลให้บริษัทฯ ไม่สามารถให้บริการวิเคราะห์พยากรณ์เบอร์มงคล หรือดำเนินการตามคำสั่งซื้อให้บรรลุวัตถุประสงค์ได้</li>
                                <li><strong>กรณีผู้เยาว์:</strong> หากท่านอายุไม่ครบ 20 ปีบริบูรณ์ หรือเป็นผู้ไร้ความสามารถ/เสมือนไร้ความสามารถ โปรดแจ้งรายละเอียด "ผู้ใช้อำนาจปกครองหรือผู้แทนโดยชอบธรรม" ให้เราทราบเพื่อดำเนินการขอความยินยอมอย่างถูกต้องตามกฎหมาย</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">5. ระยะเวลาในการเก็บรักษาข้อมูลส่วนบุคคล</h4>
                            <p class="section-desc">บริษัทฯ จะเก็บรักษาข้อมูลของท่านในระยะเวลาที่จำเป็นตามวัตถุประสงค์:</p>
                            <ul class="bullet-list">
                                <li>เราจะเก็บข้อมูลไว้ตามระยะเวลาที่คาดหมายได้ตามมาตรฐานการให้บริการ หรือตามที่กฎหมายกำหนด (เช่น กฎหมายภาษีอากร กฎหมายคอมพิวเตอร์)</li>
                                <li>บริษัทฯ มีระบบตรวจสอบเพื่อลบหรือทำลายข้อมูลเมื่อพ้นกำหนดระยะเวลา หรือเมื่อข้อมูลนั้นไม่มีความจำเป็นอีกต่อไป</li>
                                <li>กรณีท่านยกเลิกความยินยอม เราจะเก็บข้อมูลไว้เพียงเท่าที่จำเป็นสำหรับบันทึกประวัติ (Log) เพื่ออ้างอิงการใช้สิทธิในอนาคตเท่านั้น</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">6. การเปิดเผยข้อมูลส่วนบุคคลให้บุคคลอื่น</h4>
                            <p class="section-desc">เพื่อบรรลุวัตถุประสงค์ข้างต้น เราอาจแบ่งปันข้อมูลของท่านให้แก่บุคคลภายนอก ดังนี้:</p>
                            <ul class="bullet-list">
                                <li><strong>กลุ่มพันธมิตรและนิติบุคคลอื่น:</strong> เช่น ผู้แทนจำหน่าย, ผู้ให้บริการขนส่ง (ไปรษณีย์/Kerry/Flash), ผู้ให้บริการทางการเงินและธนาคารเพื่อการชำระเงิน</li>
                                <li><strong>ผู้ให้บริการทางด้านเทคโนโลยี:</strong> ระบบคลาวด์ (Cloud Computing), บริการส่ง SMS มงคล, บริการวิเคราะห์ข้อมูลสถิติ (Data Analytics), และที่ปรึกษาด้านระบบไอที</li>
                                <li><strong>หน่วยงานของรัฐ:</strong> กรมสรรพากร, สำนักงานตำรวจแห่งชาติ หรือหน่วยงานอื่นๆ ที่มีอำนาจตามกฎหมาย</li>
                            </ul>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">7. การส่งหรือโอนข้อมูลไปต่างประเทศ</h4>
                            <p class="section-desc">บริษัทฯ อาจเก็บข้อมูลของท่านบนระบบคลาวด์หรือใช้ซอฟต์แวร์ในรูปแบบ SaaS/PaaS จากผู้ให้บริการต่างประเทศที่มีชื่อเสียงระดับสากล โดยเราจะกำหนดให้มีมาตรการคุ้มครองความปลอดภัยที่เหมาะสม และไม่อนุญาตให้บุคคลที่ไม่เกี่ยวข้องเข้าถึงข้อมูลได้โดยเด็ดขาด</p>
                        </section>

                        <section class="doc-inner-section">
                            <h4 class="sub-section-title">8. มาตรการความปลอดภัยและสิทธิของท่าน</h4>
                            <p class="section-desc">ความปลอดภัยของท่านคือสิ่งสำคัญที่สุด เรานำมาตรฐานความปลอดภัยทางเทคนิค (Technical Measures) และการบริหารจัดการ (Organizational Measures) มาใช้เพื่อป้องกันการสูญหาย การเข้าถึง หรือการใช้ข้อมูลโดยมิชอบ:</p>
                            
                            <div class="rights-area">
                                <p style="font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;">สิทธิเกี่ยวกับข้อมูลส่วนบุคคลของท่าน (8 ประการ):</p>
                                <div class="rights-grid">
                                    <span><strong>1. การเข้าถึง:</strong> ขอสำเนาข้อมูลส่วนบุคคล</span>
                                    <span><strong>2. การแก้ไข:</strong> ปรับปรุงข้อมูลให้ถูกต้อง</span>
                                    <span><strong>3. การโอนย้าย:</strong> ขอรับข้อมูลรูปแบบอิเล็กทรอนิกส์</span>
                                    <span><strong>4. การคัดค้าน:</strong> คัดค้านการเก็บหรือใช้ข้อมูล</span>
                                    <span><strong>5. การระงับการใช้:</strong> หยุดใช้ข้อมูลชั่วคราว</span>
                                    <span><strong>6. การถอนความยินยอม:</strong> ยกเลิกความยินยอม</span>
                                    <span><strong>7. การลบ/ทำลาย:</strong> ขอให้ลบเมื่อหมดความจำเป็น</span>
                                    <span><strong>8. การร้องเรียน:</strong> ร้องเรียนต่อหน่สยงานรัฐ</span>
                                </div>
                            </div>
                            <p style="font-size: 12px; color: #666; margin-top: 15px;">* บริษัทฯ จะพิจารณาและแจ้งผลคำร้องขอใช้สิทธิของท่านภายใน 30 วันนับแต่วันที่ได้รับคำร้อง</p>
                        </section>
                    </div>
                </div>
            </article>

            <!-- Footer Nav -->
            <nav class="doc-footer-nav" style="margin-top: 40px; text-align: center;">
                <a href="{{ route('home') }}" class="nav-back-button">&larr; กลับหน้าหลัก</a>
            </nav>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.sidebar-item');
        const sections = document.querySelectorAll('.doc-section-content');

        function switchTab(tabId) {
            // Update buttons
            tabs.forEach(t => t.classList.remove('active'));
            const activeTab = document.querySelector(`[data-tab="${tabId}"]`);
            if (activeTab) activeTab.classList.add('active');

            // Update sections
            sections.forEach(s => s.classList.remove('active'));
            const activeSection = document.getElementById(tabId);
            if (activeSection) activeSection.classList.add('active');

            // Scroll to top of doc
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Update hash without jumping
            history.pushState(null, null, `#${tabId}`);
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                switchTab(tab.getAttribute('data-tab'));
            });
        });

        // Handle initial hash or query param
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hash = window.location.hash.replace('#', '');
        
        if (tabParam && ['personal', 'development', 'marketing'].includes(tabParam)) {
            switchTab(tabParam);
        } else if (hash && ['personal', 'development', 'marketing'].includes(hash)) {
            switchTab(hash);
        }
    });
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,700;1,400&display=swap');
    
    .paper-page-wrapper {
        background-color: #f8f6f2;
        padding: 50px 20px;
        min-height: 100vh;
        font-family: 'Sarabun', sans-serif;
        color: #333;
    }
    
    .paper-page-container {
        max-width: 1240px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 50px;
        align-items: start;
    }
    
    /* Sidebar Styles */
    .doc-sidebar {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: sticky;
        top: 100px;
        padding-top: 20px;
    }
    
    .sidebar-item {
        background-color: #fff;
        padding: 22px 25px;
        border: 1px solid #e5e0d8;
        text-decoration: none;
        color: #5c4d44;
        display: flex;
        gap: 18px;
        align-items: center;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        text-align: left;
        cursor: pointer;
        border-radius: 4px;
        outline: none;
        width: 100%;
    }
    
    .sidebar-item:hover {
        transform: translateX(8px);
        border-color: #d8a34a;
        color: #d8a34a;
        background-color: #fffaf0;
    }
    
    .sidebar-item.active {
        background-color: #2a2321;
        color: #d8a34a;
        border-color: #2a2321;
        box-shadow: 0 15px 30px rgba(42, 35, 33, 0.25);
    }
    
    .sidebar-icon {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background-color: #f9f7f4;
        transition: all 0.3s ease;
    }
    
    .sidebar-item.active .sidebar-icon {
        background-color: rgba(216, 163, 74, 0.15);
        color: #d8a34a;
    }
    
    .sidebar-icon svg {
        width: 22px;
        height: 22px;
    }
    
    .sidebar-text {
        font-size: 15px;
        font-weight: 700;
        line-height: 1.4;
    }
    
    /* Paper Document Area */
    .doc-section-content {
        display: none;
    }
    
    .doc-section-content.active {
        display: block;
        animation: fadeIn 0.6s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .paper-document {
        background-color: #fff;
        box-shadow: 0 15px 60px rgba(0,0,0,0.08);
        padding: 90px 70px;
        position: relative;
        border: 1px solid #eee;
        min-height: 800px;
    }
    
    .doc-header {
        text-align: center;
        margin-bottom: 70px;
    }
    
    .doc-title-main {
        font-size: 26px;
        font-weight: 700;
        margin: 0 0 12px 0;
        color: #2a2321;
        letter-spacing: -0.5px;
    }
    
    .doc-title-sub {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 25px 0;
        color: #6d5d54;
    }
    
    .doc-hr {
        width: 120px;
        height: 3px;
        background-color: #d8a34a;
        margin: 25px auto;
    }
    
    .doc-company-name {
        font-weight: 700;
        font-size: 19px;
        color: #2a2321;
    }
    
    .doc-date {
        text-align: right;
        font-size: 14px;
        color: #999;
        margin-top: 45px;
        font-style: italic;
    }
    
    .section-title {
        color: #2a2321;
        font-weight: 700;
        font-size: 20px;
        border-left: 6px solid #d8a34a;
        padding-left: 20px;
        margin-bottom: 30px;
        background: linear-gradient(to right, #fdf8ef, transparent);
        padding-top: 10px;
        padding-bottom: 10px;
    }
    
    .sub-section-title {
        font-weight: 700;
        font-size: 18px;
        color: #2a2321;
        margin-bottom: 20px;
        display: inline-block;
        border-bottom: 2px solid #eee;
        padding-bottom: 5px;
    }
    
    .doc-inner-section {
        margin-bottom: 50px;
    }
    
    .data-list, .bullet-list {
        list-style: none;
        padding-left: 10px;
        margin-top: 20px;
    }
    
    .data-list li, .bullet-list li {
        margin-bottom: 15px;
        line-height: 1.7;
    }
    
    .data-list .label {
        font-weight: 700;
        color: #2a2321;
    }
    
    .doc-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #eee;
    }
    
    .doc-table th {
        background-color: #f9f7f4;
        padding: 18px;
        text-align: left;
        font-weight: 700;
        color: #2a2321;
        border-bottom: 2px solid #eee;
    }
    
    .doc-table td {
        padding: 18px;
        border-bottom: 1px solid #eee;
        font-size: 15px;
        line-height: 1.6;
    }
    
    .rights-area {
        background-color: #fdfdfd;
        border: 1px solid #f0eee8;
        padding: 35px;
        border-radius: 12px;
        margin-top: 30px;
    }
    
    .rights-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        font-size: 14px;
        line-height: 1.8;
    }
    
    .feature-box {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        margin-top: 25px;
    }
    
    .feature-item {
        background: #fcfaf7;
        padding: 25px;
        border-radius: 12px;
        border-left: 4px solid #d8a34a;
    }
    
    .feature-title {
        font-weight: 700;
        margin-bottom: 10px;
        font-size: 16px;
    }
    
    .channels-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-top: 15px;
    }
    
    .channels-grid span {
        padding: 15px;
        background: #f9f7f4;
        border: 1px solid #eee;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
    }
    
    .nav-back-button {
        display: inline-block;
        padding: 14px 40px;
        background-color: #2a2321;
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 700;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    @media (max-width: 1024px) {
        .paper-page-container {
            grid-template-columns: 1fr;
        }
        .doc-sidebar {
            position: static;
            flex-direction: row;
            overflow-x: auto;
            padding-bottom: 15px;
        }
        .sidebar-item {
            min-width: 300px;
        }
        .paper-document {
            padding: 60px 25px;
        }
        .feature-box, .rights-grid, .channels-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
