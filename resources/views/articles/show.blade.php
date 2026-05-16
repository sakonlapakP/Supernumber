@extends('layouts.app')

@section('title', $article->title . ' | Supernumber')
@section('meta_description', $article->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 150)))
@section('canonical', route('articles.show', $article->slug))
@section('og_title', $article->title . ' | Supernumber')
@section('og_description', $article->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 160)))
@section('og_url', route('articles.show', $article->slug))
@php
  $detailCoverPath = $article->cover_image_square_path ?: ($article->cover_image_path ?: $article->cover_image_landscape_path);
  $detailCoverCandidate = $article->cover_image_square_path ?: $article->cover_image_path;
  $ogImagePath = asset('images/home_banner.jpg');

  if ($detailCoverCandidate) {
      $ogImagePath = asset('storage/' . ltrim((string) $detailCoverCandidate, '/'));
  }
@endphp
@section('og_image', $ogImagePath)
@section('seo_schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": {{ json_encode($article->title) }},
  "description": {{ json_encode($article->meta_description ?: ($article->excerpt ?: '')) }},
  "url": "{{ route('articles.show', $article->slug) }}",
  "datePublished": "{{ optional($article->published_at)->toIso8601String() }}",
  "dateModified": "{{ $article->getEffectiveUpdatedAt()->toIso8601String() }}",
  "author": {
    "@type": "Organization",
    "name": "Supernumber"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Supernumber",
    "url": "{{ url('/') }}"
  }@if($detailCoverCandidate),
  "image": "{{ asset('storage/' . ltrim((string)$detailCoverCandidate, '/')) }}"@endif
}
</script>
@endsection

@push('styles')
@if($article->published_at)
<meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}" />
@endif
<meta property="article:modified_time" content="{{ $article->getEffectiveUpdatedAt()->toIso8601String() }}" />
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/article.css') }}?v={{ substr(md5_file(public_path('css/article.css')), 0, 8) }}" />
@endpush

