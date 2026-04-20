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
            <div class="document-body text-[#333] leading-relaxed text-base">
                <h3 class="font-bold text-lg mb-4 text-[#2a5d34] border-l-4 border-[#2a5d34] pl-4">3. การตลาดและโปรโมชั่นพิเศษ</h3>
                
                <p class="mb-6">
                    บริษัท ซุปเปอร์นัมเบอร์ จำกัด (บริษัทฯ) มีความมุ่งมั่นที่จะนำเสนอสิ่งที่ดีที่สุดให้แก่สมาชิกคนสำคัญ เพื่อให้ท่านไม่พลาดสิทธิประโยชน์และข้อมูลมงคลฉบับพิเศษ บริษัทฯ จึงใคร่ขอความยินยอมจากท่านในการสื่อสารข้อมูลทางการตลาด ดังต่อไปนี้:
                </p>

                <div class="space-y-10">
                    <section>
                        <h4 class="font-bold text-black mb-3">1. ประเภทของข้อมูลและข่าวสารที่จะได้รับ</h4>
                        <p class="mb-2">หากท่านให้ความยินยอม ท่านจะได้รับสิทธิพิเศษที่คัดสรรมาเพื่อท่านโดยเฉพาะ ได้แก่:</p>
                        <ul class="list-disc pl-6 space-y-1">
                            <li><strong>รายการเบอร์มาใหม่ (New Arrivals):</strong> การแจ้งเตือนเมื่อมีกลุ่มเบอร์มงคลระดับพรีเมียมหรือเบอร์หายากเข้าสู่คลังก่อนใคร</li>
                            <li><strong>ข้อเสนอส่งเสริมการขาย:</strong> โค้ดส่วนลดพิเศษ, แคมเปญจองเบอร์ราคาพิเศษ และสิทธิพิเศษในวันสำคัญต่างๆ</li>
                            <li><strong>คอนเทนต์เสริมดวง:</strong> บทวิเคราะห์วิชาพยากรณ์รวมถึงเคล็ดลับการใช้ตัวเลขมงคลรายสัปดาห์</li>
                        </ul>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3 text-[#2a5d34]">2. ช่องทางการสื่อสาร (Communication Channels)</h4>
                        <p class="mb-4">บริษัทฯ จะใช้ช่องทางที่เป็นทางการในการติดต่อท่าน ดังนี้:</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 border border-gray-100 rounded-none text-center">จดหมายอิเล็กทรอนิกส์ (Email)</div>
                            <div class="p-4 bg-gray-50 border border-gray-100 rounded-none text-center">ข้อความสั้น (SMS)</div>
                            <div class="p-4 bg-gray-50 border border-gray-100 rounded-none text-center">แอปพลิเคชัน LINE Official</div>
                            <div class="p-4 bg-gray-50 border border-gray-100 rounded-none text-center">การติดต่อผ่านโทรศัพท์ (Direct Call)</div>
                        </div>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">3. มาตรฐานการปกป้องความเป็นส่วนตัวในการสื่อสาร</h4>
                        <p>บริษัทฯ จะไม่ทำการสื่อสารข้อมูลในลักษณะที่ก่อให้เกิดความรำคาญ (Spam) โดยจะเลือกส่งข้อมูลที่คาดว่าจะเป็นประโยชน์ต่อท่านตามประวัติการใช้งานและดวงชะตาที่ท่านสนใจเป็นสำคัญ และจะไม่มีการเปิดเผยช่องทางการติดต่อของท่านให้แก่บริษัทโฆษณาภายนอกโดยเด็ดขาด</p>
                    </section>

                    <section>
                        <h4 class="font-bold text-black mb-3">4. สิทธิในการยกเลิก (Opt-out Rights)</h4>
                        <p>ท่านสามารถเพิกถอนความยินยอมในการรับข้อมูลทางการตลาดได้ทุกเมื่อผ่านลิงก์ยกเลิกรับข่าวสารในอีเมล หรือแจ้งความประสงค์ผ่านเจ้าหน้าที่ฝ่ายบริการลูกค้าของเรา โดยการยกเลิกนี้จะไม่มีค่าธรรมเนียมใดๆ ทั้งสิ้น</p>
                    </section>
                </div>

                <div class="mt-16 p-8 bg-gray-50 border border-gray-200 rounded-none italic text-sm text-gray-600">
                    <p>สิทธิประโยชน์ของคุณคือหน้าที่ของเรา ขอบคุณที่ไว้วางใจให้ซุปเปอร์นัมเบอร์ดูแลความมงคลของคุณ</p>
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
