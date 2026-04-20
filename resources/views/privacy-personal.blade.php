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
            <div class="document-body text-[#333] leading-relaxed text-base text-justify">
                <h3 class="font-bold text-lg mb-6 text-[#2a5d34] border-l-4 border-[#2a5d34] pl-4 uppercase">ประกาศนโยบายความเป็นส่วนตัว (Privacy Notice)</h3>
                
                <p class="mb-6">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (ต่อไปนี้จะเรียกว่า "บริษัทฯ") ในฐานะ "ผู้ควบคุมข้อมูลส่วนบุคคล" ตระหนักถึงความรับผิดชอบในการคุ้มครองข้อมูลส่วนบุคคลของท่าน บริษัทฯ จึงได้จัดทำประกาศนโยบายความเป็นส่วนตัวนี้ขึ้น เพื่อชี้แจงถึงวิธีการประมวลผลข้อมูลส่วนบุคคลของท่านในส่วนที่เกี่ยวเนื่องกับการใช้บริการจัดหาและสั่งจองหมายเลขโทรศัพท์มงคล รวมถึงการเข้าถึงเว็บไซต์ของเรา
                </p>

                <div class="space-y-10">
                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">1. ประเภทข้อมูลส่วนบุคคลที่เราเก็บรวบรวม</h4>
                        <p class="mb-4 text-gray-700">บริษัทฯ จะดำเนินการเก็บรวบรวมข้อมูลส่วนบุคคลของท่านเท่าที่จำเป็นภายใต้วัตถุประสงค์ที่กำหนด ดังนี้:</p>
                        <ul class="list-none pl-4 space-y-4">
                            <li><span class="font-bold text-black">(ก) ข้อมูลรายละเอียดส่วนตัว (Identity Data):</span> เช่น ชื่อ, นามสกุล, วันเดือนปีเกิด, เพศ และข้อมูลระบุตัวตนอื่นๆ เพื่อใช้ในการทำสัญญา</li>
                            <li><span class="font-bold text-black">(ข) ข้อมูลติดต่อ (Contact Data):</span> เช่น ที่อยู่สำหรับการจัดส่งซิมการ์ด, หมายเลขโทรศัพท์ที่ติดต่อได้, ที่อยู่อิเล็กทรอนิกส์ (Email)</li>
                            <li><span class="font-bold text-black">(ค) ข้อมูลธุรกรรม (Transaction Data):</span> รายละเอียดการสั่งจองเบอร์มงคล, รายละเอียดการชำระเงิน และประวัติการรับบริการ</li>
                            <li><span class="font-bold text-black">(ง) ข้อมูลทางเทคนิคและพฤติกรรม (Technical and Usage Data):</span> หมายเลข IP, ประวัติการใช้เว็บ และระบบรักษาความปลอดภัยเบื้องต้น</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">2. วัตถุประสงค์และฐานทางกฎหมายในการประมวลผลข้อมูล</h4>
                        <table class="w-full border-collapse border border-gray-200 mt-4 text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border border-gray-200 p-3 text-left">วัตถุประสงค์</th>
                                    <th class="border border-gray-200 p-3 text-left">ฐานทางกฎหมายในการจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="border border-gray-200 p-3">จัดทำสัญญาซื้อขายและส่งมอบหมายเลขโทรศัพท์มือถือ (เบอร์มงคล)</td>
                                    <td class="border border-gray-200 p-3">ฐานสัญญา (Contractual Basis)</td>
                                </tr>
                                <tr>
                                    <td class="border border-gray-200 p-3">การระบุตัวตนสมาชิกและการรักษาความปลอดภัยของระบบเว็บไซต์</td>
                                    <td class="border border-gray-200 p-3">ฐานประโยชน์อันชอบธรรม (Legitimate Interest)</td>
                                </tr>
                                <tr>
                                    <td class="border border-gray-200 p-3">การปฏิบัติตามกฎหมายคอมพิวเตอร์และกฎหมายที่เกี่ยวข้องของสำนักงาน กสทช.</td>
                                    <td class="border border-gray-200 p-3">ฐานการปฏิบัติตามกฎหมาย (Legal Obligation)</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">3. การเปิดเผยข้อมูลส่วนบุคคลต่อบุคคลภายนอก</h4>
                        <p class="mb-2">บริษัทฯ อาจมีความจำเป็นต้องเปิดเผยข้อมูลของท่านเท่าที่จำเป็นให้แก่คู่ค้าภายนอกเพื่อการให้บริการ ดังนี้:</p>
                        <ul class="list-disc pl-8 space-y-2">
                            <li><strong>ผู้ให้บริการขนส่งสินค้า (Logistics):</strong> เพื่อการนำส่งซิมการ์ดและเอกสารสำคัญถึงท่าน</li>
                            <li><strong>ผู้ให้บริการด้านชำระเงินและสถาบันการเงิน:</strong> เพื่อความปลอดภัยในการทำธุรกรรม</li>
                            <li><strong>หน่วยงานราชการ:</strong> ในกรณีที่มีคำสั่งศาลหรือกฎหมายกำหนดให้เปิดเผย</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">4. มาตรการรักษาความปลอดภัยและความลับของข้อมูล</h4>
                        <p>บริษัทฯ ให้ความสำคัญกับการรักษาความมั่นคงปลอดภัยตามมาตรฐาน ISO หรือมาตรฐานความลับสูงสุด โดยข้อมูลจะถูกเก็บรักษาไว้ในระบบเซิร์ฟเวอร์ที่มีความปลอดภัยสูง มีการจำกัดสิทธิการเข้าถึงข้อมูล (Identity and Access Management) และมีการตรวจสอบความปลอดภัยอย่างสม่ำเสมอ</p>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">5. สิทธิของท่านภายใต้พระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล</h4>
                        <p class="mb-4">ท่านสามารถใช้สิทธิในการเข้าถึงข้อมูล, ขอสำเนา, ขอแก้ไข, ขอระงับใช้ หรือขอลบข้อมูลของท่านได้ทุกเมื่อที่สำนักงานใหญ่ของบริษัทฯ หรือผ่านช่องทางที่กำหนดโดยแจ้งความจำนงเป็นลายลักษณ์อักษร</p>
                    </section>
                </div>

                <div class="mt-16 p-10 bg-[#f9fafb] border-2 border-dashed border-gray-200 text-sm text-gray-600">
                    <p class="font-bold text-black mb-2 text-center text-base">การให้ความยินยอมและการรับรองข้อมูล</p>
                    <p>ข้าพเจ้ารับทราบวัตถุประสงค์ในการประมวลผลข้อมูลส่วนบุคคลของ บริษัท ซุปเปอร์นัมเบอร์ จำกัด และมีความเข้าใจเกี่ยวกับมาตรการการรักษาความลับอย่างถ่องแท้ จึงได้ให้ความยินยอมในการจัดการข้อมูลเพื่อบรรลุวัตถุประสงค์ในการให้บริการจัดหาเบอร์มงคลอย่างเป็นทางการ</p>
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
