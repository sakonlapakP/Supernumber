@extends('layouts.app')

@section('title', 'ความยินยอมในการให้ข้อมูลส่วนบุคคล - Supernumber')

@section('content')
<div class="paper-page-container py-16 bg-[#f5f5f5] min-h-screen">
    <div class="max-w-[900px] mx-auto">
        <!-- Paper Document -->
        <div class="paper-document bg-white shadow-2xl p-12 md:p-20 relative overflow-hidden">
            <!-- Document Header -->
            <div class="text-center mb-16">
                <div class="mb-6">
                    <img src="{{ asset('images/logo.png') }}" alt="Supernumber Logo" class="h-12 mx-auto grayscale-0">
                </div>
                <h1 class="text-2xl font-bold text-black mb-2">นโยบายการรักษาความมั่นคงปลอดภัยข้อมูลส่วนบุคคล</h1>
                <h2 class="text-xl font-bold text-black mb-4">และความเป็นส่วนตัวสำหรับลูกค้า (Privacy Policy)</h2>
                <div class="w-24 h-0.5 bg-black mx-auto mb-6"></div>
                <p class="font-bold text-gray-800">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                <p class="text-sm text-gray-500 mt-8 text-right">ฉบับวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</p>
            </div>

            <!-- Document Content -->
            <div class="document-body text-[#333] leading-relaxed text-base">
                <h3 class="font-bold text-lg mb-4 text-[#2a5d34] border-l-4 border-[#2a5d34] pl-4">ความยินยอมการให้ข้อมูลส่วนบุคคล</h3>
                
                <p class="mb-6">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (ซึ่งต่อไปนี้จะเรียกว่า "บริษัทฯ") ในฐานะ "ผู้ควบคุมข้อมูลส่วนบุคคล" เคารพสิทธิความเป็นส่วนตัวและให้ความสำคัญกับการคุ้มครองข้อมูลส่วนบุคคลของท่าน บริษัทฯ จึงได้จัดทำหนังสือแสดงความยินยอมฉบับนี้ขึ้น เพื่อแจ้งให้ท่านทราบถึงวัตถุประสงค์และรายละเอียดการเก็บรวบรวม ใช้ หรือเปิดเผยข้อมูลส่วนบุคคล ดังต่อไปนี้:
                </p>

                <div class="space-y-10">
                    <section>
                        <h4 class="font-bold text-black mb-3">1. ข้อมูลส่วนบุคคลที่เราจัดเก็บ</h4>
                        <p class="mb-2">เพื่อให้บริษัทฯ สามารถดำเนินกิจกรรมตามวัตถุประสงค์ในการให้บริการจัดหาเบอร์มงคลและจัดทำเอกสารสำคัญ บริษัทฯ มีความจำเป็นต้องเก็บรวบรวมข้อมูล ดังนี้:</p>
                        <ul class="list-disc pl-6 space-y-1 italic">
                            <li><strong>ข้อมูลระบุตัวตน:</strong> ชื่อ-นามสกุลจริง, เพศ, วันเดือนปีเกิด</li>
                            <li><strong>ข้อมูลการติดต่อ:</strong> หมายเลขโทรศัพท์ที่ใช้งานปัจจุบัน, อีเมลสำหรับการรับเอกสาร</li>
                            <li><strong>ข้อมูลการจัดส่ง:</strong> ที่อยู่ตามหน้าบัตรหรือที่อยู่ที่ระบุไว้เพื่อการจัดส่งหมายเลขโทรศัพท์และเอกสารสัญญา</li>
                            <li><strong>ข้อมูลทางเทคนิค:</strong> หมายเลขไอพี (IP Address) และประวัติการเข้าใช้งานเบื้องต้น</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-[#2a5d34]">2. วัตถุประสงค์ของการประมวลผลข้อมูล (Data Processing Purpose)</h4>
                        <p class="mb-4">บริษัทฯ จะนำข้อมูลดังกล่าวไปใช้ภายใต้ความยินยอมของท่านเพื่อวัตถุประสงค์ดังต่อไปนี้เท่านั้น:</p>
                        <div class="pl-4 border-l-2 border-gray-100 space-y-4 text-sm md:text-base">
                            <p><strong>2.1 เพื่อการยืนยันตัวตนและสมาชิก:</strong> ใช้ระบุตัวตนของท่านในการเข้าถึงระบบสมาชิกและการรับสิทธิพิเศษเฉพาะรายบุคคล</p>
                            <p><strong>2.2 เพื่อการดำเนินงานตามสัญญา:</strong> ใช้ในการจัดทำใบสั่งซื้อ, สัญญาการจองหมายเลขโทรศัพท์ (เบอร์มงคล), หนังสือมอบอำนาจ หรือเอกสารทางภาษีที่เกี่ยวข้อง</p>
                            <p><strong>2.3 เพื่อการจัดส่งและประสานงาน:</strong> ใช้ข้อมูลที่อยู่ให้เจ้าหน้าที่ขนส่งดำเนินการนำส่งซิมการ์ดและเอกสารสัญญาให้ถึงมือท่านอย่างถูกต้องและรัดกุม</p>
                        </div>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">3. มาตรการรักษาความปลอดภัยของข้อมูล</h4>
                        <p>บริษัทฯ ได้จัดให้มีมาตรการรักษาความมั่นคงปลอดภัยของข้อมูลส่วนบุคคลที่เหมาะสม เพื่อป้องกันการสูญหาย การเข้าถึง การใช้ การเปลี่ยนแปลง การแก้ไข หรือการเปิดเผยข้อมูลส่วนบุคคลโดยไม่ได้รับอนุญาตหรือโดยมิชอบ โดยใช้ระบบจัดเก็บข้อมูลที่มีการเข้ารหัสความปลอดภัยระดับมาตรฐานสากล</p>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">4. สิทธิของเจ้าของข้อมูล (Data Subject Rights)</h4>
                        <p class="mb-2">ท่านในฐานะเจ้าของข้อมูลส่วนบุคคล มีสิทธิตามกฎหมาย PDPA ดังนี้:</p>
                        <ul class="grid md:grid-cols-2 gap-x-8 gap-y-2 list-none p-0">
                            <li class="flex items-center gap-2"><span class="text-[#2a5d34]">●</span> สิทธิในการขอเข้าถึงและรับสำเนาข้อมูล</li>
                            <li class="flex items-center gap-2"><span class="text-[#2a5d34]">●</span> สิทธิในการขอแก้ไขข้อมูลให้เป็นปัจจุบัน</li>
                            <li class="flex items-center gap-2"><span class="text-[#2a5d34]">●</span> สิทธิในการขอระงับการใช้หรือขอลบข้อมูล</li>
                            <li class="flex items-center gap-2"><span class="text-[#2a5d34]">●</span> สิทธิในการเพิกถอนความยินยอม</li>
                        </ul>
                    </section>
                </div>

                <div class="mt-16 p-8 bg-gray-50 border border-gray-200 rounded-none italic text-sm text-gray-600">
                    <p>หมายเหตุ: ข้อมูลส่วนบุคคลที่จัดเก็บในหน้านี้ จะถูกนำไปใช้เพื่อการดำเนินงานพื้นฐานในการให้บริการของ บริษัท ซุปเปอร์นัมเบอร์ จำกัด เท่านั้น โดยท่านสามารถศึกษารายละเอียดเพิ่มเติมได้จากนโยบายความเป็นส่วนตัวฉบับเต็มของทางบริษัทฯ</p>
                </div>
            </div>

            <!-- Fake Paper Texture Overlay -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.03] bg-[url('https://www.transparenttextures.com/patterns/paper-fibers.png')]"></div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-12 flex justify-between items-center px-4">
            <a href="{{ route('home') }}" class="text-gray-500 hover:text-black transition-all flex items-center gap-2">
                &larr; กลับหน้าหลัก
            </a>
            <div class="flex gap-4">
                <a href="{{ route('privacy.development') }}" class="px-8 py-3 bg-[#2a5d34] text-white font-bold hover:bg-[#1e4a26] transition-all shadow-md">
                    ฉบับที่ 2: พัฒนาสินค้า &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,700;1,400&display=swap');
    
    .paper-page-container {
        font-family: 'Sarabun', sans-serif;
    }
    .paper-document {
        min-height: 1000px;
        border: 1px solid #ddd;
    }
    .document-body h3 {
        color: #2a5d34;
    }
    .document-body ul li {
        margin-bottom: 8px;
    }
    @media print {
        .bg-[#f5f5f5] { background: white; }
        .paper-document { box-shadow: none; border: none; p: 0; }
        .content-nav { display: none; }
    }
</style>
@endsection
