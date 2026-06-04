<?php

namespace App\Services;

use App\Models\PairMeaning;

/**
 * Auspicious-phone-number analyzer ("วิเคราะห์เบอร์มงคล").
 *
 * Single source of truth for the numerology scoring that previously lived inline
 * in resources/views/evaluate.blade.php. Consumed by:
 *  - the public API (POST /api/number/analyze) used by Phayakorn ("Powered by Supernumber")
 *  - any future Blade / Flutter consumer
 *
 * Pure calculation: it reads pair meanings from the pair_meanings table but never
 * touches the phone catalog, so it can score any arbitrary number.
 */
class PhoneNumberAnalyzer
{
    /** Pairs that flag a "watch" (warning) theme even when no pair is outright bad. */
    private const WARNING_PAIRS = ['33', '47', '74'];

    private const STATUS_LABEL_MAP = [
        'good' => 'ตรง',
        'bad' => 'เลขควรระวัง',
        'conditional' => 'ใช้ได้บางกรณี',
    ];

    private const STATUS_CLASS_MAP = [
        'good' => 'is-good',
        'bad' => 'is-danger',
        'conditional' => 'is-neutral',
        'neutral' => 'is-neutral',
    ];

    private const PAIR_HEADING_MAP = [
        'good' => 'คู่เลขดี',
        'bad' => 'คู่เลขเสีย',
        'conditional' => 'คู่เลขดีกับคู่เลขเสีย',
        'neutral' => 'คู่เลขดีกับคู่เลขเสีย',
    ];

    /** Topics shown in the at-a-glance overview, in display order. */
    private const TOPIC_OVERVIEW_ORDER = [
        'การสื่อสาร',
        'ความรัก/เสน่ห์',
        'การงาน/ความก้าวหน้า',
        'การเงิน/โชคลาภ',
        'ภาวะผู้นำ/อำนาจ',
        'ความคิดสร้างสรรค์/ไอเดีย',
        'สติปัญญา/การเรียนรู้',
        'สุขภาพ/ความเครียด',
        'สิ่งศักดิ์สิทธิ์คุ้มครอง/ลางสังหรณ์',
    ];

    private const TOPIC_ICON_MAP = [
        'การสื่อสาร' => '💬',
        'ความรัก/เสน่ห์' => '💖',
        'การงาน/ความก้าวหน้า' => '💼',
        'การเงิน/โชคลาภ' => '💰',
        'ภาวะผู้นำ/อำนาจ' => '👑',
        'ความคิดสร้างสรรค์/ไอเดีย' => '💡',
        'สติปัญญา/การเรียนรู้' => '🧠',
        'สุขภาพ/ความเครียด' => '🌿',
        'สิ่งศักดิ์สิทธิ์คุ้มครอง/ลางสังหรณ์' => '✨',
    ];

    /**
     * Analyze any phone number and return a structured result.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $rawPhone): array
    {
        $number = preg_replace('/[^0-9]/', '', $rawPhone) ?? '';

        $lastSeven = substr($number, -7);
        $pairs = [];
        for ($i = 0; $i < 6; $i++) {
            $pairs[] = substr($lastSeven, $i, 2);
        }

        $pairMeaningMap = PairMeaning::query()
            ->whereIn('pair', $this->allPairVariants($pairs))
            ->get()
            ->keyBy('pair');

        $score = $this->computeScore($pairs, $pairMeaningMap);

        $pairCards = $this->buildPairCards($pairs, $pairMeaningMap);
        $hasBadPair = collect($pairCards)->contains(fn (array $card): bool => $card['status'] === 'bad');
        $hasWarningPair = count(array_intersect($pairs, self::WARNING_PAIRS)) > 0;

        $theme = $hasBadPair ? 'danger' : ($hasWarningPair ? 'warning' : 'good');

        return [
            'phone' => $number,
            'formatted' => $this->format($number),
            'score' => $score,
            'grade' => $this->grade($score),
            'theme' => $theme,
            'summary' => $this->buildSummary($theme, $pairs, $pairMeaningMap),
            'pairs' => $pairCards,
            'topics' => $this->buildTopicOverview($pairs),
            'powered_by' => 'Supernumber',
        ];
    }

    /**
     * Score: each of the 6 trailing pairs contributes up to 100/6 points.
     * good = full, neutral/conditional = half, bad = none. Capped at 100.
     */
    private function computeScore(array $pairs, $pairMeaningMap): int
    {
        $rawScore = 0.0;
        foreach ($pairs as $pair) {
            $status = $this->statusForPair($pair, $pairMeaningMap);
            if ($status === 'good') {
                $rawScore += 100 / 6;
            } elseif ($status === 'bad') {
                // no points
            } else {
                $rawScore += 100 / 12;
            }
        }

        return min(100, (int) round($rawScore));
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }

