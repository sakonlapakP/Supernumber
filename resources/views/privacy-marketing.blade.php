@extends('layouts.app')

@section('title', 'ความยินยอมเพื่อการตลาดและโปรโมชั่น - Supernumber')

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
                <h1 class="text-2xl font-bold text-black mb-2">นโยบายความเป็นส่วนตัวด้านกิจกรรมทางการตลาดและสื่อสารองค์กร</h1>
                <h2 class="text-xl font-bold text-black mb-4">(Marketing and Communications Privacy Policy)</h2>
                <div class="w-24 h-0.5 bg-black mx-auto mb-6"></div>
                <p class="font-bold text-gray-800">บริษัท ซุปเปอร์นัมเบอร์ จำกัด</p>
                <p class="text-sm text-gray-500 mt-8 text-right">ฉบับวันที่ {{ \Carbon\Carbon::now()->format('j F 2568') }}</p>
            </div>

            <!-- Document Content -->
            <div class="document-body text-[#333] leading-relaxed text-base text-justify">
                <h3 class="font-bold text-lg mb-6 text-[#2a5d34] border-l-4 border-[#2a5d34] pl-4 uppercase">นโยบายความเป็นส่วนตัวด้านกิจกรรมทางการตลาดและการแจ้งข่าวสาร</h3>
                
                <p class="mb-6">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) ตระหนักถึงความสําคัญของการสื่อสารที่มีคุณภาพและเป็นประโยชน์ต่อท่าน บริษัทฯ จึงใคร่ขอความยินยอมเพื่อส่งข้อมูลข่าวสารเกี่ยวกับผลิตภัณฑ์เบอร์มงคล การส่งเสริมการขาย และสิทธิพิเศษที่คัดสรรมาโดยเฉพาะ ดังนี้:
                </p>

                <div class="space-y-10">
                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">1. ประเภทข้อมูลและสิทธิประโยชน์ที่จะได้รับ</h4>
                        <p class="mb-4">หากท่านให้ความยินยอม บริษัทฯ จะแจ้งข่าวสารที่เป็นประโยชน์ต่อท่าน ได้แก่:</p>
                        <ul class="list-none pl-4 space-y-4 text-gray-700">
                            <li><span class="font-bold text-black">● การแจ้งเตือนเบอร์เข้าใหม่ (Priority Notification):</span> สิทธิการเข้าถึงหมายเลขพรีเมียมมาใหม่ก่อนเปิดตัวต่อสาธารณะ</li>
                            <li><span class="font-bold text-black">● ข้อเสนอเชิงพาณิชย์:</span> โค้ดส่วนลดและสิทธิการร่วมแคมเปญชำระเงินในราคาพิเศษสำหรับสมาชิก</li>
                            <li><span class="font-bold text-black">● ข้อมูลวิชาพยากรณ์:</span> ข้อมูลความมูลาเตลูและดวงชะตาที่เกี่ยวข้องกับตัวเลขในชีวิตประจำวัน</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">2. ช่องทางและการรักษาจรรยาบรรณในการสื่อสาร</h4>
                        <p class="mb-4">บริษัทฯ จะใช้ช่องทางการสื่อสารที่ท่านได้เปิดเผยไว้ ดังนี้:</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="p-3 bg-gray-50 border border-gray-100 text-center text-xs font-bold">อีเมล (Email)</div>
                            <div class="p-3 bg-gray-50 border border-gray-100 text-center text-xs font-bold">เอสเอ็มเอส (SMS)</div>
                            <div class="p-3 bg-gray-50 border border-gray-100 text-center text-xs font-bold">แอปพลิเคชัน LINE</div>
                            <div class="p-3 bg-gray-50 border border-gray-100 text-center text-xs font-bold">แอปฯ สื่อสังคมออนไลน์</div>
                        </div>
                        <p class="italic text-sm text-gray-600">บริษัทฯ จะดำเนินการสื่อสารภายใต้จรรยาบรรณการตลาด ไม่ส่งข้อมูลที่เป็นการรบกวน (Spam) และจะไม่นำข้อมูลของท่านไปขายต่อให้แก่บุคคลภายนอกโดยเด็ดขาด</p>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-lg underline decoration-[#2a5d34] underline-offset-8">3. สิทธิในการเพิกถอนความยินยอม</h4>
                        <p>ท่านมีสิทธิในการขอยกเลิกการรับข้อมูลข่าวสารทางการตลาดได้ทุกเวลา (Opt-out) โดยไม่มีเงื่อนไขและไม่มีค่าใช้จ่าย ผ่านช่องทางลิงก์ที่แนบไปในท้ายจดหมายข่าว หรือติดต่อเจ้าหน้าที่ลูกค้าสัมพันธ์ของเราโดยตรง</p>
                    </section>
                </div>

                <div class="mt-16 p-10 bg-[#f9fafb] border-2 border-dashed border-gray-200 text-sm text-gray-600">
                    <p class="font-bold text-black mb-2 text-center text-base">ความยินยอมรับข่าวสารทางการตลาด</p>
                    <p>ข้าพเจ้ายินยอมให้ บริษัท ซุปเปอร์นัมเบอร์ จำกัด วิเคราะห์ข้อมูลเบื้องต้นและจัดส่งข่าวสารสิทธิประโยชน์ รวมถึงโปรโมชั่นต่างๆ ตามช่องทางที่ข้าพเจ้าได้ระบุไว้ เพื่อประโยชน์สูงสุดในการรับบริการจากซุปเปอร์นัมเบอร์</p>
                </div>
            </div>

            <!-- Page Texture Overlay -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.03] bg-[url('https://www.transparenttextures.com/patterns/paper-fibers.png')]"></div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-12 flex justify-between items-center px-4">
            <a href="{{ route('privacy.development') }}" class="text-gray-500 hover:text-black transition-all flex items-center gap-2">
                &larr; กลับหน้าที่ 2
            </a>
            <div class="flex gap-4">
                <a href="{{ route('home') }}" class="px-8 py-3 bg-black text-white font-bold hover:bg-gray-800 transition-all shadow-md">
                    กลับหน้าหลัก
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
