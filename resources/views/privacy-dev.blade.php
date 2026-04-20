@extends('layouts.app')

@section('title', 'ความยินยอมเพื่อพัฒนาสินค้าหรือบริการ - Supernumber')

@section('content')
<div class="privacy-page container py-16">
    <div class="max-w-4xl mx-auto">
        <div class="privacy-card bg-white rounded-[40px] shadow-2xl overflow-hidden border border-gold-100">
            <div class="privacy-card__header bg-brown-900 p-12 text-center relative">
                <div class="absolute inset-0 opacity-10 bg-[url('/images/pattern.png')]"></div>
                <div class="privacy-icon w-20 h-20 bg-gold-500 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl relative z-10">
                    <svg class="w-10 h-10 text-brown-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-3xl font-extrabold text-white relative z-10">2. การพัฒนาสินค้าหรือบริการให้ดียิ่งขึ้น</h1>
                <p class="text-gold-200 mt-4 max-w-xl mx-auto relative z-10 opacity-90">ข้อมูลของคุณช่วยให้เราสร้างสรรค์บริการที่แม่นยำและถูกใจคุณมากยิ่งขึ้นในอนาคต</p>
            </div>

            <div class="p-10 md:p-16">
                <div class="prose prose-lg prose-brown max-w-none">
                    <h2 class="text-2xl font-bold text-brown-800 mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-gold-400 rounded-full"></span>
                        ทำไมเราจึงต้องพัฒนา?
                    </h2>
                    <p class="leading-relaxed text-brown-600 mb-8">
                        บริษัท ซุปเปอร์นัมเบอร์ จำกัด ("บริษัทฯ") มีความมุ่งมั่นที่จะไม่หยุดนิ่งในการคัดสรรและวิเคราะห์เบอร์มงคล การที่คุณยินยอมให้นำข้อมูลบางส่วนไปใช้ในการวิเคราะห์จะช่วยให้:
                    </p>

                    <div class="grid md:grid-cols-2 gap-6 mb-12">
                        <div class="p-8 rounded-3xl bg-brown-50 border border-brown-100 text-center hover:shadow-lg transition-all">
                            <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center mx-auto mb-4 shadow-sm text-gold-600">📊</div>
                            <h4 class="font-bold text-brown-800 mb-2">วิเคราะห์ความต้องการ</h4>
                            <p class="text-sm text-brown-600">ช่วยระบุว่าเบอร์มงคลกลุ่มไหนที่เป็นที่ต้องการ และจัดหาเบอร์ใหม่ๆ มาให้คุณได้ไวกว่าเดิม</p>
                        </div>
                        <div class="p-8 rounded-3xl bg-brown-50 border border-brown-100 text-center hover:shadow-lg transition-all">
                            <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center mx-auto mb-4 shadow-sm text-gold-600">🖥️</div>
                            <h4 class="font-bold text-brown-800 mb-2">ปรับปรุงการใช้งาน</h4>
                            <p class="text-sm text-brown-600">วิเคราะห์พฤติกรรมการใช้งานในเว็บ เพื่อปรับหน้าตาเว็บไซต์ให้ใช้ง่ายขึ้น รวดเร็วขึ้น และปลอดภัยขึ้น</p>
                        </div>
                    </div>

                    <h2 class="text-2xl font-bold text-brown-800 mb-6 flex items-center gap-3">
                        <span class="w-2 h-8 bg-gold-400 rounded-full"></span>
                        ข้อมูลที่ใช้ในการวิเคราะห์
                    </h2>
                    <p class="text-brown-600 mb-6">ข้อมูลเหล่านี้จะถูกจัดเก็บในรูปแบบที่ไม่สามารถระบุตัวตนรายบุคคล (Anonymized) เพื่อใช้ในการสถิติจนถึงระดับสูงสุดของความปลอดภัย:</p>
                    <ul class="space-y-4 mb-12">
                        <li class="flex items-start gap-4 p-4 rounded-2xl bg-gold-50">
                            <div class="text-gold-600 font-bold">01</div>
                            <p class="text-sm text-brown-700">สถิติการคลิกดูเบอร์ในแต่ละหมวดหมู่ หรืออาชีพที่คุณสนใจ</p>
                        </li>
                        <li class="flex items-start gap-4 p-4 rounded-2xl bg-gold-50">
                            <div class="text-gold-600 font-bold">02</div>
                            <p class="text-sm text-brown-700">ข้อมูลการค้นหาเบอร์ เพื่อให้ระบบ AI แนะนำเบอร์มงคลได้แม่นยำยิ่งขึ้น</p>
                        </li>
                        <li class="flex items-start gap-4 p-4 rounded-2xl bg-gold-50">
                            <div class="text-gold-600 font-bold">03</div>
                            <p class="text-sm text-brown-700">การวัดประสิทธิภาพของแคมเปญต่างๆ เพื่อมอบส่วนลดได้ตรงใจคุณในอนาคต</p>
                        </li>
                    </ul>

                    <div class="p-8 bg-brown-900 rounded-[30px] text-white flex gap-6 items-center">
                        <div class="flex-shrink-0 text-3xl">💡</div>
                        <p class="text-sm opacity-90 leading-relaxed">
                            ความยินยอมของคุณคือแรงผลักดันสำคัญที่ทำให้เราสามารถวิจัยและพัฒนาวิชาพยากรณ์รวมถึงเทคโนโลยีการคัดเบอร์มงคลให้ดียิ่งขึ้นในทุกๆ วัน
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12 content-nav flex flex-wrap justify-center gap-4">
            <a href="{{ route('privacy.personal') }}" class="px-8 py-3 border-2 border-brown-200 text-brown-600 rounded-full font-bold hover:bg-brown-50 transition-all">&larr; กลับหน้าจัดเก็บข้อมูล</a>
            <a href="{{ route('privacy.marketing') }}" class="px-8 py-3 bg-brown-800 text-white rounded-full font-bold hover:bg-brown-900 transition-all shadow-lg">ถัดไป: โปรโมชั่น &rarr;</a>
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
    .border-brown-100 { border-color: #f0e8db; }
</style>
@endsection
