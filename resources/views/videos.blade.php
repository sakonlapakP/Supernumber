@extends('layouts.app')

@section('title', 'Supernumber | วิดีโอสุดยอดพลังตัวเลข')
@section('meta_description', 'รวมวิดีโอซีรีส์ "สุดยอดพลังตัวเลข" โดย Supernumber เรื่องจริงจากคนที่เปลี่ยนเบอร์แล้วชีวิตเปลี่ยน')
@section('og_title', 'Supernumber | วิดีโอสุดยอดพลังตัวเลข')
@section('og_description', 'รวมวิดีโอซีรีส์ "สุดยอดพลังตัวเลข" โดย Supernumber เรื่องจริงจากคนที่เปลี่ยนเบอร์แล้วชีวิตเปลี่ยน')
@section('canonical', url('/videos'))
@section('og_url', url('/videos'))
@section('og_image', asset('images/home_banner.jpg'))

@push('styles')
<style>
  .videos-hero {
    padding: 56px 0 38px;
    background:
      radial-gradient(circle at right top, rgba(216,163,74,.22), rgba(216,163,74,0) 45%),
      linear-gradient(120deg, rgba(31,25,20,.9), rgba(52,38,28,.9));
    color: #fff;
  }
  .videos-hero__kicker {
    color: #ffd68f;
    letter-spacing: .18em;
    text-transform: uppercase;
    font-size: 13px;
  }
  .videos-hero__title {
    margin-top: 10px;
    font-size: clamp(30px, 4vw, 42px);
    line-height: 1.2;
    font-weight: 700;
  }
  .videos-hero__sub {
    margin-top: 12px;
    max-width: 760px;
    color: rgba(255,255,255,.86);
    line-height: 1.7;
    font-size: .95rem;
  }
  .videos-hero__badge {
    display: inline-block;
    margin-top: 14px;
    background: rgba(255,214,143,.15);
    border: 1px solid rgba(255,214,143,.35);
    color: #ffd68f;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .1em;
    padding: .28rem .85rem;
    border-radius: 999px;
  }

  .videos-section {
    padding: 2.5rem 0 4rem;
  }
  .videos-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
  }
  @media (max-width: 900px) {
    .videos-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 560px) {
    .videos-grid { grid-template-columns: 1fr; }
  }

  .video-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease;
  }
  .video-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,.14);
  }
  .video-card__thumb {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
    background: #1a1210;
  }
  .video-card__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .22s ease;
  }
  .video-card:hover .video-card__thumb img {
    transform: scale(1.04);
  }
  .video-card__play {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,.28);
    transition: background .18s ease;
  }
  .video-card:hover .video-card__play {
    background: rgba(0,0,0,.42);
  }
  .video-card__play svg {
    width: 52px;
    height: 52px;
    filter: drop-shadow(0 2px 6px rgba(0,0,0,.5));
  }
  .video-card__body {
    padding: .85rem 1rem;
  }
  .video-card__ep {
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .08em;
    color: #c9a96e;
    text-transform: uppercase;
    margin-bottom: .25rem;
  }
  .video-card__title {
    font-size: .9rem;
    font-weight: 600;
    color: #2a2321;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* CTA */
  .videos-cta {
    background: linear-gradient(135deg, #2a2321 0%, #3d2e26 100%);
    padding: 2.5rem 0;
  }
  .videos-cta__inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    text-align: center;
  }
  .videos-cta__text {
    color: rgba(255,255,255,.85);
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
  }
  .videos-cta__btn {
    display: inline-block;
    background: #c9a96e;
    color: #2a2321;
    font-weight: 700;
    font-size: .95rem;
    padding: .75rem 1.75rem;
    border-radius: 999px;
    text-decoration: none;
    transition: background .18s ease, transform .18s ease;
    white-space: nowrap;
  }
  .videos-cta__btn:hover {
    background: #d9bc85;
    transform: translateY(-2px);
  }

  /* Modal */
  .video-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.82);
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .video-modal-overlay.is-open {
    display: flex;
  }
  .video-modal {
    position: relative;
    width: 100%;
    max-width: 860px;
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.7);
  }
  .video-modal__ratio {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
  }
  .video-modal__ratio iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: none;
  }
  .video-modal__close {
    position: absolute;
    top: -2.75rem;
    right: 0;
    background: none;
    border: none;
    color: #fff;
    font-size: 2rem;
    line-height: 1;
    cursor: pointer;
    padding: 0 .25rem;
    opacity: .85;
  }
  .video-modal__close:hover { opacity: 1; }
</style>
@endpush

@section('content')

