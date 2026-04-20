@extends('layouts.app')

@section('title', 'นโยบายความเป็นส่วนตัว - Supernumber')

@section('content')
<div class="privacy-page container py-12">
    <div class="privacy-header text-center mb-12">
        <h1 class="text-4xl font-extrabold text-brown-900 mb-4">นโยบายความเป็นส่วนตัว</h1>
        <p class="text-lg text-brown-600 max-w-2xl mx-auto">
            บริษัท ซุปเปอร์นัมเบอร์ จำกัด ให้ความสำคัญในการคุ้มครองข้อมูลส่วนบุคคลของท่าน 
            เราจึงได้จัดทำนโยบายนี้เพื่อแจ้งให้ทราบถึงแนวทางปฏิบัติในการเก็บรวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคล
        </p>
    </div>

    <div class="privacy-content max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-3xl shadow-xl border border-brown-100">
        <article class="prose prose-brown max-w-none">
            <section class="mb-10">
                <h2 class="text-2xl font-bold text-brown-800 mb-4 border-b pb-2">1. บทนำ</h2>
                <p>บริษัท ซุปเปอร์นัมเบอร์ จำกัด ("บริษัทฯ") ตระหนักถึงความสำคัญของการคุ้มครองข้อมูลส่วนบุคคลของท่าน และมุ่งมั่นที่จะปฏิบัติตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) นโยบายฉบับนี้อธิบายถึงวิธีการที่เราจัดการกับข้อมูลส่วนบุคคลของท่านเมื่อท่านใช้งานเว็บไซต์หรือรับบริการจากเรา</p>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-bold text-brown-800 mb-4 border-b pb-2">2. ข้อมูลส่วนบุคคลที่เราเก็บรวบรวม</h2>
                <p>เรามีการเก็บรวบรวมข้อมูลส่วนบุคคลของท่านที่จำเป็นเพื่อให้บรรลุวัตถุประสงค์ในการให้บริการ ดังนี้:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>ข้อมูลระบุตัวตน:</strong> ชื่อ, นามสกุล, เพศ, วันเดือนปีเกิด</li>
                    <li><strong>ข้อมูลการติดต่อ:</strong> เบอร์โทรศัพท์, อีเมล, ที่อยู่สำหรับการจัดส่ง</li>
                    <li><strong>ข้อมูลทางเทคนิค:</strong> ที่อยู่ IP, ประเภทเบราว์เซอร์, ประวัติการเข้าชมเว็บไซต์ผ่านคุกกี้</li>
                </ul>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-bold text-brown-800 mb-4 border-b pb-2">3. วัตถุประสงค์ในการใช้ข้อมูล</h2>
                <p>เราใช้ข้อมูลส่วนบุคคลของท่านเพื่อวัตถุประสงค์หลักๆ ดังนี้:</p>
                <div class="grid gap-6 mt-4">
                    <div class="flex gap-4 p-4 bg-brown-50 rounded-2xl">
                        <div class="flex-shrink-0 w-8 h-8 bg-gold-500 text-white rounded-full flex items-center justify-center font-bold">1</div>
                        <div>
                            <h4 class="font-bold text-brown-800 mb-1">การให้บริการพื้นฐาน</h4>
                            <p class="text-sm">เพื่อประมวลผลคำสั่งซื้อ การจัดส่งเบอร์มงคล การออกเอกสารสัญญา และการให้บริการดูแลลูกค้า</p>
                        </div>
                    </div>
                    <div class="flex gap-4 p-4 bg-brown-50 rounded-2xl">
                        <div class="flex-shrink-0 w-8 h-8 bg-gold-500 text-white rounded-full flex items-center justify-center font-bold">2</div>
                        <div>
                            <h4 class="font-bold text-brown-800 mb-1">การพัฒนาสินค้าและบริการ</h4>
                            <p class="text-sm">เพื่อวิเคราะห์ความต้องการและการใช้งานเว็บไซต์ นำไปสู่การปรับปรุงหน้าตาและบริการให้ดียิ่งขึ้น</p>
                        </div>
                    </div>
                    <div class="flex gap-4 p-4 bg-brown-50 rounded-2xl">
                        <div class="flex-shrink-0 w-8 h-8 bg-gold-500 text-white rounded-full flex items-center justify-center font-bold">3</div>
                        <div>
                            <h4 class="font-bold text-brown-800 mb-1">การตลาดและการแจ้งข้อมูล</h4>
                            <p class="text-sm">เพื่อนำเสนอโปรโมชั่นพิเศษ ข้อมูลเบอร์มงคลมาใหม่ หรือสิทธิประโยชน์ที่ท่านอาจสนใจ (ภายใต้ความยินยอมของท่าน)</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-bold text-brown-800 mb-4 border-b pb-2">4. ระยะเวลาการเก็บรักษาข้อมูล</h2>
                <p>เราจะเก็บรักษาข้อมูลส่วนบุคคลของท่านไว้เพียงเท่าที่จำเป็นต่อการดำเนินตามวัตถุประสงค์ที่แจ้งไว้ หรือตามที่กฎหมายกำหนดเท่านั้น โดยส่วนใหญ่จะเก็บรักษาไว้ไม่เกิน 10 ปีหลังจากสิ้นสุดความสัมพันธ์ในฐานะลูกค้า</p>
            </section>

            <section class="mb-10">
                <h2 class="text-2xl font-bold text-brown-800 mb-4 border-b pb-2">5. สิทธิของเจ้าของข้อมูล</h2>
                <p>ท่านมีสิทธิตามกฎหมายที่ควรทราบ ดังนี้:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>สิทธิในการเข้าถึงและขอรับสำเนาข้อมูล</li>
                    <li>สิทธิในการขอแก้ไขข้อมูลให้ถูกต้อง</li>
                    <li>สิทธิในการขอลบหรือทำลายข้อมูล</li>
                    <li>สิทธิในการเพิกถอนความยินยอมได้ทุกเมื่อ</li>
                </ul>
            </section>

            <section class="mb-6">
                <h2 class="text-2xl font-bold text-brown-800 mb-4 border-b pb-2">6. ช่องทางการติดต่อ</h2>
                <p>หากท่านมีข้อสงสัยหรือต้องการใช้สิทธิเกี่ยวกับข้อมูลส่วนบุคคล สามารถติดต่อเจ้าหน้าที่คุ้มครองข้อมูลของเราได้ที่:</p>
                <p class="mt-2 font-bold text-gold-600">อีเมล: contact@supernumber.co.th<br>โทรศัพท์: 096-323-2656</p>
            </section>
        </article>
    </div>

    <div class="text-center mt-12">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-brown-800 text-white rounded-full font-bold hover:bg-brown-900 transition-all shadow-lg">
            <span>&larr;</span> กลับหน้าหลัก
        </a>
    </div>
</div>

<style>
    .privacy-page { font-family: 'Sarabun', sans-serif; color: #3b2f27; }
    .text-brown-900 { color: #2a2321; }
    .text-brown-800 { color: #3b2f27; }
    .text-brown-600 { color: #5b4d42; }
    .bg-brown-50 { background-color: #fbf9f6; }
    .bg-gold-500 { background: linear-gradient(135deg, #f3ca7a 0%, #d8a34a 100%); }
    .text-gold-600 { color: #d8a34a; }
    .border-brown-100 { border-color: #eee5d8; }
</style>
@endsection
