@extends('layouts.app')

@section('title', 'กำลังประมวลผลเบอร์ที่เหมาะกับคุณ | Supernumber')
@section('meta_description', 'ระบบกำลังวิเคราะห์และคัดเบอร์ที่เหมาะกับคุณ')
@section('robots', 'noindex, nofollow')
@section('body_class', 'numbers-scale-soft estimate-processing-body')

@section('content')
  <section class="estimate-processing" aria-labelledby="estimate-processing-title">
    <div class="estimate-processing__panel">
      <div class="estimate-processing__mark" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <p class="estimate-processing__kicker">กำลังประมวณผล...</p>
      <h1 id="estimate-processing-title">กำลังวิเคราะห์และคัดเบอร์ที่เหมาะกับคุณ...</h1>
      <div class="estimate-processing__bar" aria-hidden="true"><span></span></div>
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    window.setTimeout(() => {
      window.location.href = @json($resultsUrl);
    }, 5000);
  </script>
@endpush