@php
$videos = [
  ['id' => 'mnE67CCaj9o', 'ep' => 'EP.03', 'title' => 'จิ๊ก Bouclés salon เปลี่ยนเบอร์กับพี่จิ้มมี่ เดือนเดียวงานล้น !'],
  ['id' => 'xH-TPuISsO8', 'ep' => 'EP.04', 'title' => 'โทรุ-เฟิร์ส ชวนกันมา…ตามหาเลขซุปตาร์'],
  ['id' => 'zlW1CgDg5BE', 'ep' => 'EP.05', 'title' => 'พาไหว้ท่านท้าวหิรัญฯ ให้เห็นผลแบบ 300%'],
  ['id' => 'VyNzm5RhFZU', 'ep' => 'EP.06', 'title' => 'หมอเนื่อง-ศรัณย์ เปลี่ยนเบอร์จนได้เป็นพระเอกดัง'],
  ['id' => 'rsh37oh2VYE', 'ep' => 'EP.07', 'title' => 'ชมพู่ เปลี่ยนเบอร์ยังไงให้พ้นคานทอง และเลขขึ้นคานที่ต้องระวัง'],
  ['id' => '2TW9GL8okwM', 'ep' => 'EP.08', 'title' => 'นนทน์ นักธุรกิจหนุ่ม จากไม่เชื่อเรื่องเบอร์จนตอนนี้เชื่อ 300%'],
  ['id' => 'izI03nGUYDY', 'ep' => 'EP.09', 'title' => 'ปลื้ม ปุริม จากคนไม่เชื่อเรื่องเบอร์จน ตอนนี้เชื่อสนิทใจ'],
  ['id' => 'Q7EzOnKPX-E', 'ep' => 'EP.10', 'title' => 'คุณตุ้ย จากเบอร์สวยเบอร์ต้อง ยังปังสู้เบอร์มงคลไม่ได้'],
  ['id' => 'yWiB42AjYDk', 'ep' => 'EP.11', 'title' => 'หญิงแอร์ พาชมวังนั่งคุย หลังพี่จิ้มมี่เปลี่ยนเบอร์ให้ทั้งบ้าน'],
  ['id' => 'peoxO5-M-i4', 'ep' => 'EP.12', 'title' => 'ท็อปแท็ป จิรกิตติ์ งานวงการก็ปัง ธุรกิจส่วนตัวก็ปัง'],
  ['id' => 'YPh8qEOwSLA', 'ep' => 'EP.13', 'title' => 'กำลังตจิ เทยเที่ยวไทย บุกออฟฟิศมาเล่าถึงความเชื่อในเรื่องตัวเลข'],
  ['id' => 'cnyuzYF35LM', 'ep' => 'EP.14', 'title' => 'ครูจำ๊ะ งานศิลปะขนมไทย เปลี่ยนเบอร์แล้วออเดอร์เข้ารัว'],
];
@endphp

<section class="videos-hero">
  <div class="container">
    <p class="videos-hero__kicker">Supernumber Original Series</p>
    <h1 class="videos-hero__title">วิดีโอสุดยอดพลังตัวเลข</h1>
    <p class="videos-hero__sub">เรื่องจริงจากคนที่เปลี่ยนเบอร์แล้วชีวิตเปลี่ยน</p>
    <span class="videos-hero__badge">{{ count($videos) }} EP</span>
  </div>
</section>

<section class="videos-section">
  <div class="container">
    <div class="videos-grid">
      @foreach($videos as $video)
      <div class="video-card" role="button" tabindex="0"
           aria-label="ดูวิดีโอ {{ $video['ep'] }}: {{ $video['title'] }}"
           data-video-id="{{ $video['id'] }}"
           onclick="openVideoModal('{{ $video['id'] }}')">
        <div class="video-card__thumb">
          <img
            src="https://img.youtube.com/vi/{{ $video['id'] }}/hqdefault.jpg"
            alt="{{ $video['ep'] }}: {{ $video['title'] }}"
            loading="{{ $loop->index < 3 ? 'eager' : 'lazy' }}"
          />
          <div class="video-card__play" aria-hidden="true">
            <svg viewBox="0 0 68 48" xmlns="http://www.w3.org/2000/svg">
              <path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55c-2.93.78-4.63 3.26-5.42 6.19C0 13.05 0 24 0 24s0 10.95 1.48 16.26c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55c2.93-.78 4.64-3.26 5.42-6.19C68 34.95 68 24 68 24s0-10.95-1.48-16.26z" fill="red"/>
              <path d="M45 24 27 14v20" fill="white"/>
            </svg>
          </div>
        </div>
        <div class="video-card__body">
          <p class="video-card__ep">{{ $video['ep'] }}</p>
          <p class="video-card__title">{{ $video['title'] }}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>

{{-- CTA --}}
<section class="videos-cta">
  <div class="container videos-cta__inner">
    <p class="videos-cta__text">อยากเปลี่ยนเบอร์ให้ชีวิตเปลี่ยน?</p>
    <a href="{{ route('numbers.index') }}" class="videos-cta__btn">เลือกเบอร์มงคลได้เลย →</a>
  </div>
</section>

{{-- Modal --}}
<div class="video-modal-overlay" id="videoModalOverlay" role="dialog" aria-modal="true" aria-label="เล่นวิดีโอ" onclick="closeVideoModal(event)">
  <div class="video-modal">
    <button class="video-modal__close" onclick="closeVideoModal()" aria-label="ปิด">&times;</button>
    <div class="video-modal__ratio">
      <iframe id="videoModalIframe" src="" allowfullscreen allow="autoplay; encrypted-media"></iframe>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function openVideoModal(id) {
    document.getElementById('videoModalIframe').src =
      'https://www.youtube.com/embed/' + id + '?autoplay=1&rel=0';
    document.getElementById('videoModalOverlay').classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }
  function closeVideoModal(e) {
    if (e && e.target !== document.getElementById('videoModalOverlay') && !e.target.classList.contains('video-modal__close')) return;
    document.getElementById('videoModalIframe').src = '';
    document.getElementById('videoModalOverlay').classList.remove('is-open');
    document.body.style.overflow = '';
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeVideoModal();
  });
  document.querySelectorAll('.video-card').forEach(function(card) {
    card.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openVideoModal(card.dataset.videoId);
      }
    });
  });
</script>
@endpush

@endsection
