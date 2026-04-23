@extends('layouts.app')

@section('title', $article->title . ' | Supernumber')
@section('meta_description', $article->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 150)))
@section('canonical', route('articles.show', $article->slug))
@section('og_title', $article->title . ' | Supernumber')
@section('og_description', $article->meta_description ?: ($article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->sanitizedContent()), 160)))
@section('og_url', route('articles.show', $article->slug))
@php
  $detailCoverCandidate = $article->cover_image_square_path ?: $article->cover_image_path;
  $detailCoverPath = null;

  if ($detailCoverCandidate) {
      $detailCoverPath = ltrim((string) $detailCoverCandidate, '/');
  }
@endphp
@if ($detailCoverPath)
  @section('og_image', asset('storage/' . $detailCoverPath))
@endif

@section('content')
  @php
    $publishedAt = optional($article->published_at)->format('d/m/Y H:i') ?: optional($article->created_at)->format('d/m/Y H:i');
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
        <a href="{{ route('articles.index') }}" class="article-detail__back">กลับไปหน้าบทความ</a>
        <div class="article-detail__meta-row">
          <span class="article-detail__meta-pill">เผยแพร่ {{ $publishedAt }}</span>
          <span class="article-detail__meta-pill article-detail__meta-pill--soft">Supernumber Insight</span>
        </div>
        <h1>{{ $article->title }}</h1>
        @if ($article->excerpt)
          <p class="article-detail__excerpt">{{ $article->excerpt }}</p>
        @endif
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
@endsection
