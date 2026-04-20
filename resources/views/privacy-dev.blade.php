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
            <div class="document-body text-[#333] leading-relaxed text-base">
                <h3 class="font-bold text-lg mb-4 text-[#2a5d34] border-l-4 border-[#2a5d34] pl-4">2. ความยินยอมเพื่อพัฒนาสินค้าหรือบริการให้ดียิ่งขึ้น</h3>
                
                <p class="mb-6">
                    เพื่อให้การเข้าถึงบริการจัดหาเบอร์มงคลของท่านได้รับความพึงพอใจสูงสุด บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) มีความประสงค์จะขอความยินยอมจากท่านในการนำข้อมูลไปวิเคราะห์และประมวลผลเพื่อการพัฒนา โดยมีรายละเอียดดังต่อไปนี้:
                </p>

                <div class="space-y-10">
                    <section>
                        <h4 class="font-bold text-black mb-3">1. ลักษณะของข้อมูลที่นำไปประมวลผล</h4>
                        <p class="mb-2">บริษัทฯ จะนำข้อมูลในรูปแบบสถิติหรือข้อมูลที่ไม่สามารถระบุตัวบุคคลได้โดยตรง (Pseudo-anonymized Data) มาดำเนินการ ดังนี้:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li>พฤติกรรมการค้นหาและรูปแบบการเลือกหมวดหมู่เบอร์มงคล</li>
                            <li>ระยะเวลาการใช้งานในแต่ละส่วนของเว็บไซต์ และอัตราการโต้ตอบกับระบบ AI แนะนำเบอร์</li>
                            <li>อุปกรณ์และช่องทางที่ใช้เข้าถึงบริการ เพื่อเพิ่มประสิทธิภาพการแสดงผล</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-[#2a5d34]">2. วัตถุประสงค์ในการวิเคราะห์ (Analysis Purposes)</h4>
                        <p class="mb-4">ข้อมูลของท่านจะมีส่วนช่วยในการสร้างสรรค์ระบบที่แม่นยำยิ่งขึ้น ดังนี้:</p>
                        <div class="pl-4 border-l-2 border-gray-100 space-y-4">
                            <p><strong>2.1 การพัฒนาระบบ AI พยากรณ์:</strong> เพื่อประมวลผลว่าศาสตร์พยากรณ์รวมถึงกลุ่มตัวเลขใดที่มีอิทธิพลต่อความต้องการของผู้ใช้ เพื่อปรับปรุงอัลกอริทึมการแนะนำเบอร์ให้ตรงจุด</p>
                            <p><strong>2.2 การเพิ่มประสิทธิภาพความปลอดภัย:</strong> วิเคราะห์รูปแบบการเข้าถึงเพื่อป้องกันการโจมตีทางเทคนิค และเสริมความแข็งแกร่งของระบบปกป้องข้อมูลสมาชิก</p>
                            <p><strong>2.3 การปรับแต่งประสบการณ์ส่วนบุคคล:</strong> พัฒนาระบบแสดงผลเพื่อให้ท่านสามารถค้นหาเบอร์ที่เหมาะสมกับอาชีพและดวงชะตาได้ง่ายและรวดเร็วที่สุด</p>
                        </div>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">3. การคุ้มครองข้อมูลในระหว่างการพัฒนา</h4>
                        <p>บริษัทฯ ขอยืนยันว่าการประมวลผลข้อมูลเพื่อการพัฒนาจะกระทำผ่านระบบปิด (Dedicated Environment) โดยจำกัดสิทธิการเข้าถึงข้อมูลเฉพาะเจ้าหน้าที่วิจัยและพัฒนาที่ได้รับมอบหมายเท่านั้น และข้อมูลจะถูกทำลายทันทีเมื่อบรรลุวัตถุประสงค์ในการจัดทำสถิติ</p>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">4. การเพิกถอนความยินยอม</h4>
                        <p>ท่านสามารถใช้สิทธิในการระงับการให้ข้อมูลเพื่อการพัฒนาได้ทุกเวลา โดยจะไม่มีผลกระทบต่อการรับบริการพื้นฐานในการสั่งซื้อเบอร์มงคลตามปกติของท่าน</p>
                    </section>
                </div>

                <div class="mt-16 p-8 bg-gray-50 border border-gray-200 rounded-none italic text-sm text-gray-600">
                    <p>เอกสารฉบับนี้จัดทำขึ้นภายใต้มาตรฐาน PDPA เพื่อความเป็นส่วนตัวสูงสุดของลูกค้าซุปเปอร์นัมเบอร์</p>
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
