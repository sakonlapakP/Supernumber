<?php

namespace App\Services;

use App\Models\EstimateLead;
use App\Models\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EstimateRecommendationService
{
    public const TOPIC_BY_WORK_TYPE = [
        'owner' => ['การเงิน/โชคลาภ', 'ภาวะผู้นำ/อำนาจ', 'การงาน/ความก้าวหน้า'],
        'manager' => ['ภาวะผู้นำ/อำนาจ', 'การสื่อสาร'],
        'freelance' => ['การเงิน/โชคลาภ', 'ความคิดสร้างสรรค์/ไอเดีย', 'การสื่อสาร'],
        'finance' => ['สติปัญญา/การเรียนรู้', 'สุขภาพ/ความเครียด'],
        'real_estate' => ['การสื่อสาร', 'การเงิน/โชคลาภ', 'ความรัก/เสน่ห์'],
        'government' => ['การงาน/ความก้าวหน้า', 'สติปัญญา/การเรียนรู้'],
        'health_beauty' => ['สุขภาพ/ความเครียด', 'ความรัก/เสน่ห์', 'การสื่อสาร'],
        'technical' => ['สติปัญญา/การเรียนรู้', 'การงาน/ความก้าวหน้า'],
        'logistics' => ['การงาน/ความก้าวหน้า', 'สติปัญญา/การเรียนรู้', 'สิ่งศักดิ์สิทธิ์คุ้มครอง/ลางสังหรณ์'],
        'student' => ['สติปัญญา/การเรียนรู้', 'ความคิดสร้างสรรค์/ไอเดีย'],
        'sales' => ['การสื่อสาร', 'การเงิน/โชคลาภ'],
        'service' => ['การสื่อสาร', 'ความรัก/เสน่ห์'],
        'office' => ['การงาน/ความก้าวหน้า', 'สติปัญญา/การเรียนรู้'],
        'online' => ['ความคิดสร้างสรรค์/ไอเดีย', 'การสื่อสาร'],
    ];

    private const LOVE_POSITION_PAIRS = ['62', '26', '36', '63', '46', '64', '23', '32', '24', '42'];
    private const HEALTH_BLOCKED_PATTERNS = ['7', '8', '35', '39', '53', '93'];
    private const HEALTH_REQUIRED_PATTERNS = ['95', '59', '915', '519', '49', '94'];
    private const WORK_REQUIRED_PATTERNS = ['15', '51', '36', '63'];
    private const MONEY_OWNER_PATTERNS = ['78', '87', '28', '82'];
    private const MONEY_GENERAL_PATTERNS = ['16', '61', '56', '65'];
    private const BUSINESS_MONEY_WORK_TYPES = ['owner', 'real_estate'];

    /**
     * @return array{
     *     lead: EstimateLead,
     *     numbers: Collection<int, PhoneNumber>,
     *     work_topics: array<int, string>,
     *     work_topic_cards: array<int, array{topic: string, icon: string}>,
     *     work_rule_text: string,
     *     goal_rule_text: string,
     *     goal_patterns: array<int, string>,
     *     goal_blocked_patterns: array<int, string>,
     *     matched_strict_topics: bool
     * }
     */
    public function buildResult(EstimateLead $lead, int $limit = 12): array
    {
        $workTopics = self::TOPIC_BY_WORK_TYPE[$lead->work_type] ?? [];
        $goalRule = $this->goalRule($lead->goal, $lead->work_type);

        $query = PhoneNumber::query()
            ->available()
            ->orderBy('sale_price')
            ->orderByDesc('id')
            ->limit(800);

        $this->applyGoalQuery($query, $goalRule);

        /** @var Collection<int, PhoneNumber> $candidates */
        $candidates = $query->get();

        $scored = $candidates
            ->map(function (PhoneNumber $number) use ($workTopics, $goalRule): array {
                $supportedTopics = collect($number->supported_topic_icons)->pluck('topic')->all();
                $matchedTopics = array_values(array_intersect($workTopics, $supportedTopics));
                $goalScore = $this->goalScore($number->phone_number, $goalRule);

                return [
                    'number' => $number,
                    'topic_matches' => $matchedTopics,
                    'score' => (count($matchedTopics) * 20) + $goalScore,
                ];
            })
            ->filter(fn (array $item): bool => $goalRule['type'] === null || $this->goalMatches($item['number']->phone_number, $goalRule));

        $strict = $workTopics === []
            ? $scored
            : $scored->filter(fn (array $item): bool => count($item['topic_matches']) === count($workTopics));

        $matchedStrictTopics = $workTopics === [] || $strict->count() >= min(4, $limit);
        $pool = $matchedStrictTopics ? $strict : $scored->filter(fn (array $item): bool => count($item['topic_matches']) > 0);

        if ($pool->count() < $limit) {
            $pool = $pool->merge($scored)->unique(fn (array $item): int => (int) $item['number']->id);
        }

        $numbers = $pool
            ->sort(function (array $a, array $b): int {
                $scoreComparison = $b['score'] <=> $a['score'];

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return ((int) $a['number']->sale_price) <=> ((int) $b['number']->sale_price);
            })
            ->take($limit)
            ->pluck('number')
            ->values();

        return [
            'lead' => $lead,
            'numbers' => $numbers,
            'work_topics' => $workTopics,
            'work_topic_cards' => $this->topicCards($workTopics),
            'work_rule_text' => $this->workRuleText($lead, $workTopics),
            'goal_rule_text' => $goalRule['text'],
            'goal_patterns' => $goalRule['required'],
            'goal_blocked_patterns' => $goalRule['blocked'],
            'matched_strict_topics' => $matchedStrictTopics,
        ];
    }

    /**
     * @return array{type: ?string, required: array<int, string>, blocked: array<int, string>, text: string}
     */
    private function goalRule(?string $goal, ?string $workType): array
    {
        if ($goal === 'love') {
            return [
                'type' => 'love_position',
                'required' => self::LOVE_POSITION_PAIRS,
                'blocked' => [],
                'text' => 'เป้าหมายความรัก: ควรมีเลขเสน่ห์ในเบอร์ เพื่อช่วยเสริมความสัมพันธ์ ภาพลักษณ์ให้น่าดึงดูด และเพิ่มโอกาสที่จะได้พบความสัมพันธ์ที่ดี',
            ];
        }

        if ($goal === 'health') {
            return [
                'type' => 'contains_any',
                'required' => self::HEALTH_REQUIRED_PATTERNS,
                'blocked' => self::HEALTH_BLOCKED_PATTERNS,
                'text' => 'เป้าหมายสุขภาพ: ควรเลือกเบอร์ที่ช่วยให้พลังงานนิ่งขึ้น ลดความเครียด หลีกเลี่ยงเลขที่กระตุ้นความกดดัน และมีเลขสิ่งศักดิ์สิทธิ์คุ้มครอง เพื่อเสริมความอุ่นใจ สติ และการฟื้นตัวทั้งกายใจ',
            ];
        }

        if ($goal === 'work') {
            return [
                'type' => 'contains_any',
                'required' => self::WORK_REQUIRED_PATTERNS,
                'blocked' => [],
                'text' => 'เป้าหมายการงาน: ควรมีเลขที่ช่วยเสริมความก้าวหน้า ความน่าเชื่อถือ และโอกาสในการทำงาน',
            ];
        }

        if ($goal === 'money') {
            $patterns = in_array($workType, self::BUSINESS_MONEY_WORK_TYPES, true)
                ? self::MONEY_OWNER_PATTERNS
                : self::MONEY_GENERAL_PATTERNS;

            return [
                'type' => 'contains_any',
                'required' => $patterns,
                'blocked' => [],
                'text' => 'เป้าหมายการเงิน: ควรมีเลขที่ช่วยเสริมพื้นฐานการบริหารจัดการด้านการเงิน วางแผนก่อนใช้ และเพิ่มโอกาสในการสร้างรายรับอย่างมั่นคง',
            ];
        }

        return [
            'type' => null,
            'required' => [],
            'blocked' => [],
            'text' => 'ยังไม่ได้เลือกเป้าหมาย จึงเรียงผลจากหมวดอาชีพเป็นหลัก',
        ];
    }

    /**
     * @param array{type: ?string, required: array<int, string>, blocked: array<int, string>, text: string} $goalRule
     */
    private function applyGoalQuery(Builder $query, array $goalRule): void
    {
        foreach ($goalRule['blocked'] as $pattern) {
            $query->where('phone_number', 'not like', '%' . $pattern . '%');
        }

        if ($goalRule['type'] === 'love_position') {
            $query->where(function (Builder $inner) use ($goalRule): void {
                foreach ($goalRule['required'] as $pair) {
                    $inner->orWhere('phone_number', 'like', '___' . $pair . '_____');
                }
            });

            return;
        }

        if ($goalRule['required'] !== []) {
            $query->where(function (Builder $inner) use ($goalRule): void {
                foreach ($goalRule['required'] as $pattern) {
                    $inner->orWhere('phone_number', 'like', '%' . $pattern . '%');
                }
            });
        }
    }

    /**
     * @param array{type: ?string, required: array<int, string>, blocked: array<int, string>, text: string} $goalRule
     */
    private function goalMatches(?string $phoneNumber, array $goalRule): bool
    {
        $digits = $this->digitsOnly($phoneNumber);

        foreach ($goalRule['blocked'] as $pattern) {
            if (str_contains($digits, $pattern)) {
                return false;
            }
        }

        if ($goalRule['type'] === 'love_position') {
            return in_array(substr($digits, 3, 2), $goalRule['required'], true);
        }

        if ($goalRule['required'] === []) {
            return true;
        }

        foreach ($goalRule['required'] as $pattern) {
            if (str_contains($digits, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{type: ?string, required: array<int, string>, blocked: array<int, string>, text: string} $goalRule
     */
    private function goalScore(?string $phoneNumber, array $goalRule): int
    {
        if (! $this->goalMatches($phoneNumber, $goalRule)) {
            return 0;
        }

        $digits = $this->digitsOnly($phoneNumber);
        $score = 10;

        foreach ($goalRule['required'] as $pattern) {
            if (str_contains($digits, $pattern)) {
                $score += strlen($pattern) >= 3 ? 8 : 5;
            }
        }

        return $score;
    }

    /**
     * @param array<int, string> $topics
     * @return array<int, array{topic: string, icon: string}>
     */
    private function topicCards(array $topics): array
    {
        return array_map(
            static fn (string $topic): array => [
                'topic' => $topic,
                'icon' => PhoneNumber::TOPIC_ICON_MAP[$topic] ?? '•',
            ],
            $topics
        );
    }

    /**
     * @param array<int, string> $workTopics
     */
    private function workRuleText(EstimateLead $lead, array $workTopics): string
    {
        $workType = $lead->work_type_label;

        if ($workTopics === []) {
            return 'อาชีพ: ' . $workType . ' ยังไม่มี mapping หมวดเฉพาะ จึงใช้เป้าหมายเป็นตัวคัดหลัก';
        }

        return 'อาชีพ: ' . $workType . ' เน้นหมวด ' . implode(', ', $workTopics);
    }

    private function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