    private function statusForPair(string $pair, $pairMeaningMap): string
    {
        $meaning = $pairMeaningMap->get($pair) ?? $pairMeaningMap->get(strrev($pair));

        return $meaning?->status ?? 'neutral';
    }

    /**
     * One card per distinct (order-insensitive) pair, in first-appearance order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPairCards(array $pairs, $pairMeaningMap): array
    {
        $grouped = [];
        foreach ($pairs as $index => $pair) {
            $chars = str_split($pair);
            sort($chars);
            $key = implode('', $chars);
            if (! isset($grouped[$key])) {
                $grouped[$key] = ['key' => $key, 'primary_pair' => $pair, 'first_index' => $index];
            }
        }
        usort($grouped, fn ($a, $b) => $a['first_index'] <=> $b['first_index']);

        $cards = [];
        foreach ($grouped as $group) {
            $label = $group['key'];
            if (strlen($label) === 2 && $label[0] !== $label[1]) {
                $label = $label . ' ' . strrev($label);
            }

            $meaning = $pairMeaningMap->get($group['primary_pair'])
                ?? $pairMeaningMap->get($group['key'])
                ?? $pairMeaningMap->get(strrev($group['key']));
            $status = $meaning?->status ?? 'neutral';

            $cards[] = [
                'pair' => $label,
                'primary' => $group['primary_pair'],
                'status' => $status,
                'label' => self::STATUS_LABEL_MAP[$status] ?? 'เลขทั่วไป',
                'heading' => self::PAIR_HEADING_MAP[$status] ?? 'คู่เลขดีกับคู่เลขเสีย',
                'short' => $meaning?->short_meaning ?? 'ยังไม่มีข้อมูลความหมายแบบสั้นสำหรับคู่นี้',
                'class' => self::STATUS_CLASS_MAP[$status] ?? 'is-neutral',
            ];
        }

        return $cards;
    }

    /**
     * Pick the highest-scoring pair group (by position weight) as the headline meaning.
     *
     * @return array<string, string>
     */
    private function buildSummary(string $theme, array $pairs, $pairMeaningMap): array
    {
        $groupScores = [];
        foreach ($pairs as $index => $pair) {
            $chars = str_split($pair);
            sort($chars);
            $key = implode('', $chars);
            $positionScore = $index < 5 ? 10 : 30; // last pair weighted higher
            if (! isset($groupScores[$key])) {
                $groupScores[$key] = ['key' => $key, 'score' => 0, 'first_index' => $index];
            }
            $groupScores[$key]['score'] += $positionScore;
        }

        $topGroup = null;
        foreach ($groupScores as $meta) {
            if ($topGroup === null
                || $meta['score'] > $topGroup['score']
                || ($meta['score'] === $topGroup['score'] && $meta['first_index'] < $topGroup['first_index'])) {
                $topGroup = $meta;
            }
        }

        $topMeaningRecord = $topGroup !== null
            ? PairMeaning::query()->where('pair', $topGroup['key'])->first()
            : null;

        $title = $topMeaningRecord
            ? (self::STATUS_LABEL_MAP[$topMeaningRecord->status] ?? 'เลขทั่วไป')
            : 'เลขทั่วไป';

        $text = $topMeaningRecord
            ? trim($topMeaningRecord->long_meaning ?: $topMeaningRecord->short_meaning)
            : 'คู่นี้มีคะแนนสูงสุดตามตำแหน่ง แนะนำประเมินร่วมกับคู่เลขอื่นเพื่อความแม่นยำ';

        if (($topMeaningRecord?->status ?? null) === 'good') {
            $text = preg_replace('/^เลขชุดนี้/u', 'ผู้ที่ใช้เบอร์นี้', $text) ?? $text;
        } elseif ($topMeaningRecord !== null) {
            $text = $title . ' — ' . $text;
        }

        $heading = match ($theme) {
            'danger' => 'เบอร์นี้มีคู่เลขที่ควรระวัง',
            'warning' => 'เบอร์นี้มีคู่เลขใช้ได้บางกรณี',
            default => 'เบอร์นี้ดี',
        };

        return [
            'heading' => $heading,
            'title' => $title,
            'text' => $text,
        ];
    }