@section('content')
  @php
    $publishedAt = optional(optional($article->published_at)->timezone('Asia/Bangkok'))->format('d/m/Y H:i') ?: optional(optional($article->created_at)->timezone('Asia/Bangkok'))->format('d/m/Y H:i');
    $effectiveUpdatedAt = $article->getEffectiveUpdatedAt()->timezone('Asia/Bangkok');
    $thaiMonthsArr = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $updatedAtDisplay = $effectiveUpdatedAt->day . ' ' . $thaiMonthsArr[$effectiveUpdatedAt->month] . ' ' . ($effectiveUpdatedAt->year + 543);
    $hasContentUpdate = $article->content_updated_at && $article->published_at &&
        abs($article->content_updated_at->timestamp - $article->published_at->timestamp) > 86400;
    $contentRaw = trim((string) $article->sanitizedContent());
    $hasHtml = \Illuminate\Support\Str::contains($contentRaw, ['<p', '<div', '<br', '<ul', '<ol', '<li', '<strong', '<em', '<a', '<h1', '<h2', '<h3']);
    $contentForPattern = $contentRaw;
    if ($hasHtml) {
        $contentWithBreaks = preg_replace('/<\/(p|div|h[1-6]|li|ul|ol)>/iu', "\n", $contentRaw);
        $contentWithBreaks = preg_replace('/<li\b[^>]*>/iu', "\n", (string) $contentWithBreaks);
        $contentWithBreaks = preg_replace_callback('/<img\b[^>]*\balt=(["\'])(.*?)\1[^>]*>/iu', static function ($matches): string {
            return ' ' . trim((string) ($matches[2] ?? '')) . ' ';
        }, (string) $contentWithBreaks);
        $contentWithBreaks = str_ireplace(['<br>', '<br/>', '<br />'], "\n", (string) $contentWithBreaks);
        $contentForPattern = html_entity_decode((string) $contentWithBreaks, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $contentForPattern = html_entity_decode($contentForPattern, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $contentForPattern = strip_tags($contentForPattern);
    }

    $contentForPattern = preg_replace("/\r\n|\r/u", "\n", $contentForPattern) ?? $contentForPattern;
    $contentForPattern = preg_replace('/\x{00A0}/u', ' ', $contentForPattern) ?? $contentForPattern;
    $contentForPattern = preg_replace('/[☑✔✓]+/u', '✅', $contentForPattern) ?? $contentForPattern;
    $contentForPattern = preg_replace('/\s*📲\s*/u', "\n\n📲 ", $contentForPattern) ?? $contentForPattern;
    $contentForPattern = preg_replace('/\s*✅\s*/u', "\n✅ ", $contentForPattern) ?? $contentForPattern;
    $contentForPattern = trim($contentForPattern);

    $hasCheckPattern = preg_match('/^(?:✅\s*)?\d{2}\s*\d{2}\s+.+$/mu', $contentForPattern) === 1;
    $hasNumberPattern = preg_match('/(?:📲|เบอร์ที่.*แนะนำ)/u', $contentForPattern) === 1;
    $usePatternBlocks = $hasCheckPattern || $hasNumberPattern;
    $paragraphs = preg_split("/\n{2,}/u", $contentForPattern) ?: [];

    preg_match_all('/\b0\d{9}\b/u', $contentForPattern, $allMatches);
    $candidateNumbers = collect($allMatches[0] ?? [])->unique()->values()->all();
    $activeNumbers = \App\Models\PhoneNumber::query()
        ->whereIn('phone_number', $candidateNumbers)
        ->where('status', \App\Models\PhoneNumber::STATUS_ACTIVE)
        ->pluck('phone_number')
        ->flip();
    $linkify = function (string $text): string {
        $escaped = e($text);

        return preg_replace_callback('/https?:\/\/[^\s<]+/u', function ($matches): string {
            $url = $matches[0];

            return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e($url) . '</a>';
        }, $escaped) ?? $escaped;
    };
  @endphp

  <article class="article-detail container">
    <div class="article-detail__shell">
      <header class="article-detail__header">
        <a href="/articles" class="article-detail__back">กลับไปหน้าบทความ</a>
        <div class="article-detail__meta-row">
          @if($hasContentUpdate)
          <span class="article-detail__meta-pill article-detail__meta-pill--updated">🔄 อัปเดตล่าสุด {{ $updatedAtDisplay }}</span>
          @endif
          <span class="article-detail__meta-pill article-detail__meta-pill--soft">👁️ ยอดเข้าชม {{ number_format($article->view_count) }} ครั้ง</span>
          <span class="article-detail__meta-pill article-detail__meta-pill--soft">Supernumber Insight</span>
        </div>
        <h1>{{ $article->title }}</h1>
        @if ($article->excerpt)
          <p class="article-detail__excerpt">{{ $article->excerpt }}</p>
        @endif
          <div class="article-detail__share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('articles.show', $article->slug)) }}"
               class="article-detail__share-btn article-detail__share-btn--facebook"
               target="_blank"
               rel="noopener noreferrer"
               title="แชร์ไป Facebook">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
              Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('articles.show', $article->slug)) }}&text={{ urlencode($article->title . ' - Supernumber') }}"
               class="article-detail__share-btn article-detail__share-btn--twitter"
               target="_blank"
               rel="noopener noreferrer"
               title="แชร์ไป X/Twitter">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
              X
            </a>
            <a href="https://line.me/R/msg/text/?{{ urlencode($article->title) }}%0A{{ urlencode(route('articles.show', $article->slug)) }}"
               class="article-detail__share-btn article-detail__share-btn--line"
               target="_blank"
               rel="noopener noreferrer"
               title="แชร์ไป LINE">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.365 9.863c.349-1.288.087-2.797-.958-3.935-1.045-1.137-2.41-1.537-3.758-1.537-1.349 0-2.714.4-3.759 1.537C9.796 7.066 9.534 8.575 9.883 9.863c-.524 1.687-.524 3.369 0 5.056-.349 1.288-.087 2.797.958 3.935 1.045 1.137 2.41 1.537 3.758 1.537 1.349 0 2.714-.4 3.759-1.537 1.045-1.138 1.307-2.647.958-3.935.524-1.687.524-3.369 0-5.056zm-6.52 7.373c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm3.758 0c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm3.758 0c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm1.577-4.256c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm-9.036 0c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585z"/>
              </svg>
              LINE
            </a>
            <a href="https://wa.me/?text={{ urlencode($article->title . ' ' . route('articles.show', $article->slug)) }}"
               class="article-detail__share-btn article-detail__share-btn--whatsapp"
               target="_blank"
               rel="noopener noreferrer"
               title="แชร์ไป WhatsApp">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a6.963 6.963 0 00-6.993 6.992c0 1.925.563 3.81 1.63 5.478L2.974 22l5.693-1.5a6.963 6.963 0 005.596 2.479h.005c3.851 0 6.993-3.14 6.993-6.993 0-1.866-.73-3.623-2.053-4.947a6.975 6.975 0 00-4.941-2.039M19.19 2.357a9.934 9.934 0 00-7.018-2.906c-5.465 0-9.91 4.446-9.91 9.911 0 1.743.357 3.435 1.06 5.034L2.5 21.866l5.773-1.52a9.926 9.926 0 004.766 1.215h.005c5.465 0 9.911-4.446 9.911-9.911 0-2.65-1.032-5.14-2.905-7.017"/>
              </svg>
              WhatsApp
            </a>
            <button class="article-detail__share-btn article-detail__share-btn--copy"
                    onclick="copyArticleLink()"
                    title="คัดลอกลิงก์บทความ">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m2.828-2.828a4.5 4.5 0 016.364 0m0 0a4.5 4.5 0 010 6.364L9.172 20.5a4.5 4.5 0 01-6.364-6.364l4.5-4.5a4.5 4.5 0 011.414-1.414m2.828-2.828L5.172 5.172a4.5 4.5 0 016.364 0" />
              </svg>
              คัดลอก
            </button>
          </div>
      </header>

      @if ($detailCoverPath && ! empty($lotteryResult))
        <div class="article-detail__lottery-cover-media" data-lottery-cover-media>
          <figure class="article-detail__cover-wrap article-detail__cover-wrap--media">
            <img
              src="{{ asset('storage/' . $detailCoverPath) }}"
              alt="{{ $article->title }}"
              class="article-detail__cover"
              onerror="const media=this.closest('[data-lottery-cover-media]'); if(media){ media.classList.add('is-fallback'); }"
            />
          </figure>
          <div class="article-detail__cover-wrap article-detail__cover-wrap--fallback" data-lottery-cover-fallback>
            @include('articles.partials.lottery-cover-fallback', ['lotteryResult' => $lotteryResult, 'wrapFigure' => false])
          </div>
        </div>
      @elseif ($detailCoverPath)
        <figure class="article-detail__cover-wrap">
          <img src="{{ asset('storage/' . $detailCoverPath) }}" alt="{{ $article->title }}" class="article-detail__cover" />
        </figure>
      @elseif (! empty($lotteryResult))
        @include('articles.partials.lottery-cover-fallback', ['lotteryResult' => $lotteryResult])
      @endif

      @if ($hasHtml && ! $usePatternBlocks)
        <div class="article-detail__content article-detail__content--html">{!! $contentRaw !!}</div>
      @else
        <div class="article-detail__content">
          @foreach ($paragraphs as $paragraph)
            @php
              $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/u', trim($paragraph)) ?: []), fn ($line) => $line !== ''));
              $checkItems = [];
              foreach ($lines as $line) {
                  if (! str_contains($line, '✅')) {
                      continue;
                  }

                  $parts = preg_split('/✅\s*/u', $line) ?: [];
                  foreach ($parts as $part) {
                      $part = trim($part);
                      if ($part !== '') {
                          $checkItems[] = $part;
                      }
                  }
              }
              $isCheckList = count($checkItems) > 0;
              if (! $isCheckList) {
                  foreach ($lines as $line) {
                      if (preg_match('/^\s*(?:[-•*]\s*)?(\d{2}\s*\d{2}\s+.+)$/u', $line, $lineMatch) === 1) {
                          $checkItems[] = trim($lineMatch[1]);
                      }
                  }
                  $isCheckList = count($checkItems) > 0;
              }

              $joinedLines = implode(' ', $lines);
              $isNumberSet = str_contains($joinedLines, '📲') || preg_match('/เบอร์ที่.*แนะนำ/u', $joinedLines) === 1;
              $numberHeading = 'เบอร์ที่แนะนำ';
              $numberTokens = [];
              if ($isNumberSet) {
                  if (str_contains($joinedLines, '📲')) {
                      $afterMarker = trim((string) preg_replace('/^.*?📲\s*/u', '', $joinedLines));
                  } else {
                      $afterMarker = $joinedLines;
                  }

                  if (preg_match('/^(เบอร์ที่[^:：]*แนะนำ)[:：]?\s*(.*)$/u', $afterMarker, $matches) === 1) {
                      $numberHeading = trim($matches[1]) !== '' ? trim($matches[1]) : $numberHeading;
                      $numbersArea = trim($matches[2] ?? '');
                  } elseif (preg_match('/^([^:：]+)[:：]?\s*(.*)$/u', $afterMarker, $matches) === 1) {
                      $numberHeading = trim($matches[1]) !== '' ? trim($matches[1]) : $numberHeading;
                      $numbersArea = trim($matches[2] ?? '');
                  } else {
                      $numbersArea = $afterMarker;
                  }

                  preg_match_all('/\b0\d{9}\b/u', $numbersArea, $numberMatches);
                  $numberTokens = $numberMatches[0] ?? [];
              }
            @endphp

            @if ($isCheckList)
              <ul class="article-detail__check-list">
                @foreach ($checkItems as $item)
                  <li>{{ $item }}</li>
                @endforeach
              </ul>
            @elseif ($isNumberSet)
              <section class="article-detail__number-block">
                <h2>{{ $numberHeading }}</h2>
                <div class="article-detail__numbers">
                  @foreach ($numberTokens as $number)
                    @if (isset($activeNumbers[$number]))
                      <a href="{{ route('evaluate', ['phone' => $number]) }}">{{ $number }}</a>
                    @else
                      <span class="is-disabled">{{ $number }}</span>
                    @endif
                  @endforeach
                </div>
              </section>
            @else
              <p>{!! nl2br($linkify(implode("\n", $lines))) !!}</p>
            @endif
          @endforeach
        </div>
      @endif

      <div class="article-detail__share article-detail__share--bottom">
        <span class="article-detail__share-label">แชร์บทความ:</span>
        <div class="article-detail__share-buttons">
          <a href="javascript:void(0)"
             onclick="trackAndShare('facebook', 'https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('articles.show', $article->slug)) }}')"
             class="article-detail__share-btn article-detail__share-btn--facebook"
             title="แชร์ไป Facebook">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook
          </a>
          <a href="javascript:void(0)"
             onclick="trackAndShare('twitter', 'https://twitter.com/intent/tweet?url={{ urlencode(route('articles.show', $article->slug)) }}&text={{ urlencode($article->title . ' - Supernumber') }}')"
             class="article-detail__share-btn article-detail__share-btn--twitter"
             title="แชร์ไป X/Twitter">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417a9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
            </svg>
            X
          </a>
          <a href="javascript:void(0)"
             onclick="trackAndShare('line', 'https://line.me/R/msg/text/?{{ urlencode($article->title) }}%0A{{ urlencode(route('articles.show', $article->slug)) }}')"
             class="article-detail__share-btn article-detail__share-btn--line"
             title="แชร์ไป LINE">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19.365 9.863c.349-1.288.087-2.797-.958-3.935-1.045-1.137-2.41-1.537-3.758-1.537-1.349 0-2.714.4-3.759 1.537C9.796 7.066 9.534 8.575 9.883 9.863c-.524 1.687-.524 3.369 0 5.056-.349 1.288-.087 2.797.958 3.935 1.045 1.137 2.41 1.537 3.758 1.537 1.349 0 2.714-.4 3.759-1.537 1.045-1.138 1.307-2.647.958-3.935.524-1.687.524-3.369 0-5.056zm-6.52 7.373c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm3.758 0c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm3.758 0c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm1.577-4.256c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585zm-9.036 0c-.868 0-1.577-.71-1.577-1.585 0-.875.709-1.585 1.577-1.585.868 0 1.577.71 1.577 1.585 0 .875-.709 1.585-1.577 1.585z"/>
            </svg>
            LINE
          </a>
          <a href="javascript:void(0)"
             onclick="trackAndShare('whatsapp', 'https://wa.me/?text={{ urlencode($article->title . ' ' . route('articles.show', $article->slug)) }}')"
             class="article-detail__share-btn article-detail__share-btn--whatsapp"
             title="แชร์ไป WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a6.963 6.963 0 00-6.993 6.992c0 1.925.563 3.81 1.63 5.478L2.974 22l5.693-1.5a6.963 6.963 0 005.596 2.479h.005c3.851 0 6.993-3.14 6.993-6.993 0-1.866-.73-3.623-2.053-4.947a6.975 6.975 0 00-4.941-2.039M19.19 2.357a9.934 9.934 0 00-7.018-2.906c-5.465 0-9.91 4.446-9.91 9.911 0 1.743.357 3.435 1.06 5.034L2.5 21.866l5.773-1.52a9.926 9.926 0 004.766 1.215h.005c5.465 0 9.911-4.446 9.911-9.911 0-2.65-1.032-5.14-2.905-7.017"/>
            </svg>
            WhatsApp
          </a>
          <button class="article-detail__share-btn article-detail__share-btn--copy"
                  onclick="copyArticleLink()"
                  title="คัดลอกลิงก์บทความ">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m2.828-2.828a4.5 4.5 0 016.364 0m0 0a4.5 4.5 0 010 6.364L9.172 20.5a4.5 4.5 0 01-6.364-6.364l4.5-4.5a4.5 4.5 0 011.414-1.414m2.828-2.828L5.172 5.172a4.5 4.5 0 016.364 0" />
            </svg>
            คัดลอก
          </button>
        </div>
      </div>

      @php
        // Strategic SEO: Fetch relevant numbers based on article keywords
        $searchQuery = null;
        $title = $article->title;
        $slug = $article->slug;
        $fullText = $title . ' ' . $slug;

        if (Str::contains($fullText, ['มังกร', '789'])) {
            $searchQuery = '789';
        } elseif (Str::contains($fullText, ['หงส์', '289'])) {
            $searchQuery = '289';
        } elseif (Str::contains($fullText, ['กวนอู', '639'])) {
            $searchQuery = '639';
        } elseif (Str::contains($fullText, ['เสน่ห์', 'เมตตา', '232', '323', '24', '42'])) {
            $searchQuery = '42'; // Sample logic for charm
        }

        $relevantNumbers = \App\Models\PhoneNumber::query()
            ->with('package')
            ->available()
            ->when($searchQuery, function($q) use ($searchQuery) {
                return $q->where('phone_number', 'like', '%' . $searchQuery . '%');
            })
            ->inRandomOrder()
            ->limit(4)
            ->get();
            
        // Fallback to random high quality if not enough relevant numbers
        if ($relevantNumbers->count() < 4) {
            $extra = \App\Models\PhoneNumber::query()
                ->with('package')
                ->available()
                ->whereNotIn('id', $relevantNumbers->pluck('id'))
                ->inRandomOrder()
                ->limit(4 - $relevantNumbers->count())
                ->get();
            $relevantNumbers = $relevantNumbers->concat($extra);
        }
      @endphp

      @if($relevantNumbers->count() > 0)
        <section class="article-related-numbers">
            <div class="article-related-numbers__header">
                <h2>เบอร์มงคลแนะนำสำหรับคุณ</h2>
                <p>เลือกเบอร์ที่ใช่เพื่อเสริมพลังตามคำทำนายในบทความนี้</p>
            </div>
            <div class="article-related-numbers__grid">
                @foreach($relevantNumbers as $num)
                    <div class="article-number-card">
                        <div class="article-number-card__phone">{{ $num->display_phone }}</div>
                        <div class="article-number-card__meta">
                            <span class="article-number-card__price">{{ $num->payment_label }}</span>
                            <span class="article-number-card__network">{{ $num->network_label }}</span>
                        </div>
                        <a href="{{ route('evaluate', ['phone' => $num->phone_number]) }}" class="article-number-card__btn">ดูรายละเอียด</a>
                    </div>
                @endforeach
            </div>
            <div class="article-related-numbers__footer">
                <a href="{{ route('numbers.index', $searchQuery ? ['q' => $searchQuery] : []) }}" class="btn-view-all">ดูเบอร์หมวดนี้ทั้งหมด</a>
            </div>
        </section>
      @endif

      <section class="article-comments article-comments--readers">
        <h2>คอมเมนต์จากผู้อ่าน</h2>

        @if (session('comment_status_message'))
          <div class="article-comments__alert">{{ session('comment_status_message') }}</div>
        @endif

        @if ($errors->any())
          <div class="article-comments__alert article-comments__alert--error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('articles.comments.store', $article->slug) }}" method="post" class="article-comments__form">
          @csrf
          <label>
            <span>ชื่อ *</span>
            <input type="text" name="commenter_name" value="{{ old('commenter_name') }}" required />
          </label>
          <label class="article-comments__textarea-label">
            <span>ความคิดเห็น *</span>
            <textarea name="content" rows="4" required>{{ old('content') }}</textarea>
          </label>
          <button type="submit">ส่งคอมเมนต์</button>
          <p class="article-comments__hint">คอมเมนต์จะถูกแสดงหลังจากแอดมินตรวจสอบและอนุมัติ</p>
        </form>

        <div class="article-comments__list">
          @forelse ($article->approvedComments as $comment)
            <article class="article-comment-card">
              <div class="article-comment-card__head">
                <strong>{{ $comment->commenter_name }}</strong>
                <span>{{ optional($comment->created_at)->format('d/m/Y H:i') }}</span>
              </div>
              <p>{!! nl2br(e($comment->content)) !!}</p>
            </article>
          @empty
            <p class="article-comments__empty">ยังไม่มีคอมเมนต์ในบทความนี้</p>
          @endforelse
        </div>
      </section>
    </div>
  </article>

  <script>
    function trackAndShare(platform, shareUrl) {
      const slug = '{{ $article->slug }}';
      fetch('{{ route("articles.track-share", ":slug") }}'.replace(':slug', slug), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ platform: platform })
      }).then(() => {
        window.open(shareUrl, '_blank', 'width=600,height=400');
      }).catch(err => {
        console.error('Track share error:', err);
        window.open(shareUrl, '_blank', 'width=600,height=400');
      });
    }

    function copyArticleLink() {
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>คัดลอกแล้ว';
        btn.style.background = '#22c55e';

        fetch('{{ route("articles.track-share", ":slug") }}'.replace(':slug', '{{ $article->slug }}'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ platform: 'copy' })
        });

        setTimeout(() => {
          btn.innerHTML = originalText;
          btn.style.background = '';
        }, 2000);
      });
    }
  </script>
@endsection
