@extends('layouts.app')

@section('title', 'ความยินยอมเพื่อพัฒนาสินค้าและบริการ - Supernumber')

@section('content')
<div class="paper-page-container py-16 bg-[#f5f5f5] min-h-screen">
    <div class="max-w-[900px] mx-auto">
        <!-- Paper Document -->
        <div class="paper-document bg-white shadow-2xl p-12 md:p-20 relative overflow-hidden">
            <!-- Document Header -->
            <div class="text-center mb-16">
                <div class="mb-6">
                    <img src="{{ asset('images/logo.png') }}" alt="Supernumber Logo" class="h-12 mx-auto grayscale-100 opacity-80">
                </div>
                <h1 class="text-2xl font-bold text-black mb-2">นโยบายการประมวลผลข้อมูลเพื่อวัตถุประสงค์ทางสถิติและการพัฒนา</h1>
                <h2 class="text-xl font-bold text-black mb-4">(Product and Service Development Privacy Policy)</h2>
                <div class="w-24 h-0.5 bg-black mx-auto mb-6"></div>
                <p class="font-bold text-gray-800">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                <p class="text-sm text-gray-500 mt-8 text-right">ฉบับวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</p>
            </div>

            <!-- Document Content -->
            <div class="document-body text-[#333] leading-relaxed text-base text-justify">
                <h3 class="font-bold text-lg mb-6 text-[#2a5d34] border-l-4 border-[#2a5d34] pl-4 uppercase">นโยบายการคุ้มครองข้อมูลส่วนบุคคลเพื่อการวิจัยและพัฒนา</h3>
                
                <p class="mb-6">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) มีความมุ่งมั่นที่จะนำเทคโนโลยีและศาสตร์พยากรณ์ตัวเลขมาประยุกต์ใช้เพื่อสร้างประสบการณ์ที่ดีที่สุดแก่ท่าน บริษัทฯ จึงมีความจำเป็นต้องประมวลผลข้อมูลบางส่วนเพื่อวัตถุประสงค์ในการวิจัยและพัฒนาสินค้าและบริการ (Research and Development) ภายใต้มาตรฐานความปลอดภัยสูงสุด ดังนี้:
                </p>

                <div class="space-y-10">
                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">1. ประเภทข้อมูลและเทคนิคการประมวลผล</h4>
                        <p class="mb-4">เพื่อให้เป็นไปตามหลักการลดการใช้ข้อมูล (Data Minimization) บริษัทฯ จะใช้วิธีการดังต่อไปนี้:</p>
                        <ul class="list-none pl-4 space-y-4">
                            <li><span class="font-bold text-black">(ก) การทำให้ข้อมูลเป็นนามแฝง (Pseudonymization):</span> การนำข้อมูลพฤติกรรมการค้นหาเบอร์มาแปลงรหัสเพื่อให้ไม่สามารถระบุถึงตัวบุคคลได้ในระหว่างขั้นตอนการวิจัย</li>
                            <li><span class="font-bold text-black">(ข) ข้อมูลทางสถิติการใช้งาน:</span> สถิติการเข้าถึงหมวดหมู่เบอร์มงคลต่างๆ ความนิยมของกลุ่มตัวเลขแยกตามกลุ่มความต้องการ</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">2. วัตถุประสงค์และประโยชน์ที่ท่านจะได้รับ</h4>
                        <div class="pl-4 border-l-2 border-gray-100 space-y-6">
                            <div>
                                <p class="font-bold text-black">2.1 การพัฒนาความแม่นยำของระบบพยากรณ์ (AI Accuracy)</p>
                                <p class="text-sm">เพื่อวิเคราะห์ว่ารูปแบบตัวเลขใดส่งผลดีต่อสถิติความพึงพอใจของผู้ใช้ และนำไปปรับปรุงอัลกอริทึมการแนะนำเบอร์มงคลให้สอดคล้องกับความต้องการของท่านมากขึ้น</p>
                            </div>
                            <div>
                                <p class="font-bold text-black">2.2 การเพิ่มประสิทธิภาพความมั่นคงปลอดภัยไซเบอร์</p>
                                <p class="text-sm">เพื่อตรวจสอบและวิเคราะห์พฤติกรรมผิดปกติในการเข้าถึงระบบ ช่วยป้องกันการรั่วไหลของข้อมูลและเสริมสร้างเกราะคุ้มกันความเป็นส่วนตัวให้แข็งแกร่งยิ่งขึ้น</p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">3. มาตรการจำกัดสิทธิการเข้าถึงข้อมูลเพื่อการวิจัย</h4>
                        <p>ข้อมูลเพื่อการวิจัยและพัฒนาจะถูกแยกเก็บไว้ในระบบฐานข้อมูลที่แยกอิสระ (Sandbox Environment) โดยผู้ที่มีสิทธิเข้าถึงข้อมูลจะเป็นเพียงเจ้าหน้าที่เฉพาะกลุ่ม (Data Science & Engineer Team) ซึ่งผ่านการตรวจสอบประวัติและลงนามในสัญญาปกปิดความลับเท่านั้น</p>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">4. สิทธิในการคัดค้านการประมวลผล</h4>
                        <p>ท่านมีสิทธิในการปฏิเสธการให้ข้อมูลเพื่อการวิจัยและพัฒนาได้ตลอดเวลา โดยการเพิกถอนความยินยอมนี้จะไม่ส่งผลกระทบต่อสิทธิในการเลือกซื้อหรือใช้บริการจัดหาเบอร์มงคลจากบริษัทฯ ในกรณีปกติ</p>
                    </section>
                </div>

                <div class="mt-16 p-10 bg-[#f9fafb] border-2 border-dashed border-gray-200 text-sm text-gray-600">
                    <p class="font-bold text-black mb-2 text-center text-base">การให้ความยินยอมเพื่อการพัฒนา</p>
                    <p>ข้าพเจ้ายินยอมให้ บริษัท ซุปเปอร์นัมเบอร์ จำกัด นำข้อมูลพฤติกรรมการใช้งานบางส่วนไปประมวลผลภายใต้เงื่อนไขการรักษาความลับ เพื่อนำไปพัฒนาและปรับปรุงคุณภาพการให้บริการจัดหาเบอร์มงคลให้ดียิ่งขึ้น</p>
                </div>
            </div>

            <!-- Page Texture Overlay -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.03] bg-[url('https://www.transparenttextures.com/patterns/paper-fibers.png')]"></div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-12 flex justify-between items-center px-4">
            <a href="{{ route('privacy.personal') }}" class="text-gray-500 hover:text-black transition-all flex items-center gap-2">
                &larr; กลับหน้าที่ 1
            </a>
            <div class="flex gap-4">
                <a href="{{ route('privacy.marketing') }}" class="px-8 py-3 bg-[#2a5d34] text-white font-bold hover:bg-[#1e4a26] transition-all shadow-md">
                    ฉบับที่ 3: โปรโมชั่น &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,700;1,400&display=swap');
    .paper-page-container { font-family: 'Sarabun', sans-serif; }
    .paper-document { min-height: 1000px; border: 1px solid #ddd; }
    .document-body h3 { color: #2a5d34; }
</style>
@endsection