    /**
     * At-a-glance support for each life topic, scored from the 6 trailing pairs
     * (last pair weighted x2).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTopicOverview(array $pairs): array
    {
        $topicPairMap = $this->topicPairMap();

        $lastPairIndex = count($pairs) - 1;
        $weightedPairs = [];
        foreach ($pairs as $index => $pair) {
            $weightedPairs[] = [
                'variants' => $this->pairVariants($pair),
                'weight' => $index === $lastPairIndex ? 2 : 1,
            ];
        }

        $cards = [];
        foreach (self::TOPIC_OVERVIEW_ORDER as $topic) {
            $topicPairs = $topicPairMap[$topic] ?? null;
            if (! is_array($topicPairs)) {
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
                } elseif (count(array_intersect($variants, $topicPairs['conditional'])) > 0) {
                    $conditionalWeight += $weight;
                } elseif (count(array_intersect($variants, $topicPairs['bad'])) > 0) {
                    $badWeight += $weight;
                }
            }

            $supports = ($goodWeight + ($conditionalWeight * 0.5)) > $badWeight;

            $cards[] = [
                'label' => $topic,
                'icon' => self::TOPIC_ICON_MAP[$topic] ?? '•',
                'supports' => $supports,
            ];
        }

        return $cards;
    }

    public function format(string $digits): string
    {
        if (strlen($digits) !== 10) {
            return $digits;
        }

        return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
    }

    /** @return array<int, string> pair + reverse for a DB lookup that covers both orderings */
    private function allPairVariants(array $pairs): array
    {
        $variants = [];
        foreach ($pairs as $pair) {
            $variants[] = $pair;
            $variants[] = strrev($pair);
            $chars = str_split($pair);
            sort($chars);
            $variants[] = implode('', $chars);
        }

        return array_values(array_unique($variants));
    }

    /** @return array<int, string> */
    private function pairVariants(string $pair): array
    {
        $chars = str_split($pair);
        sort($chars);
        $normalized = implode('', $chars);

        return array_values(array_unique([$pair, strrev($pair), $normalized]));
    }

    /**
     * Good/bad/conditional pairs per life topic. Kept here verbatim from the
     * legacy Blade so the API matches the existing web report exactly.
     *
     * @return array<string, array{good: array<int,string>, bad: array<int,string>, conditional: array<int,string>}>
     */
    private function topicPairMap(): array
    {
        return [
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
            'สิ่งศักดิ์สิทธิ์คุ้มครอง/ลางสังหรณ์' => [
                'good' => ['49', '59', '79', '89', '94', '95', '97', '98', '99'],
                'bad' => ['00', '09', '90'],
                'conditional' => [],
            ],
        ];
    }
}
