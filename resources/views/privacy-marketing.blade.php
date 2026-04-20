@extends('layouts.app')

@section('title', 'ความยินยอมเพื่อการตลาดและโปรโมชั่น - Supernumber')

@section('content')
<div class="privacy-page container py-16">
    <div class="max-w-4xl mx-auto">
        <div class="privacy-card bg-white rounded-[40px] shadow-2xl overflow-hidden border border-gold-100">
            <div class="privacy-card__header bg-brown-900 p-12 text-center relative">
                <div class="absolute inset-0 opacity-10 bg-[url('/images/pattern.png')]"></div>
                <div class="privacy-icon w-20 h-20 bg-gold-500 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                    <svg class="w-10 h-10 text-brown-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167a2.45 2.45 0 00-1.243-1.391L3 11.751V10.5l1.193-.52a2.45 2.45 0 001.243-1.391l2.147-6.167a1.76 1.76 0 013.417.592zM15 12l4 4m0 0l4-4m-4 4V4"></path></svg>
                </div>
                <h1 class="text-3xl font-extrabold text-white relative z-10">3. การตลาดและโปรโมชั่นพิเศษ</h1>
                <p class="text-gold-200 mt-4 max-w-xl mx-auto relative z-10 opacity-90">เชื่อมต่อกับข้อเสนอที่ดีที่สุด และเบอร์มงคลมาใหม่ก่อนใคร เพื่อความมงคลที่เหนือกว่า</p>
            </div>

            <div class="p-10 md:p-16">
                <div class="prose prose-lg prose-brown max-w-none">
                    <h2 class="text-2xl font-bold text-brown-800 mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-gold-400 rounded-full"></span>
                        รับสิทธิประโยชน์ที่เราเตรียมไว้ให้
                    </h2>
                    <p class="leading-relaxed text-brown-600 mb-8">
                        เมื่อคุณกดยินยอมให้ข้อมูลเพื่อการสื่อสารทางการตลาด คุณจะไม่พลาดข้อมูลสำคัญที่คัดสรรมาเป็นพิเศษสำหรับสมาชิก "ซุปเปอร์นัมเบอร์" เท่านั้น:
                    </p>

                    <div class="space-y-6 mb-12">
                        <div class="flex items-center gap-6 p-8 rounded-3xl bg-brown-50 border border-brown-100 hover:border-gold-300 transition-all">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm text-2xl">🎁</div>
                            <div>
                                <h4 class="font-bold text-brown-800 mb-1">โปรโมชั่นและส่วนลดพิเศษ</h4>
                                <p class="text-sm text-brown-600">รับโค้ดส่วนลดในการจองเบอร์มงคล หรือโปรโมชั่นแพ็กเกจราคาพิเศษที่มีเฉพาะช่วงเวลา</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 p-8 rounded-3xl bg-brown-50 border border-brown-100 hover:border-gold-300 transition-all">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm text-2xl">✨</div>
                            <div>
                                <h4 class="font-bold text-brown-800 mb-1">แจ้งเตือนเบอร์เข้าใหม่</h4>
                                <p class="text-sm text-brown-600">เป็นคนแรกที่ได้รับการแจ้งเตือนเมื่อมีกลุ่มเบอร์หายาก หรือเลขมงคลเกรดพรีเมียมเข้ามาในสต็อก</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 p-8 rounded-3xl bg-brown-50 border border-brown-100 hover:border-gold-300 transition-all">
                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm text-2xl">📅</div>
                            <div>
                                <h4 class="font-bold text-brown-800 mb-1">กิจกรรมและดูดวงรายสัปดาห์</h4>
                                <p class="text-sm text-brown-600">รับคอนเทนต์พิเศษเกี่ยวกับโชคลาภ และวิชาพยากรณ์ที่ส่งตรงถึงอีเมลหรือเบอร์โทรศัพท์ของคุณ</p>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-brown-800 mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-gold-400 rounded-full"></span>
                        อิสระในการรับข้อมูล
                    </h2>
                    <p class="text-brown-600 mb-8">เราเคารพในความเป็นส่วนตัวของคุณเสมอ คุณสามารถเลือกช่องทางในการรับข้อมูลได้ตามความสมัครใจ:</p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                        <div class="text-center p-4 bg-gold-50 rounded-2xl border border-gold-200">
                            <span class="block text-2xl mb-2">✉️</span>
                            <span class="text-xs font-bold text-brown-600 uppercase tracking-wider">Email</span>
                        </div>
                        <div class="text-center p-4 bg-gold-50 rounded-2xl border border-gold-200">
                            <span class="block text-2xl mb-2">📱</span>
                            <span class="text-xs font-bold text-brown-600 uppercase tracking-wider">SMS</span>
                        </div>
                        <div class="text-center p-4 bg-gold-50 rounded-2xl border border-gold-200">
                            <span class="block text-2xl mb-2">💬</span>
                            <span class="text-xs font-bold text-brown-600 uppercase tracking-wider">LINE</span>
                        </div>
                        <div class="text-center p-4 bg-gold-50 rounded-2xl border border-gold-200">
                            <span class="block text-2xl mb-2">📞</span>
                            <span class="text-xs font-bold text-brown-600 uppercase tracking-wider">Direct Call</span>
                        </div>
                    </div>

                    <div class="text-center p-10 bg-brown-50 rounded-[40px] border border-dashed border-brown-200">
                        <h4 class="font-bold text-brown-800 mb-2">ยกเลิกได้ทุกเมื่อ</h4>
                        <p class="text-sm text-brown-600 max-w-sm mx-auto">หากท่านไม่ต้องการรับข่าวสาร สามารถแจ้งยกเลิกได้ผ่านการกดยกเลิกรับข่าวสารในอีเมล หรือติดต่อ LINE ของเราได้ทันที</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 content-nav flex flex-wrap justify-center gap-4">
            <a href="{{ route('privacy.development') }}" class="px-8 py-3 border-2 border-brown-200 text-brown-600 rounded-full font-bold hover:bg-brown-50 transition-all">&larr; กลับหน้าพัฒนาสินค้า</a>
            <a href="{{ route('home') }}" class="px-8 py-3 bg-brown-800 text-white rounded-full font-bold hover:bg-brown-900 transition-all shadow-lg">กลับสู่หน้าหลัก</a>
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
    .border-brown-200 { border-color: #eee5d8; }
</style>
@endsection
