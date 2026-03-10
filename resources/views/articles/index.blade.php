@extends('layouts.app')

@section('title', 'บทความ | Supernumber')
@section('meta_description', 'บทความและความรู้เรื่องเบอร์มงคล การเลือกเบอร์ให้เหมาะกับคุณ และเทคนิคเสริมพลังชีวิตจาก Supernumber')
@section('canonical', route('articles.index'))
@section('og_title', 'บทความ | Supernumber')
@section('og_description', 'รวมบทความความรู้เรื่องเบอร์มงคล และเทคนิคการเลือกเบอร์ให้ตอบโจทย์ชีวิต')
@section('og_url', route('articles.index'))

@section('content')
  <section class="article-hero">
    <div class="container">
      <p class="article-hero__kicker">Knowledge Hub</p>
      <h1 class="article-hero__title">บทความจาก Supernumber</h1>
      <p class="article-hero__subtitle">รวมบทความความรู้เรื่องเบอร์มงคล เทคนิคเลือกเบอร์ และเคล็ดลับเสริมพลังชีวิต</p>
    </div>
  </section>

  <section class="article-listing container">
    @if ($articles->count())
      <div class="article-grid">
        @foreach ($articles as $article)
          <article class="article-card">
            @if ($article->cover_image_path)
              <a href="{{ route('articles.show', $article->slug) }}" class="article-card__cover-link" aria-label="อ่านบทความ {{ $article->title }}">
                <img src="{{ asset('storage/' . $article->cover_image_path) }}" alt="{{ $article->title }}" class="article-card__cover" loading="lazy" />
              </a>
            @endif
            <div class="article-card__body">
              <p class="article-card__meta">{{ optional($article->published_at)->format('d/m/Y') ?: optional($article->created_at)->format('d/m/Y') }}</p>
              <h2 class="article-card__title">
                <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
              </h2>
              <p class="article-card__excerpt">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 170) }}</p>
              <a href="{{ route('articles.show', $article->slug) }}" class="article-card__link">อ่านต่อ</a>
            </div>
          </article>
        @endforeach
      </div>

      @if ($articles->hasPages())
        <nav class="article-pagination" aria-label="เปลี่ยนหน้าบทความ">
          @if ($articles->onFirstPage())
            <span>ก่อนหน้า</span>
          @else
            <a href="{{ $articles->previousPageUrl() }}">ก่อนหน้า</a>
          @endif

          @php
            $startPage = max(1, $articles->currentPage() - 2);
            $endPage = min($articles->lastPage(), $articles->currentPage() + 2);
          @endphp

          @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $articles->currentPage())
              <span class="is-active">{{ $page }}</span>
            @else
              <a href="{{ $articles->url($page) }}">{{ $page }}</a>
            @endif
          @endfor

          @if ($articles->hasMorePages())
            <a href="{{ $articles->nextPageUrl() }}">ถัดไป</a>
          @else
            <span>ถัดไป</span>
          @endif
        </nav>
      @endif
    @else
      <div class="article-empty">ยังไม่มีบทความเผยแพร่ในตอนนี้</div>
    @endif
  </section>
@endsection
