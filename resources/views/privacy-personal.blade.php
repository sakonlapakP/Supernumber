@extends('layouts.app')

@section('title', 'ความยินยอมในการให้ข้อมูลส่วนบุคคล - Supernumber')

@section('content')
<div class="privacy-page container py-16">
    <div class="max-w-4xl mx-auto">
        <div class="privacy-card bg-white rounded-[40px] shadow-2xl overflow-hidden border border-gold-100">
            <div class="privacy-card__header bg-brown-900 p-12 text-center relative">
                <div class="absolute inset-0 opacity-10 bg-[url('/images/pattern.png')]"></div>
                <div class="privacy-icon w-20 h-20 bg-gold-500 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                    <svg class="w-10 h-10 text-brown-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <h1 class="text-3xl font-extrabold text-white relative z-10">1. ความยินยอมในการให้ข้อมูลส่วนบุคคล</h1>
                <p class="text-gold-200 mt-4 max-w-xl mx-auto relative z-10 opacity-90">ข้อมูลของคุณคือสิ่งสำคัญ เรามุ่งมั่นปกป้องและรักษาความเป็นส่วนตัวอย่างมืออาชีพ</p>
            </div>

            <div class="p-10 md:p-16">
                <div class="prose prose-lg prose-brown max-w-none">
                    <h2 class="text-2xl font-bold text-brown-800 mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-gold-500 rounded-full"></span>
                        วัตถุประสงค์ของการจัดเก็บข้อมูล
                    </h2>
                    <p class="leading-relaxed text-brown-600 mb-8">
                        บริษัท ซุปเปอร์นัมเบอร์ จำกัด ("บริษัทฯ") มีความจำเป็นต้องเก็บรวบรวมข้อมูลส่วนบุคคลของท่าน เพื่อใช้ในการดำเนินงานตามวัตถุประสงค์ดังต่อไปนี้:
                    </p>

                    <div class="grid gap-4 mb-12">
                        <div class="flex gap-5 items-start p-6 rounded-3xl bg-brown-50 border border-brown-100">
                            <div class="w-10 h-10 rounded-full bg-gold-100 flex items-center justify-center flex-shrink-0 text-gold-600 font-bold">✓</div>
                            <p class="text-brown-700">เพื่อใช้ในการยืนยันตัวตนขณะลงชื่อเข้าใช้งานเว็บไซต์ และจัดทำฐานข้อมูลสมาชิก</p>
                        </div>
                        <div class="flex gap-5 items-start p-6 rounded-3xl bg-brown-50 border border-brown-100">
                            <div class="w-10 h-10 rounded-full bg-gold-100 flex items-center justify-center flex-shrink-0 text-gold-600 font-bold">✓</div>
                            <p class="text-brown-700">เพื่อใช้ในการจัดทำเอกสารสำคัญ เช่น ใบสั่งซื้อ สัญญาการใช้งานเบอร์มือถือ หรือใบกำกับภาษี</p>
                        </div>
                        <div class="flex gap-5 items-start p-6 rounded-3xl bg-brown-50 border border-brown-100">
                            <div class="w-10 h-10 rounded-full bg-gold-100 flex items-center justify-center flex-shrink-0 text-gold-600 font-bold">✓</div>
                            <p class="text-brown-700">เพื่อใช้ในการติดต่อประสานงาน และจัดส่งหมายเลขโทรศัพท์ (เบอร์มงคล) ถึงมือคุณอย่างรวดเร็วและถูกต้อง</p>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-brown-800 mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-gold-500 rounded-full"></span>
                        ข้อมูลที่เราจัดเก็บ
                    </h2>
                    <p class="text-brown-600 mb-6">เพื่อให้เราสามารถให้บริการคุณได้อย่างสมบูรณ์แบบ เราจะจัดเก็บข้อมูลดังนี้:</p>
                    <ul class="space-y-3 mb-12">
                        <li class="flex items-center gap-3 text-brown-700"><span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> ชื่อจริง - นามสกุล</li>
                        <li class="flex items-center gap-3 text-brown-700"><span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> หมายเลขโทรพศัพท์ที่ติดต่อได้</li>
                        <li class="flex items-center gap-3 text-brown-700"><span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> ที่อยู่สำหรับการจัดส่งเอกสารและหมายเลขโทรศัพท์</li>
                        <li class="flex items-center gap-3 text-brown-700"><span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span> ข้อมูลทางเทคนิค (IP Address) เพื่อความปลอดภัยของระบบ</li>
                    </ul>

                    <div class="bg-gold-50 p-8 rounded-[30px] border border-gold-200">
                        <p class="text-brown-800 text-sm italic">
                            *บริษัทฯ ขอรับรองว่าจะไม่มีการนำข้อมูลของคุณไปเผยแพร่หรือจำหน่ายให้แก่บุคคลภายนอก หากไม่ได้รับความยินยอมจากคุณ ยกเว้นในกรณีที่กฎหมายกำหนด
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 content-nav flex flex-wrap justify-center gap-4">
            <a href="{{ route('home') }}" class="px-8 py-3 bg-brown-800 text-white rounded-full font-bold hover:bg-brown-900 transition-all shadow-lg">หน้าหลัก</a>
            <a href="{{ route('privacy.development') }}" class="px-8 py-3 border-2 border-gold-500 text-gold-700 rounded-full font-bold hover:bg-gold-50 transition-all">ถัดไป: พัฒนาสินค้า &rarr;</a>
        </div>
    </div>
</div>

<style>
    .privacy-page { font-family: 'Sarabun', sans-serif; }
    .bg-brown-900 { background-color: #2a2321; }
    .bg-brown-50 { background-color: #fbf9f6; }
    .bg-gold-50 { background-color: #fefcf5; }
    .bg-gold-500 { background: linear-gradient(135deg, #f3ca7a 0%, #d8a34a 100%); }
    .text-gold-200 { color: #f3ca7a; }
    .text-gold-400 { color: #d8a34a; }
    .text-gold-600 { color: #d8a34a; }
    .text-gold-700 { color: #b78a3c; }
    .border-gold-100 { border-color: #eee5d8; }
    .border-gold-200 { border-color: #f1dfaf; }
    .border-brown-100 { border-color: #f0e8db; }
</style>
@endsection
