<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticlePlan;
use App\Models\FacebookImportedPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacebookContentRefreshService
{
    private const SKIP_TOPIC_PATTERNS = [
        '/evergreen/i',
        '/pillar content/i',
        '/คั่นกลาง/u',
        '/อุดช่องว่าง/u',
        '/ปิดท้าย/u',
    ];

    /**
     * @return array{
     *   processed: int,
     *   created: int,
     *   updated: int,
     *   deleted_imports: int,
     *   skipped: int,
     *   topics: array<int, array<string, mixed>>
     * }
     */
    public function refresh(?int $authorUserId = null): array
    {
        return $this->run($authorUserId, false);
    }

    /**
     * @return array{
     *   processed: int,
     *   created: int,
     *   updated: int,
     *   deleted_imports: int,
     *   skipped: int,
     *   topics: array<int, array<string, mixed>>
     * }
     */
    public function preview(?int $authorUserId = null): array
    {
        return $this->run($authorUserId, true);
    }

    /**
     * @return array{
     *   processed: int,
     *   created: int,
     *   updated: int,
     *   deleted_imports: int,
     *   skipped: int,
     *   topics: array<int, array<string, mixed>>
     * }
     */
    private function run(?int $authorUserId, bool $dryRun): array
    {
        $imports = FacebookImportedPost::query()
            ->orderByDesc('facebook_created_time')
            ->orderByDesc('id')
            ->get();

        $topics = $this->eligibleTopicGroups();

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted_imports' => 0,
            'skipped' => 0,
            'topics' => [],
        ];

        $run = function () use ($imports, $topics, $authorUserId, $dryRun, &$summary): void {
            $remainingImports = $imports;

            foreach ($topics as $topicKey => $plans) {
                $result = $this->refreshTopicGroup($topicKey, $plans, $remainingImports, $authorUserId, $dryRun);

                if ($result === null) {
                    $summary['skipped']++;
                    continue;
                }

                $summary['processed']++;
                $summary['created'] += $result['created'] ? 1 : 0;
                $summary['updated'] += $result['created'] ? 0 : 1;
                $summary['deleted_imports'] += $result['deleted_imports'];
                $summary['topics'][] = $result;

                $matchedIds = $result['matched_import_ids'];
                $remainingImports = $remainingImports
                    ->reject(function (FacebookImportedPost $post) use ($matchedIds): bool {
                        return in_array($post->id, $matchedIds, true);
                    })
                    ->values();
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        return $summary;
    }

    /**
     * @return Collection<string, Collection<int, ArticlePlan>>
     */
    private function eligibleTopicGroups(): Collection
    {
        $plans = ArticlePlan::query()
            ->orderBy('publish_date')
            ->orderBy('publish_time')
            ->get()
            ->groupBy(function (ArticlePlan $plan): string {
                return $this->normalizeTopic((string) $plan->topic);
            });

        return $plans->reject(function (Collection $group, string $topicKey): bool {
            return $topicKey === '' || $this->shouldSkipTopic($topicKey);
        });
    }

    private function normalizeTopic(string $topic): string
    {
        $topic = trim($topic);
        if ($topic === '') {
            return '';
        }

        $parts = preg_split('/[:：]/u', $topic, 2);
        $base = trim((string) ($parts[0] ?? $topic));

        return preg_replace('/\s+/u', ' ', $base) ?: '';
    }

    private function shouldSkipTopic(string $topicKey): bool
    {
        $normalized = mb_strtolower($topicKey);

        if ($normalized === '') {
            return true;
        }

        foreach (self::SKIP_TOPIC_PATTERNS as $pattern) {
            if (preg_match($pattern, $topicKey) === 1) {
                return true;
            }
        }

        return str_contains($normalized, 'คอนเทนต์หวย')
            || str_contains($normalized, 'หวย')
            || str_contains($normalized, 'evergreen')
            || str_contains($normalized, 'pillar content');
    }

    /**
     * @param Collection<int, ArticlePlan> $plans
     * @param Collection<int, FacebookImportedPost> $imports
     * @return array<string, mixed>|null
     */
    private function refreshTopicGroup(string $topicKey, Collection $plans, Collection $imports, ?int $authorUserId, bool $dryRun): ?array
    {
        $searchTerms = $this->searchTermsForTopic($topicKey);
        $matchedImports = $this->matchImportsByTopic($imports, $searchTerms, $plans);

        if ($matchedImports->isEmpty()) {
            return null;
        }

        $latestImport = $matchedImports->sort(function (FacebookImportedPost $left, FacebookImportedPost $right): int {
            return $this->compareImportOrder($left, $right);
        })->last();

        $earliestImport = $matchedImports->sort(function (FacebookImportedPost $left, FacebookImportedPost $right): int {
            return $this->compareImportOrder($right, $left);
        })->last();

        if (! $latestImport || ! $earliestImport) {
            return null;
        }

        $articleData = $this->buildArticleData($topicKey, $latestImport, $earliestImport, $authorUserId);
        $slug = $articleData['slug'];

        $existing = Article::query()->where('slug', $slug)->first();
        $created = $existing === null;

        if ($existing) {
            if (! $dryRun) {
                $existing->fill($articleData);
                $existing->save();
            }
        } else {
            if ($dryRun) {
                $existing = new Article($articleData);
                $existing->setAttribute('id', null);
            } else {
                $existing = Article::query()->create($articleData);
            }
        }

        $deletedIds = $matchedImports->pluck('id')->all();
        if (! $dryRun) {
            FacebookImportedPost::query()
                ->whereIn('id', $deletedIds)
                ->delete();
        }

        return [
            'topic' => $topicKey,
            'slug' => $existing->slug,
            'article_id' => $existing->id,
            'title' => $articleData['title'],
            'excerpt' => $articleData['excerpt'],
            'meta_description' => $articleData['meta_description'],
            'created' => $created,
            'published_at' => optional($existing->published_at)?->toIso8601String(),
            'deleted_imports' => count($deletedIds),
            'matched_import_ids' => $deletedIds,
        ];
    }

    /**
     * @param Collection<int, FacebookImportedPost> $imports
     * @param array<int, string> $searchTerms
     * @param Collection<int, ArticlePlan> $plans
     * @return Collection<int, FacebookImportedPost>
     */
    private function matchImportsByTopic(Collection $imports, array $searchTerms, Collection $plans): Collection
    {
        $imports = $imports->filter(function (FacebookImportedPost $post): bool {
            return ! $this->isVideoPost($post);
        });

        $matches = $imports->filter(function (FacebookImportedPost $post) use ($searchTerms): bool {
            $haystack = mb_strtolower(trim(($post->message ?? '') . "\n" . ($post->story ?? '')));
            if ($haystack === '') {
                return false;
            }

            foreach ($searchTerms as $term) {
                $needle = mb_strtolower(trim($term));
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    return true;
                }
            }

            return false;
        });

        if ($matches->isNotEmpty()) {
            return $matches->values();
        }

        $fallbackMonthDays = $plans
            ->map(function (ArticlePlan $plan): string {
                return Carbon::parse($plan->publish_date)->format('m-d');
            })
            ->unique()
            ->values();

        if ($fallbackMonthDays->isEmpty()) {
            return collect();
        }

        return $imports
            ->filter(function (FacebookImportedPost $post) use ($fallbackMonthDays): bool {
                $createdAt = $this->resolvePostDate($post);
                if ($createdAt === null) {
                    return false;
                }

                return $fallbackMonthDays->contains($createdAt->format('m-d'));
            })
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function searchTermsForTopic(string $topicKey): array
    {
        $baseTerms = [$topicKey];

        $lookup = [
            'วันขึ้นปีใหม่' => ['ปีใหม่', 'สวัสดีปีใหม่', 'ขึ้นปีใหม่'],
            'วันเด็ก' => ['วันเด็ก'],
            'วันตรุษจีน' => ['วันตรุษจีน', 'ตรุษจีน', 'ปีใหม่จีน'],
            'วันวาเลนไทน์' => ['วันวาเลนไทน์', 'วาเลนไทน์'],
            'วันมาฆบูชา' => ['วันมาฆบูชา', 'มาฆบูชา'],
            'วันวิสาขบูชา' => ['วันวิสาขบูชา', 'วิสาขบูชา'],
            'วันพืชมงคล' => ['วันพืชมงคล', 'พืชมงคล'],
            'วันสุนทรภู่' => ['วันสุนทรภู่', 'สุนทรภู่'],
            'วันอาสาฬหบูชา' => ['วันอาสาฬหบูชา', 'อาสาฬหบูชา'],
            'วันสงกรานต์' => ['วันสงกรานต์', 'สงกรานต์'],
            'วันแม่' => ['วันแม่'],
            'วันคเณศจตุรถี' => ['วันคเณศจตุรถี', 'คเณศจตุรถี', 'คเณศ', 'พระพิฆเนศ'],
            'วันไหว้พระจันทร์' => ['วันไหว้พระจันทร์', 'ไหว้พระจันทร์'],
            'เทศกาลกินเจ' => ['เทศกาลกินเจ', 'กินเจ'],
            'วันปิยมหาราช' => ['วันปิยมหาราช', 'ปิยมหาราช'],
            'วันลอยกระทง' => ['วันลอยกระทง', 'ลอยกระทง'],
            'วันพ่อ' => ['วันพ่อ'],
            'วันรัฐธรรมนูญ' => ['วันรัฐธรรมนูญ', 'รัฐธรรมนูญ'],
            'วันสิ้นปี' => ['วันสิ้นปี', 'สิ้นปี', 'ส่งท้ายปี', 'ปลายปี'],
            'คอนเทนต์มงคลรวมใจ (ร.10)' => ['คอนเทนต์มงคลรวมใจ', 'ร.10', 'รัชกาลที่ 10', 'ในหลวง'],
            'วันจักรี' => ['วันจักรี', 'จักรี', '6 เมษายน', 'พระพุทธยอดฟ้า'],
        ];

        foreach ($lookup as $key => $terms) {
            if ($key === $topicKey) {
                $baseTerms = array_merge($baseTerms, $terms);
                break;
            }
        }

        $terms = [];
        foreach ($baseTerms as $term) {
            $term = trim($term);
            if ($term !== '') {
                $terms[] = $term;
            }
        }

        return array_values(array_unique($terms));
    }

    private function isVideoPost(FacebookImportedPost $post): bool
    {
        $attachments = is_array($post->attachments_json) ? $post->attachments_json : [];
        if ($this->containsVideoMarker($attachments)) {
            return true;
        }

        $raw = is_array($post->raw_json) ? $post->raw_json : [];
        return $this->containsVideoMarker($raw);
    }

    /**
     * @param mixed $value
     */
    private function containsVideoMarker(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            if (is_string($item) && mb_strtolower($item) === 'video') {
                return true;
            }

            if ((is_string($key) && in_array(mb_strtolower($key), ['media_type', 'type'], true))
                && is_string($item)
                && mb_strtolower($item) === 'video') {
                return true;
            }

            if ($this->containsVideoMarker($item)) {
                return true;
            }
        }

        return false;
    }

    private function resolvePostDate(FacebookImportedPost $post): ?Carbon
    {
        $date = $post->facebook_created_time
            ?? $post->imported_at
            ?? $post->created_at;

        if (! $date) {
            return null;
        }

        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    private function compareImportOrder(FacebookImportedPost $left, FacebookImportedPost $right): int
    {
        $leftDate = $this->resolvePostDate($left);
        $rightDate = $this->resolvePostDate($right);

        if ($leftDate === null && $rightDate === null) {
            return $left->id <=> $right->id;
        }

        if ($leftDate === null) {
            return -1;
        }

        if ($rightDate === null) {
            return 1;
        }

        $comparison = $leftDate->getTimestamp() <=> $rightDate->getTimestamp();
        if ($comparison !== 0) {
            return $comparison;
        }

        return $left->id <=> $right->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildArticleData(string $topicKey, FacebookImportedPost $latestImport, FacebookImportedPost $earliestImport, ?int $authorUserId): array
    {
        $meta = $this->topicMetadata($topicKey);
        $latestDate = $this->resolvePostDate($latestImport) ?? Carbon::now('Asia/Bangkok');
        $earliestDate = $this->resolvePostDate($earliestImport) ?? $latestDate;
        $storedImagePath = $this->storePostImage($latestImport, (string) $meta['slug'], $latestDate);

        $latestBeYear = $latestDate->copy()->timezone('Asia/Bangkok')->year + 543;
        $earliestBeYear = $earliestDate->copy()->timezone('Asia/Bangkok')->year + 543;

        $title = sprintf(
            '%s %d: %s',
            $meta['display_name'],
            $latestBeYear,
            $meta['title_suffix']
        );

        $contentHtml = $this->buildArticleContentHtml($meta['display_name'], $latestDate, $earliestDate, $latestImport, $storedImagePath);
        $excerpt = $this->buildExcerpt($latestImport, $title);
        $metaDescription = $this->buildMetaDescription($meta['display_name'], $latestBeYear, $excerpt);

        $articleData = [
            'title' => $title,
            'slug' => $meta['slug'],
            'excerpt' => $excerpt,
            'content' => $contentHtml,
            'meta_description' => $metaDescription,
            'keywords' => $meta['keywords'],
            'lsi_keywords' => $meta['lsi_keywords'],
            'is_published' => true,
            'is_auto_post' => false,
            'published_at' => $earliestDate->copy()->timezone(config('app.timezone')),
            'author_user_id' => $authorUserId,
        ];

        if ($storedImagePath !== null) {
            $articleData['cover_image_path'] = $storedImagePath;
            $articleData['cover_image_square_path'] = $storedImagePath;
            $articleData['cover_image_landscape_path'] = $storedImagePath;
        }

        return $articleData;
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function topicMetadata(string $topicKey): array
    {
        $map = [
            'วันขึ้นปีใหม่' => [
                'display_name' => 'ปีใหม่',
                'slug' => 'new-year-lucky-numbers',
                'title_suffix' => 'เลขมงคลเริ่มต้นปีให้ปัง',
                'keywords' => 'ปีใหม่, เลขมงคล, เสริมดวง',
                'lsi_keywords' => 'สวัสดีปีใหม่, โชคลาภ, เริ่มต้นปี, พลังบวก, เลขนำโชค, เฮง, ดวงดี',
            ],
            'วันเด็ก' => [
                'display_name' => 'วันเด็ก',
                'slug' => 'children-day-lucky-numbers',
                'title_suffix' => 'เลขมงคลเสริม IQ และพลังใจ',
                'keywords' => 'วันเด็ก, เลขมงคล, เสริม IQ',
                'lsi_keywords' => 'เด็ก, พัฒนาการ, ครอบครัว, การเรียน, ความคิดสร้างสรรค์, อนาคต, พลังบวก',
            ],
            'วันตรุษจีน' => [
                'display_name' => 'วันตรุษจีน',
                'slug' => 'chinese-new-year-wealth-numbers',
                'title_suffix' => 'เลขรับทรัพย์เสริมโชคลาภ',
                'keywords' => 'วันตรุษจีน, เลขมงคล, รับทรัพย์',
                'lsi_keywords' => 'อั่งเปา, มั่งคั่ง, โชคลาภ, ปีใหม่จีน, เฮง, ธุรกิจ, ความมั่งคั่ง',
            ],
            'วันวาเลนไทน์' => [
                'display_name' => 'วันวาเลนไทน์',
                'slug' => 'valentines-day-love-numbers',
                'title_suffix' => 'คู่เลขความรักที่ควรรู้',
                'keywords' => 'วันวาเลนไทน์, เลขความรัก, คู่เลข',
                'lsi_keywords' => 'ความรัก, คนโสด, คู่รัก, โรแมนติก, ดวงคู่ครอง, ความสัมพันธ์, หวานใจ',
            ],
            'วันมาฆบูชา' => [
                'display_name' => 'วันมาฆบูชา',
                'slug' => 'magha-bucha-number-guidance',
                'title_suffix' => 'เลขสายบุญและสมาธิ',
                'keywords' => 'วันมาฆบูชา, เลขสายบุญ, สมาธิ',
                'lsi_keywords' => 'ทำบุญ, สติ, ปัญญา, ธรรมะ, ความสงบ, กุศล, พลังใจ',
            ],
            'วันวิสาขบูชา' => [
                'display_name' => 'วันวิสาขบูชา',
                'slug' => 'visakha-bucha-wisdom-numbers',
                'title_suffix' => 'เลขสติปัญญาและการเริ่มต้นใหม่',
                'keywords' => 'วันวิสาขบูชา, เลขมงคล, เริ่มต้นใหม่',
                'lsi_keywords' => 'พุทธประวัติ, ปัญญา, ความสงบ, ธรรมะ, การเริ่มต้น, ศรัทธา, จิตใจ',
            ],
            'วันพืชมงคล' => [
                'display_name' => 'วันพืชมงคล',
                'slug' => 'royal-ploughing-ceremony-lucky-numbers',
                'title_suffix' => 'เลขมงคลการเงินและความมั่งคั่ง',
                'keywords' => 'วันพืชมงคล, เลขมงคล, การเงิน',
                'lsi_keywords' => 'เกษตร, ความอุดมสมบูรณ์, มั่งคั่ง, เจริญงอกงาม, ฤกษ์ดี, ผลผลิต, ความมั่นคง',
            ],
            'วันสุนทรภู่' => [
                'display_name' => 'วันสุนทรภู่',
                'slug' => 'sunthorn-phu-number-guidance',
                'title_suffix' => 'เลขมงคลสายวาทศิลป์และการเจรจา',
                'keywords' => 'วันสุนทรภู่, วาทศิลป์, เลขมงคล',
                'lsi_keywords' => 'กวี, ภาษา, การสื่อสาร, ปากกานำโชค, เสน่ห์, การเจรจา, ความคิด',
            ],
            'วันอาสาฬหบูชา' => [
                'display_name' => 'วันอาสาฬหบูชา',
                'slug' => 'asarnha-bucha-number-balance',
                'title_suffix' => 'ปรับพลังงานตัวเลข',
                'keywords' => 'วันอาสาฬหบูชา, ปรับพลังงาน, เลขมงคล',
                'lsi_keywords' => 'ธรรมะ, ความสงบ, พลังชีวิต, สมดุล, ปัญญา, ศรัทธา, ทำบุญ',
            ],
            'วันสงกรานต์' => [
                'display_name' => 'วันสงกรานต์',
                'slug' => 'songkran-safe-travel-numbers',
                'title_suffix' => 'เลขปลอดภัยในการเดินทาง',
                'keywords' => 'วันสงกรานต์, เดินทางปลอดภัย, เลขมงคล',
                'lsi_keywords' => 'น้ำ, ครอบครัว, กลับบ้าน, ความสุข, ความปลอดภัย, ปีใหม่ไทย, เฮง',
            ],
            'วันแม่' => [
                'display_name' => 'วันแม่',
                'slug' => 'mother-day-health-lucky-numbers',
                'title_suffix' => 'เลขมงคลสุขภาพและความอบอุ่น',
                'keywords' => 'วันแม่, เลขมงคล, สุขภาพ',
                'lsi_keywords' => 'ครอบครัว, ความรัก, ความกตัญญู, ดูแลสุขภาพ, อบอุ่น, ความผูกพัน, ความสุข',
            ],
            'วันคเณศจตุรถี' => [
                'display_name' => 'วันคเณศจตุรถี',
                'slug' => 'ganesh-chaturthi-blessing-numbers',
                'title_suffix' => 'เลขมงคลประทานพร',
                'keywords' => 'คเณศจตุรถี, พระพิฆเนศ, เลขมงคล',
                'lsi_keywords' => 'ขอพร, ความสำเร็จ, โชคลาภ, ปัญญา, อุปสรรค, การงาน, ศรัทธา',
            ],
            'วันไหว้พระจันทร์' => [
                'display_name' => 'วันไหว้พระจันทร์',
                'slug' => 'mid-autumn-festival-wealth-numbers',
                'title_suffix' => 'เลขเมตตามหานิยม',
                'keywords' => 'ไหว้พระจันทร์, เลขมงคล, เมตตา',
                'lsi_keywords' => 'ดวงจันทร์, ครอบครัว, ความรัก, ความอ่อนโยน, โชคลาภ, ความสามัคคี, ความสุข',
            ],
            'เทศกาลกินเจ' => [
                'display_name' => 'เทศกาลกินเจ',
                'slug' => 'vegetarian-festival-pure-luck-numbers',
                'title_suffix' => 'เลขสายขาวและพลังใจ',
                'keywords' => 'เทศกาลกินเจ, เลขมงคล, สายขาว',
                'lsi_keywords' => 'ถือศีล, งดเนื้อสัตว์, จิตใจบริสุทธิ์, ทำบุญ, ความสงบ, พลังบวก, เมตตา',
            ],
            'วันปิยมหาราช' => [
                'display_name' => 'วันปิยมหาราช',
                'slug' => 'chulalongkorn-day-career-numbers',
                'title_suffix' => 'เลขมงคลการงานและความก้าวหน้า',
                'keywords' => 'ปิยมหาราช, เลขมงคล, การงาน',
                'lsi_keywords' => 'รัชกาลที่ 5, ความก้าวหน้า, เกียรติยศ, ความมั่นคง, ผู้ใหญ่สนับสนุน, โอกาส, ความสำเร็จ',
            ],
            'วันลอยกระทง' => [
                'display_name' => 'วันลอยกระทง',
                'slug' => 'loy-krathong-wish-luck-numbers',
                'title_suffix' => 'เลขขอพรโชคลาภ',
                'keywords' => 'ลอยกระทง, เลขมงคล, ขอพร',
                'lsi_keywords' => 'ปล่อยเคราะห์, ความรัก, โชคลาภ, ความอ่อนโยน, น้ำ, ขอขมา, ความหวัง',
            ],
            'วันพ่อ' => [
                'display_name' => 'วันพ่อ',
                'slug' => 'father-day-stability-numbers',
                'title_suffix' => 'เลขมงคลความมั่นคง',
                'keywords' => 'วันพ่อ, เลขมงคล, ความมั่นคง',
                'lsi_keywords' => 'ครอบครัว, ความรัก, ความรับผิดชอบ, ผู้ชาย, ความอบอุ่น, การสนับสนุน, ความสำเร็จ',
            ],
            'วันรัฐธรรมนูญ' => [
                'display_name' => 'วันรัฐธรรมนูญ',
                'slug' => 'constitution-day-discipline-numbers',
                'title_suffix' => 'เลขมงคลระเบียบวินัย',
                'keywords' => 'วันรัฐธรรมนูญ, เลขมงคล, วินัย',
                'lsi_keywords' => 'กฎหมาย, ระเบียบ, ความถูกต้อง, ความมั่นคง, ระบบ, ความรับผิดชอบ, ความเรียบร้อย',
            ],
            'วันสิ้นปี' => [
                'display_name' => 'วันสิ้นปี',
                'slug' => 'year-end-luck-summary',
                'title_suffix' => 'สรุปเลขปีและโอกาสใหม่',
                'keywords' => 'วันสิ้นปี, เลขมงคล, สรุปปี',
                'lsi_keywords' => 'ส่งท้ายปี, ปีใหม่, สรุปผล, เป้าหมาย, เริ่มต้นใหม่, โชคลาภ, ความสำเร็จ',
            ],
            'คอนเทนต์มงคลรวมใจ (ร.10)' => [
                'display_name' => 'คอนเทนต์มงคลรวมใจ (ร.10)',
                'slug' => 'royal-number-tribute-r10',
                'title_suffix' => 'เลขมงคลรวมพลังใจ',
                'keywords' => 'ร.10, เลขมงคล, ในหลวง',
                'lsi_keywords' => 'รัชกาลที่ 10, ในหลวง, ความจงรักภักดี, พลังใจ, ความสามัคคี, ความก้าวหน้า, ศรัทธา',
            ],
            'วันจักรี' => [
                'display_name' => 'วันจักรี',
                'slug' => 'chakri-day-power-numbers',
                'title_suffix' => 'เลขเสริมอำนาจและเกียรติยศ',
                'keywords' => 'วันจักรี, เลขมงคล, อำนาจ',
                'lsi_keywords' => '6 เมษายน, ราชวงศ์, เกียรติยศ, ความมั่นคง, ความเป็นผู้นำ, ประวัติศาสตร์, บารมี',
            ],
        ];

        return $map[$topicKey] ?? [
            'display_name' => $topicKey,
            'slug' => Str::slug($topicKey) ?: 'facebook-content-refresh',
            'title_suffix' => 'เลขมงคลและเคล็ดลับเสริมดวง',
            'keywords' => $topicKey . ', เลขมงคล, เสริมดวง',
            'lsi_keywords' => 'เลขนำโชค, พลังบวก, ความสำเร็จ, โชคลาภ, อ่านง่าย, อัปเดตล่าสุด, SEO',
        ];
    }

    private function buildArticleContentHtml(string $displayName, Carbon $latestDate, Carbon $earliestDate, FacebookImportedPost $latestImport, ?string $storedImagePath): string
    {
        $latestYear = $latestDate->copy()->timezone('Asia/Bangkok')->year + 543;
        $earliestYear = $earliestDate->copy()->timezone('Asia/Bangkok')->year + 543;
        $sourceText = trim((string) ($latestImport->message ?: $latestImport->story ?: ''));
        $keyPoints = $this->extractKeyPoints($sourceText, $displayName);

        $intro = sprintf(
            '%s ฉบับรีเฟรชนี้อัปเดตจากโพสต์ Facebook ล่าสุดของปี %d แต่ยังคงวันเผยแพร่แรกสุดของชุดคอนเทนต์ไว้ที่ปี %d เพื่อช่วยต่ออายุ SEO, รักษา URL เดิม และทำให้หน้าบทความมีความสดใหม่ต่อเนื่องในสายตาผู้อ่านและเสิร์ชเอนจิน',
            $displayName,
            $latestYear,
            $earliestYear
        );

        $closing = sprintf(
            'การรีเฟรชแบบนี้เหมาะกับ %s เพราะได้ทั้งความสดใหม่ของข้อมูลล่าสุดและอายุบทความเดิมที่สะสมสัญญาณ SEO เอาไว้แล้ว',
            $displayName
        );

        $contentBlocks = [
            sprintf('<h2>%s %d</h2>', e($displayName), $latestYear),
            '<p>' . e($intro) . '</p>',
            '<h3>ทำไมคอนเทนต์ชุดนี้จึงควรถูกรีเฟรช</h3>',
            '<p>' . e(sprintf(
                'เมื่อหัวข้อเดียวกันถูกพูดถึงหลายปี การนำเวอร์ชันล่าสุดมาแทนที่เนื้อหาเดิมช่วยให้บทความตอบโจทย์ผู้ค้นหาได้ดีกว่าเดิม โดยเฉพาะถ้าเป็นหัวข้อที่มีฤดูกาลชัดเจนอย่าง %s และยังช่วยให้หน้าเดิมไม่ต้องแยกหลาย URL จนทำให้สัญญาณการจัดอันดับกระจายออกไป',
                $displayName
            )) . '</p>',
            '<p>' . e('แนวทางนี้ช่วยให้ผู้อ่านเห็นข้อมูลที่ใหม่ที่สุดบนหน้าเดิม ขณะเดียวกันก็ยังรักษาวันเผยแพร่ครั้งแรกของเนื้อหาไว้เหมือนเดิม ซึ่งเป็นโครงสร้างที่เหมาะกับงาน content optimization มากกว่าการสร้างบทความใหม่ซ้ำหัวข้อเดิมทุกปี') . '</p>',
            '<h3>ไฮไลต์จากโพสต์ล่าสุด</h3>',
            '<p>' . e(sprintf(
                'โพสต์ล่าสุดที่ถูกใช้เป็นต้นฉบับสะท้อนบริบทของ %s ในปี %d และสามารถนำมาขยายเป็นบทความเชิงอธิบายได้ โดยเนื้อหาหลักยังคงเชื่อมกับอารมณ์ของวันสำคัญและคำค้นที่คนมักใช้ค้นหาในช่วงนั้น',
                $displayName,
                $latestYear
            )) . '</p>',
            $this->renderKeyPointsHtml($keyPoints),
            '<h3>ข้อความจากโพสต์ล่าสุด</h3>',
            $this->renderSourceTextHtml($sourceText),
            '<h3>แนวทางนำไปใช้กับหน้าเดิม</h3>',
            '<p>' . e('สำหรับทีมคอนเทนต์ สิ่งสำคัญคือการนำประเด็นจากโพสต์ล่าสุดมาเรียบเรียงให้เป็นเรื่องอ่านง่าย เพิ่มหัวข้อย่อย, bullet, และคำอธิบายที่ตอบ intent ของผู้ค้นหาให้ครบ โดยคง slug เดิมไว้เพื่อให้หน้าเดิมยังมี authority ต่อเนื่อง') . '</p>',
            '<ul>' .
                '<li>' . e(sprintf('ใช้ %s เป็นหัวข้อหลักของหน้า', $displayName)) . '</li>' .
                '<li>' . e('คง slug เดิมไว้ ไม่สร้าง URL ใหม่โดยไม่จำเป็น') . '</li>' .
                '<li>' . e('ใส่คำหลักที่เกี่ยวข้องลงใน H2/H3 อย่างเป็นธรรมชาติ') . '</li>' .
                '<li>' . e('เพิ่มสรุปสั้น ๆ เพื่อช่วยผู้อ่านตัดสินใจอ่านต่อ') . '</li>' .
                '<li>' . e('อัปเดตข้อมูลล่าสุดแต่คง published_at แรกสุดไว้') . '</li>' .
            '</ul>',
            '<h3>คำถามที่พบบ่อย</h3>',
            '<p><strong>Q:</strong> ทำไมไม่สร้างหน้าใหม่ทุกปี?<br><strong>A:</strong> เพราะหน้าเดิมมีโอกาสสะสมอันดับและลิงก์ภายในไว้แล้ว การรีเฟรชหน้าบทความเดิมมักคุ้มค่ากว่าในเชิง SEO</p>',
            '<p><strong>Q:</strong> ทำไมต้องคงวันเผยแพร่แรกสุดไว้?<br><strong>A:</strong> เพื่อรักษาอายุหน้าเดิมและสัญญาณความน่าเชื่อถือ ขณะเดียวกันก็ยังอัปเดตเนื้อหาให้ทันสมัยได้</p>',
            '<p><strong>Q:</strong> อะไรคือสิ่งที่ผู้อ่านได้ประโยชน์ที่สุด?<br><strong>A:</strong> ได้อ่านข้อมูลที่สดใหม่ขึ้น โดยไม่เสียบริบทของหน้าเดิมที่อ้างอิงมานานแล้ว</p>',
            '<p>' . e($closing) . '</p>',
        ];

        if ($storedImagePath !== null) {
            array_splice($contentBlocks, 2, 0, [
                '<figure><img src="' . e(asset('storage/' . ltrim($storedImagePath, '/'))) . '" alt="' . e($displayName) . '" loading="lazy" /><figcaption>' . e('ภาพจากโพสต์ต้นฉบับ Facebook') . '</figcaption></figure>',
            ]);
        }

        return implode("\n", $contentBlocks);
    }

    private function storePostImage(FacebookImportedPost $post, string $slug, Carbon $date): ?string
    {
        $sourceImageUrl = $this->resolveImageUrl($post);
        if ($sourceImageUrl === null) {
            return null;
        }

        try {
            $response = Http::timeout(20)->get($sourceImageUrl);
            if (! $response->successful()) {
                return null;
            }

            $contentType = (string) $response->header('Content-Type', '');
            $ext = 'jpg';
            if (str_contains($contentType, 'png')) {
                $ext = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $ext = 'webp';
            }

            $year = $date->copy()->timezone('Asia/Bangkok')->format('Y');
            $path = "articles/{$year}/{$slug}/facebook-cover-{$post->id}.{$ext}";
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveImageUrl(FacebookImportedPost $post): ?string
    {
        $imageUrl = trim((string) ($post->full_picture ?? ''));
        if ($imageUrl !== '') {
            return $imageUrl;
        }

        $attachments = is_array($post->attachments_json) ? $post->attachments_json : [];
        if (! isset($attachments['data']) || ! is_array($attachments['data'])) {
            return null;
        }

        foreach ($attachments['data'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $candidate = trim((string) data_get($item, 'media.image.src', ''));
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'media.source', ''));
            }
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'url', ''));
            }
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function extractKeyPoints(string $sourceText, string $displayName): array
    {
        $sourceText = trim(preg_replace('/\s+/u', ' ', strip_tags($sourceText)) ?: '');

        if ($sourceText === '') {
            return [
                sprintf('%s เป็นหัวข้อที่ดึงความสนใจได้ดีในช่วงวันสำคัญ', $displayName),
                'การอัปเดตคอนเทนต์ช่วยให้หน้าบทความดูสดใหม่และยังคงใช้ URL เดิม',
                'การคง published_at แรกสุดไว้ช่วยต่ออายุสัญญาณ SEO ของหน้าเดิม',
            ];
        }

        $chunks = preg_split('/(?<=[.!?。！？])\s+|(?<=\p{Thai})\s{2,}/u', $sourceText) ?: [];
        $points = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            $points[] = Str::limit($chunk, 120, '');
            if (count($points) >= 3) {
                break;
            }
        }

        if ($points === []) {
            $points[] = Str::limit($sourceText, 120, '');
        }

        return $points;
    }

    /**
     * @param array<int, string> $keyPoints
     */
    private function renderKeyPointsHtml(array $keyPoints): string
    {
        if ($keyPoints === []) {
            return '';
        }

        $items = '';
        foreach ($keyPoints as $point) {
            $items .= '<li>' . e($point) . '</li>';
        }

        return '<ul>' . $items . '</ul>';
    }

    private function renderSourceTextHtml(string $sourceText): string
    {
        $sourceText = trim($sourceText);
        if ($sourceText === '') {
            return '<p>ไม่มีข้อความต้นฉบับจาก Facebook ให้รีเฟรช</p>';
        }

        $lines = preg_split('/\R+/u', $sourceText) ?: [];
        $html = [];
        $buffer = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                if ($buffer !== []) {
                    $html[] = '<p>' . e(implode(' ', $buffer)) . '</p>';
                    $buffer = [];
                }
                continue;
            }

            if (preg_match('/^(?:[-*•]|[0-9]+[.)])\s+/u', $line) === 1) {
                if ($buffer !== []) {
                    $html[] = '<p>' . e(implode(' ', $buffer)) . '</p>';
                    $buffer = [];
                }

                $items = [];
                $items[] = preg_replace('/^(?:[-*•]|[0-9]+[.)])\s+/u', '', $line);
                $html[] = '<ul><li>' . e((string) $items[0]) . '</li></ul>';
                continue;
            }

            $buffer[] = $line;
        }

        if ($buffer !== []) {
            $html[] = '<p>' . e(implode(' ', $buffer)) . '</p>';
        }

        return implode("\n", $html);
    }

    private function buildExcerpt(FacebookImportedPost $latestImport, string $title): string
    {
        $sourceText = trim((string) ($latestImport->message ?: $latestImport->story ?: ''));
        $excerpt = trim(Str::limit(preg_replace('/\s+/u', ' ', strip_tags($sourceText)) ?: '', 180, ''));

        if ($excerpt === '') {
            $excerpt = trim(Str::limit($title, 180, ''));
        }

        return $excerpt;
    }

    private function buildMetaDescription(string $displayName, int $latestBeYear, string $excerpt): string
    {
        $text = trim($displayName . ' ' . $latestBeYear . ' ' . $excerpt);

        return trim(Str::limit(preg_replace('/\s+/u', ' ', $text) ?: '', 155, ''));
    }
}
