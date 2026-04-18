@extends('layouts.app')

@section('title', 'Supernumber | ผลการวิเคราะห์เบอร์')
@section('meta_description', 'สรุปความหมายเบอร์มงคลแบบเข้าใจง่าย พร้อมจุดเด่นและคำแนะนำการใช้งาน')
@section('og_title', 'Supernumber | ผลการวิเคราะห์เบอร์')
@section('og_description', 'สรุปความหมายเบอร์มงคลแบบเข้าใจง่าย พร้อมจุดเด่นและคำแนะนำการใช้งาน')
@section('canonical', url('/evaluate'))
@section('og_url', url('/evaluate'))
@section('og_image', asset('images/evaluate_banner.jpg'))
@section('preload_image', asset('images/evaluate_banner.jpg'))

@section('content')
  @php
    $rawPhone = $phone ?? request('phone');
    $number = preg_replace('/[^0-9]/', '', $rawPhone ?? '');
    if ($number === '') {
        $number = '0645391429';
    }
    if (strlen($number) < 7) {
        $number = '0645391429';
    }
    $phone = $number;
    $currentNumberModel = \App\Models\PhoneNumber::query()
        ->where('phone_number', $number)
        ->first();
    $currentServiceType = $currentNumberModel?->service_type ?? \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID;
    $currentPackage = $currentNumberModel?->is_prepaid
        ? ((int) ($currentNumberModel->sale_price ?? 0))
        : ($currentNumberModel?->normalized_package_price ?? 699);
    if ($currentPackage < 1) {
        $currentPackage = 699;
    }

    $recommendedNumbers = \App\Models\PhoneNumber::query()
        ->available()
        ->where('network_code', 'true_dtac')
        ->when($currentNumberModel !== null, function ($query) use ($currentServiceType) {
            $query->where('service_type', $currentServiceType);
        })
        ->where('phone_number', '!=', $number)
        ->inRandomOrder()
        ->limit(12)
        ->get();

    if ($recommendedNumbers->count() < 12) {
        $excludedNumbers = $recommendedNumbers
            ->pluck('phone_number')
            ->push($number)
            ->all();
        $extraNumbers = \App\Models\PhoneNumber::query()
            ->available()
            ->where('network_code', 'true_dtac')
            ->when($currentNumberModel !== null, function ($query) use ($currentServiceType) {
                $query->where('service_type', $currentServiceType);
            })
            ->whereNotIn('phone_number', $excludedNumbers)
            ->inRandomOrder()
            ->limit(12 - $recommendedNumbers->count())
            ->get();
        $recommendedNumbers = $recommendedNumbers->concat($extraNumbers);
    }

    $lastSeven = substr($number, -7);
    $pairs = [];
    for ($i = 0; $i < 6; $i++) {
        $pairs[] = substr($lastSeven, $i, 2);
    }
    $groupedPairs = [];
    foreach ($pairs as $index => $pair) {
        $chars = str_split($pair);
        sort($chars);
        $key = implode('', $chars);
        if (!isset($groupedPairs[$key])) {
            $groupedPairs[$key] = [
                'key' => $key,
                'primary_pair' => $pair,
                'first_index' => $index,
            ];
        }
    }
    usort($groupedPairs, fn ($a, $b) => $a['first_index'] <=> $b['first_index']);

    $pairsWithScore = [];
    for ($i = 0; $i < 6; $i++) {
        $pair = $pairs[$i];
        $pairsWithScore[] = [
            'pair' => $pair,
            'score' => $i < 5 ? 10 : 30,
            'position' => $i + 1,
        ];
    }
    $groupScores = [];
    foreach ($pairsWithScore as $index => $data) {
        $chars = str_split($data['pair']);
        sort($chars);
        $key = implode('', $chars);
        $mapKey = 'k' . $key;
        if (!isset($groupScores[$mapKey])) {
            $groupScores[$mapKey] = [
                'key' => $key,
                'score' => 0,
                'first_index' => $index,
            ];
        }
        $groupScores[$mapKey]['score'] += $data['score'];
    }
    $topGroup = null;
    foreach ($groupScores as $meta) {
        if ($topGroup === null) {
            $topGroup = $meta;
            continue;
        }
        if ($meta['score'] > $topGroup['score']) {
            $topGroup = $meta;
        } elseif ($meta['score'] === $topGroup['score'] && $meta['first_index'] < $topGroup['first_index']) {
            $topGroup = $meta;
        }
    }
    $topGroupKey = $topGroup['key'];

    $statusLabelMap = [
        'good' => 'ตรง',
        'bad' => 'เลขควรระวัง',
        'conditional' => 'ใช้ได้บางกรณี',
    ];
    $statusClassMap = [
        'good' => 'is-good',
        'bad' => 'is-danger',
        'conditional' => 'is-neutral',
        'neutral' => 'is-neutral',
    ];
    $pairHeadingMap = [
        'good' => 'คู่เลขดี',
        'bad' => 'คู่เลขเสีย',
        'conditional' => 'คู่เลขดีกับคู่เลขเสีย',
        'neutral' => 'คู่เลขดีกับคู่เลขเสีย',
    ];
    $pairMeaningMap = \App\Models\PairMeaning::whereIn('pair', $pairs)
        ->get()
        ->keyBy('pair');

    $topMeaningRecord = \App\Models\PairMeaning::where('pair', $topGroupKey)->first();
    $topMeaning = [
        'title' => $topMeaningRecord ? ($statusLabelMap[$topMeaningRecord->status] ?? 'เลขทั่วไป') : 'เลขทั่วไป',
        'desc' => $topMeaningRecord
            ? ($topMeaningRecord->long_meaning ?: $topMeaningRecord->short_meaning)
            : 'คู่นี้มีคะแนนสูงสุดตามตำแหน่ง แนะนำประเมินร่วมกับคู่เลขอื่นเพื่อความแม่นยำ',
    ];
    $topMeaningSummary = trim($topMeaning['desc']);
    if (($topMeaningRecord?->status ?? null) === 'good') {
        $topMeaningSummary = preg_replace('/^เลขชุดนี้/u', 'ผู้ที่ใช้เบอร์นี้', $topMeaningSummary) ?? $topMeaningSummary;
    } else {
        $topMeaningSummary = $topMeaning['title'] . ' — ' . $topMeaningSummary;
    }

    $topicPairMap = [
        'การสื่อสาร' => [
            'good' => ['14', '22', '23', '24', '32', '41', '42', '44', '45', '46', '49', '54', '64'],
            'bad' => ['03', '04', '05', '06', '12', '13', '18', '21', '27', '30', '31', '34', '40', '43', '48', '50', '57', '60', '72', '75', '81', '84'],
            'conditional' => ['33', '47', '74'],
        ],
        'ความรัก/เสน่ห์' => [
            'good' => ['22', '23', '24', '26', '28', '29', '32', '35', '36', '41', '42', '44', '46', '62', '63', '64', '66', '69'],
            'bad' => ['00', '02', '06', '08', '12', '20', '21', '25', '27', '34', '37', '38', '43', '52', '57', '58', '60', '67', '68', '72', '73', '75', '76', '80', '83', '85', '86', '88'],
            'conditional' => ['33'],
        ],
        'ผู้ใหญ่เมตตา/อุปถัมภ์' => [
            'good' => ['14', '15', '22', '23', '24', '32', '35', '36', '41', '42', '45', '51', '53', '54', '55', '56', '59', '63', '65'],
            'bad' => ['05', '13', '25', '31', '50', '52', '57', '75'],
            'conditional' => [],
        ],
        'การงาน/ความก้าวหน้า' => [
            'good' => ['14', '15', '16', '19', '23', '24', '26', '28', '29', '32', '35', '36', '39', '41', '42', '44', '45', '46', '49', '51', '53', '54', '55', '56', '59', '61', '62', '63', '64', '65'],
            'bad' => ['00', '01', '02', '03', '04', '07', '08', '09', '10', '11', '12', '13', '17', '18', '20', '21', '25', '27', '30', '31', '34', '37', '38', '40', '43', '48', '52', '57', '58', '67', '68', '70', '71', '72', '73', '75', '76', '77', '80', '81', '83', '84', '85', '86', '88', '90'],
            'conditional' => ['33', '47', '74'],
        ],
        'การเงิน/โชคลาภ' => [
            'good' => ['28', '78', '82', '87'],
            'bad' => ['01', '02', '04', '06', '10', '12', '18', '20', '21', '25', '27', '34', '37', '40', '43', '52', '58', '60', '67', '68', '72', '73', '76', '81', '85', '86', '88'],
            'conditional' => ['47', '74'],
        ],
        'ภาวะผู้นำ/อำนาจ' => [
            'good' => ['35', '53', '39', '93', '28', '82', '78', '87', '89', '98'],
            'bad' => ['08', '80', '88'],
            'conditional' => ['47', '74'],
        ],
        'ความคิดสร้างสรรค์/ไอเดีย' => [
            'good' => ['19', '29', '69', '91', '92', '96'],
            'bad' => [],
            'conditional' => [],
        ],
        'สติปัญญา/การเรียนรู้' => [
            'good' => ['14', '15', '41', '44', '45', '49', '51', '54', '55'],
            'bad' => [],
            'conditional' => [],
        ],
        'สุขภาพ/ความเครียด' => [
            'good' => ['15', '24', '29', '42', '45', '51', '54', '59', '69', '92', '95', '99'],
            'bad' => ['00', '01', '02', '03', '04', '05', '06', '07', '09', '10', '11', '12', '13', '17', '20', '21', '25', '27', '30', '31', '34', '37', '40', '43', '48', '50', '52', '57', '58', '60', '70', '71', '72', '73', '75', '77', '84', '85', '90'],
            'conditional' => ['47', '74'],
        ],
        'ระวังอุบัติเหตุ/เหตุฉุกเฉิน' => [
            'good' => [],
            'bad' => ['13', '31', '37', '48', '73', '84'],
            'conditional' => [],
        ],
        'ระวังอารมณ์/ความใจร้อน' => [
            'good' => [],
            'bad' => ['00', '01', '03', '09', '10', '11', '12', '13', '17', '18', '21', '30', '31', '38', '71', '81', '83', '88', '90'],
            'conditional' => ['33'],
        ],
        'ศาสตร์เร้นลับ/ลางสังหรณ์' => [
            'good' => ['49', '59', '79', '89', '94', '95', '97', '98', '99'],
            'bad' => ['00', '09', '90'],
            'conditional' => [],
        ],
    ];
    $topicOverviewOrder = [
        'การสื่อสาร',
        'ความรัก/เสน่ห์',
        'การงาน/ความก้าวหน้า',
        'การเงิน/โชคลาภ',
        'ภาวะผู้นำ/อำนาจ',
        'ความคิดสร้างสรรค์/ไอเดีย',
        'สติปัญญา/การเรียนรู้',
        'สุขภาพ/ความเครียด',
        'ศาสตร์เร้นลับ/ลางสังหรณ์',
    ];
    $topicIconMap = [
        'การสื่อสาร' => '💬',
        'ความรัก/เสน่ห์' => '💖',
        'การงาน/ความก้าวหน้า' => '💼',
        'การเงิน/โชคลาภ' => '💰',
        'ภาวะผู้นำ/อำนาจ' => '👑',
        'ความคิดสร้างสรรค์/ไอเดีย' => '💡',
        'สติปัญญา/การเรียนรู้' => '🧠',
        'สุขภาพ/ความเครียด' => '🌿',
        'ศาสตร์เร้นลับ/ลางสังหรณ์' => '✨',
    ];
    $buildPairVariants = static function (string $pair): array {
        $chars = str_split($pair);
        sort($chars);
        $normalized = implode('', $chars);

        return array_values(array_unique([$pair, strrev($pair), $normalized]));
    };
    $weightedPairs = [];
    $lastPairIndex = count($pairs) - 1;
    foreach ($pairs as $index => $pair) {
        $weightedPairs[] = [
            'variants' => $buildPairVariants($pair),
            'weight' => $index === $lastPairIndex ? 2 : 1,
        ];
    }
    $topicOverviewCards = [];
    foreach ($topicOverviewOrder as $topic) {
        $topicPairs = $topicPairMap[$topic] ?? null;
        if (!is_array($topicPairs)) {
            continue;
        }

        $goodWeight = 0.0;
        $conditionalWeight = 0.0;
        $badWeight = 0.0;
        foreach ($weightedPairs as $weightedPair) {
            $variants = $weightedPair['variants'];
            $weight = $weightedPair['weight'];

            if (count(array_intersect($variants, $topicPairs['good'])) > 0) {
                $goodWeight += $weight;
                continue;
            }

            if (count(array_intersect($variants, $topicPairs['conditional'])) > 0) {
                $conditionalWeight += $weight;
                continue;
            }

            if (count(array_intersect($variants, $topicPairs['bad'])) > 0) {
                $badWeight += $weight;
            }
        }

        $supportsTopic = ($goodWeight + ($conditionalWeight * 0.5)) > $badWeight;
        $tone = $supportsTopic ? 'supported' : 'muted';
        $ariaLabel = $supportsTopic ? ($topic . ' ช่วย') : ($topic . ' ไม่ช่วย');

        $topicOverviewCards[] = [
            'label' => $topic,
            'icon' => $topicIconMap[$topic] ?? '•',
            'supports' => $supportsTopic,
            'tone' => $tone,
            'aria_label' => $ariaLabel,
        ];
    }

    $pairCards = [];
    $hasBadPair = false;
    $warningThemePairs = ['33', '47', '74'];
    $hasWarningPair = count(array_intersect($pairs, $warningThemePairs)) > 0;
    foreach ($groupedPairs as $group) {
        $label = $group['key'];
        if (strlen($label) === 2 && $label[0] !== $label[1]) {
            $label = $label . ' ' . strrev($label);
        }
        $meaning = $pairMeaningMap->get($group['primary_pair'])
            ?? $pairMeaningMap->get($group['key'])
            ?? $pairMeaningMap->get(strrev($group['key']));
        $status = $meaning?->status ?? 'neutral';
        if ($status === 'bad') {
            $hasBadPair = true;
        }
        $pairCards[] = [
            'pair' => $label,
            'status' => $status,
            'label' => $statusLabelMap[$status] ?? 'เลขทั่วไป',
            'heading' => $pairHeadingMap[$status] ?? 'คู่เลขดีกับคู่เลขเสีย',
            'desc' => $meaning?->short_meaning ?? 'ยังไม่มีข้อมูลความหมายแบบสั้นสำหรับคู่นี้',
            'class' => $statusClassMap[$status] ?? 'is-neutral',
        ];
    }

    $themeState = $hasBadPair ? 'danger' : ($hasWarningPair ? 'warning' : 'good');
    $heroClass = match ($themeState) {
        'danger' => 'evaluate-hero evaluate-hero--danger',
        'warning' => 'evaluate-hero evaluate-hero--warning',
        default => 'evaluate-hero',
    };
    $resultsClass = match ($themeState) {
        'danger' => 'evaluate-results evaluate-results--danger',
        'warning' => 'evaluate-results evaluate-results--warning',
        default => 'evaluate-results',
    };
    $summaryNumberClass = match ($themeState) {
        'danger' => 'summary-number summary-number--danger',
        'warning' => 'summary-number summary-number--warning',
        default => 'summary-number',
    };
    $ctaClass = match ($themeState) {
        'danger' => 'evaluate-cta evaluate-cta--danger',
        'warning' => 'evaluate-cta evaluate-cta--warning',
        default => 'evaluate-cta',
    };
    $heroKicker = match ($themeState) {
        'danger' => 'รายงานความเสี่ยง',
        'warning' => 'รายงานเฝ้าระวัง',
        default => 'ผลการวิเคราะห์',
    };
    $summaryHeading = match ($themeState) {
        'danger' => 'เบอร์นี้มีคู่เลขที่ควรระวัง',
        'warning' => 'เบอร์นี้มีคู่เลขใช้ได้บางกรณี',
        default => 'เบอร์นี้ดี',
    };
    $recommendHeading = match ($themeState) {
        'danger' => 'เบอร์แนะนำเพื่อปรับสมดุล',
        'warning' => 'เบอร์แนะนำเพื่อเสริมสมดุล',
        default => 'เลือกเบอร์อื่นเพิ่มเติม',
    };
    $ctaHeading = match ($themeState) {
        'danger' => 'ต้องการให้ผู้เชี่ยวชาญช่วยวิเคราะห์แบบละเอียด?',
        'warning' => 'อยากได้เบอร์ที่สมดุลและนิ่งขึ้น?',
        default => 'อยากได้เบอร์ที่ตรงกับเป้าหมายมากขึ้น?',
    };
    $ctaDescription = match ($themeState) {
        'danger' => 'ให้ทีม Supernumber คัดเบอร์ใหม่ที่เหมาะกับคุณ ลดความเสี่ยงและเพิ่มโอกาสในชีวิต',
        'warning' => 'ให้ทีม Supernumber ช่วยคัดเบอร์ที่บาลานซ์กว่าเดิม ลดจุดผันผวนและเพิ่มความนิ่งในการใช้งาน',
        default => 'ให้ทีม Supernumber ช่วยคัดเบอร์มงคลที่ตอบโจทย์งาน เงิน และความรักของคุณ',
    };
  @endphp

  <section class="{{ $heroClass }}" aria-labelledby="evaluate-title">
    <div class="evaluate-overlay"></div>
    <div class="container evaluate-hero__content">
      <div class="good-number-hero__text">
        <p class="evaluate-kicker">{{ $heroKicker }}</p>
        <h1 id="evaluate-title">
          <span class="good-number-hero__title-text">ความหมายของหมายเลข</span>
          <span class="good-number-hero__title-number">{{ $number }}</span>
        </h1>
        <p class="good-number-hero__lead">
          อ่านความหมายของเบอร์ พร้อมคำแนะนำการใช้งานของตัวเลขให้เหมาะกับจังหวะของชีวิตและความต้องการของคุณ
        </p>
      </div>
    </div>
  </section>

  <section class="{{ $resultsClass }}">
    <div class="container">
      <div class="evaluate-summary">
        <div class="{{ $summaryNumberClass }}">
          <span>หมายเลขที่คุณเลือก</span>
          <strong>{{ $number }}</strong>
          <a class="summary-order-btn" href="{{ route('book', ['number' => $number, 'package' => $currentPackage]) }}">สั่งซื้อ</a>
        </div>
        <div class="summary-body">
          <h2>{{ $summaryHeading }}</h2>
          <p>{{ $topMeaningSummary }}</p>
        </div>
      </div>

      @if ($themeState === 'danger')
        <div class="danger-banner">
          <div class="danger-icon">!</div>
          <div>
            <h3>คำเตือนระดับสูง</h3>
            <p>
              พบคู่เลขเสียอย่างน้อย 1 คู่ในเบอร์นี้ จึงเปลี่ยนการแสดงผลเป็นธีมเตือนเพื่อเน้นจุดที่ควรระวังและการปรับสมดุล
            </p>
          </div>
        </div>
      @elseif ($themeState === 'warning')
        <div class="warning-banner">
          <div class="danger-icon">!</div>
          <div>
            <h3>คู่เลขต้องดูจังหวะการใช้งาน</h3>
            <p>
              พบคู่เลข 47, 74 หรือ 33 ในเบอร์นี้ จึงใช้ธีมเหลืองเพื่อบอกว่าเป็นเลขที่ใช้ได้บางกรณี ควรดูเป้าหมายและบริบทการใช้งานร่วมด้วย
            </p>
          </div>
        </div>
      @endif

      <section class="theme-overview" aria-labelledby="theme-overview-title">
        <div class="theme-overview__heading">
          <h2 id="theme-overview-title">ภาพรวมหมวดที่เบอร์นี้ส่งเสริม</h2>
          <p>แสดงผลจากหมวดวิเคราะห์ของระบบแบบภาพรวม เพื่อให้ดูออกทันทีว่าด้านไหนของเบอร์นี้เด่นมากหรือน้อย</p>
        </div>
        <div class="theme-overview__grid">
          @foreach ($topicOverviewCards as $topicCard)
            <article class="theme-meter-card theme-meter-card--{{ $topicCard['tone'] }}">
              <h3><span class="theme-meter-card__icon" aria-hidden="true">{{ $topicCard['icon'] }}</span><span>{{ $topicCard['label'] }}</span></h3>
              <div class="theme-meter" role="img" aria-label="{{ $topicCard['aria_label'] }}">
                <span class="theme-meter__dot{{ $topicCard['supports'] ? ' is-active' : '' }}"></span>
              </div>
            </article>
          @endforeach
        </div>
      </section>

      <!-- <div class="evaluate-highlight">
        <div class="highlight-card">
          <h3>การงานและธุรกิจ</h3>
          <p>ช่วยให้ปิดดีลง่ายขึ้น เสริมพลังการตัดสินใจ และทำให้ทีมเชื่อมั่นในทิศทางของคุณ</p>
        </div>
        <div class="highlight-card">
          <h3>การเงินและโชคลาภ</h3>
          <p>รายรับเข้ามาเป็นจังหวะดี เหมาะกับการเริ่มโปรเจกต์ใหม่และขยายฐานลูกค้า</p>
        </div>
        <div class="highlight-card">
          <h3>ความรักและความสัมพันธ์</h3>
          <p>เสน่ห์นุ่มนวล สื่อสารตรงใจ ลดความเข้าใจผิด เหมาะกับการปรับความสัมพันธ์ให้มั่นคง</p>
        </div>
      </div> -->

      <div class="pair-grid">
        @foreach ($pairCards as $card)
          <article class="pair-card {{ $card['class'] }}">
            <div class="pair-score">{{ $card['pair'] }}</div>
            <h4>{{ $card['heading'] }}</h4>
            <p>{{ $card['desc'] }}</p>
          </article>
        @endforeach
      </div>

      <section class="recommend-section" aria-labelledby="recommend-title">
        <h2 id="recommend-title">{{ $recommendHeading }}</h2>
        <div class="card-grid recommend-grid">
          @forelse ($recommendedNumbers as $recommended)
            <article class="number-card number-card--recommend">
              <div class="card-top">{{ $recommended->display_number ?: $recommended->phone_number }}</div>
              <div class="card-body">
                <div class="card-meta-stack">
                  <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $recommended->service_type_label }}</span></span>
                  @if ($recommended->is_prepaid)
                    <span class="card-meta-plan">{{ $recommended->payment_label }}</span>
                  @endif
                  @if ($recommended->is_postpaid)
                    <span class="card-meta-price">{!! $recommended->initial_payment_html !!}</span>
                  @endif
                  @if ($recommended->supported_topic_icons !== [])
                    @php
                      $topicIcons = collect($recommended->supported_topic_icons);
                      $visibleTopicIcons = $topicIcons->take(4);
                      $hasMoreTopicIcons = $topicIcons->count() > 4;
                    @endphp
                    <div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">
                      @foreach ($visibleTopicIcons as $topic)
                        <span class="card-topic-icon" title="{{ $topic['topic'] }}" aria-label="{{ $topic['topic'] }}">{{ $topic['icon'] }}</span>
                      @endforeach
                      @if ($hasMoreTopicIcons)
                        <span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดที่ช่วยเพิ่มเติม">+</span>
                      @endif
                    </div>
                  @endif
                </div>
              </div>
              <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $recommended->phone_number]) }}">สั่งซื้อ</a>
            </article>
          @empty
            <p class="numbers-empty">ยังไม่มีเบอร์แนะนำในระบบตอนนี้</p>
          @endforelse
        </div>
      </section>

      <div class="{{ $ctaClass }}">
        <div>
          <h3>{{ $ctaHeading }}</h3>
          <p>{{ $ctaDescription }}</p>
        </div>
        <a class="cta-btn" href="{{ route('home') }}">กลับไปเลือกเบอร์</a>
      </div>
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      if (!window.SupernumberAnalytics) return;

      window.SupernumberAnalytics.track("view_number_analysis", {
        analysis_theme: @json($themeState ?? 'default'),
      });
    })();
  </script>
@endpush
