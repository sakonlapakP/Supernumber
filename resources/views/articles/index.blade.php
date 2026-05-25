@extends('layouts.app')

@section('title', 'บทความเบอร์มงคล ความรู้เรื่องตัวเลขและเสริมดวง | Supernumber')
@section('meta_description', 'บทความและความรู้เรื่องเบอร์มงคล การเลือกเบอร์ให้เหมาะกับคุณ และเทคนิคเสริมพลังชีวิตจาก Supernumber')
@section('canonical', url('/articles'))
@section('og_title', 'บทความเบอร์มงคล ความรู้เรื่องตัวเลขและเสริมดวง | Supernumber')
@section('og_description', 'รวมบทความความรู้เรื่องเบอร์มงคล และเทคนิคการเลือกเบอร์ให้ตอบโจทย์ชีวิต')
@section('og_url', url('/articles'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/article.css') }}?v={{ substr(md5_file(public_path('css/article.css')), 0, 8) }}" />
@endpush

@section('content')
  <section class="article-hero">
    <div class="container">
      <p class="article-hero__kicker">Knowledge Hub</p>
      <h1 class="article-hero__title">บทความจาก Supernumber</h1>
      <p class="article-hero__subtitle">รวมบทความความรู้เรื่องเบอร์มงคล เทคนิคเลือกเบอร์ และเคล็ดลับเสริมพลังชีวิต</p>
    </div>
  </section>

  <section class="article-listing container">
    @if ($pinnedArticles->count())
      <div class="article-pinned-section">
        <div class="article-pinned-label">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16 4a1 1 0 0 0-1.447-.894l-4 2A1 1 0 0 0 10 6v1.382l-3.447 6.894A1 1 0 0 0 7.447 16H11v4a1 1 0 0 0 2 0v-4h3.553a1 1 0 0 0 .894-1.447L14 8.382V7a1 1 0 0 0-.553-.894L10 4.618V6l2 .894V8a1 1 0 0 0 .105.447L14.882 14H9.118l2.777-5.553A1 1 0 0 0 12 8V6.618L14 5.724V4z"/></svg>
          ปักหมุด
        </div>
        <div class="article-grid article-grid--pinned">
          @foreach ($pinnedArticles as $article)
            @php
              $listingCoverPath = $article->cover_image_landscape_path ?: ($article->cover_image_path ?: $article->cover_image_square_path);
            @endphp
            <article class="article-card article-card--pinned">
              <span class="article-card__pin-badge" title="ปักหมุด">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/></svg>
                ปักหมุด
              </span>
              @if ($listingCoverPath)
                <a href="{{ route('articles.show', $article->slug) }}" class="article-card__cover-link" aria-label="อ่านบทความ {{ $article->title }}">
                  <img src="{{ asset('storage/' . $listingCoverPath) }}" alt="{{ $article->title }}" class="article-card__cover" loading="lazy" />
                </a>
              @endif
              <div class="article-card__body">
                <p class="article-card__meta">{{ optional(optional($article->published_at)->timezone('Asia/Bangkok'))->format('d/m/Y') ?: optional(optional($article->created_at)->timezone('Asia/Bangkok'))->format('d/m/Y') }}</p>
                <h2 class="article-card__title">
                  <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
                </h2>
                <p class="article-card__excerpt">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 170) }}</p>
                <a href="{{ route('articles.show', $article->slug) }}" class="article-card__link">อ่านต่อ</a>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    @endif

    @if ($articles->count())
      <div class="article-grid">
        @foreach ($articles as $article)
          @php
            $listingCoverPath = $article->cover_image_landscape_path ?: ($article->cover_image_path ?: $article->cover_image_square_path);
          @endphp
          <article class="article-card">
            @if ($listingCoverPath)
              <a href="{{ route('articles.show', $article->slug) }}" class="article-card__cover-link" aria-label="อ่านบทความ {{ $article->title }}">
                <img src="{{ asset('storage/' . $listingCoverPath) }}" alt="{{ $article->title }}" class="article-card__cover" loading="lazy" />
              </a>
            @endif
            <div class="article-card__body">
              <p class="article-card__meta">{{ optional(optional($article->published_at)->timezone('Asia/Bangkok'))->format('d/m/Y') ?: optional(optional($article->created_at)->timezone('Asia/Bangkok'))->format('d/m/Y') }}</p>
              <h2 class="article-card__title">
                <a href="{{ route('articles.show', $article->slug) }}">{{ $article->title }}</a>
              </h2>
              <p class="article-card__excerpt">{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 170) }}</p>
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
