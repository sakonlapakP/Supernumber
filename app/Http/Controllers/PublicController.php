<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ContactMessage;
use App\Models\LotteryResult;
use App\Models\PhoneNumber;
use App\Models\PhoneNumberStatusLog;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\EstimateLead;
use App\Models\SalesDocument;
use App\Models\User;
use App\Services\ContactSpamFilter;
use App\Services\LineOrderNotifier;
use App\Services\TurnstileVerifier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicController extends Controller
{
    /**
     * Home page
     */
    public function index()
    {
        $homeNumbersLimitPerType = 48;

        $baseQuery = PhoneNumber::query()
            ->available()
            ->where('network_code', 'true_dtac');

        $prepaidNumbers = (clone $baseQuery)
            ->where('service_type', PhoneNumber::SERVICE_TYPE_PREPAID)
            ->inRandomOrder()
            ->limit($homeNumbersLimitPerType)
            ->get()
            ->values();

        $postpaidNumbers = (clone $baseQuery)
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->inRandomOrder()
            ->limit($homeNumbersLimitPerType)
            ->get()
            ->values();

        // Data for full filter form
        $search = '';
        $selectedPlan = '';
        $selectedServiceType = '';
        $buildPlanOptions = static fn (array $labels): array => collect($labels)
            ->map(static fn (string $label): array => [
                'value' => $label,
                'label' => $label,
            ])
            ->values()
            ->all();

        $plansByServiceType = [
            'all' => $buildPlanOptions(PhoneNumber::packageLabelsForQuery(
                PhoneNumber::query()
                    ->available()
                    ->where('network_code', 'true_dtac')
            )),
            PhoneNumber::SERVICE_TYPE_POSTPAID => $buildPlanOptions(PhoneNumber::packageLabelsForQuery(
                PhoneNumber::query()
                    ->available()
                    ->where('network_code', 'true_dtac')
                    ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            )),
            PhoneNumber::SERVICE_TYPE_PREPAID => PhoneNumber::prepaidPriceRangeOptions(),
        ];

        $plans = collect($plansByServiceType['all']);

        return view('index', compact('prepaidNumbers', 'postpaidNumbers', 'plans', 'plansByServiceType', 'search', 'selectedPlan', 'selectedServiceType'));
    }

    /**
     * Numbers catalog
     */
    public function numbers(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $searchDigits = preg_replace('/[^0-9]/', '', $search);
        $selectedPlan = trim((string) $request->input('plan', ''));
        $selectedServiceType = trim((string) $request->input('service_type', ''));
        $positionPattern = PhoneNumber::buildSearchPattern($request->query());

        if (! in_array($selectedServiceType, [PhoneNumber::SERVICE_TYPE_POSTPAID, PhoneNumber::SERVICE_TYPE_PREPAID], true)) {
            $selectedServiceType = '';
        }

        $baseQuery = PhoneNumber::query()
            ->available()
            ->where('network_code', 'true_dtac')
            ->when($selectedServiceType !== '', function ($query) use ($selectedServiceType) {
                $query->where('service_type', $selectedServiceType);
            });

        $buildPlanOptions = static fn (array $labels): array => collect($labels)
            ->map(static fn (string $label): array => [
                'value' => $label,
                'label' => $label,
            ])
            ->values()
            ->all();

        $planOptionsByServiceType = [
            'all' => $buildPlanOptions(PhoneNumber::packageLabelsForQuery(
                PhoneNumber::query()
                    ->available()
                    ->where('network_code', 'true_dtac')
            )),
            PhoneNumber::SERVICE_TYPE_POSTPAID => $buildPlanOptions(PhoneNumber::packageLabelsForQuery(
                PhoneNumber::query()
                    ->available()
                    ->where('network_code', 'true_dtac')
                    ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            )),
            PhoneNumber::SERVICE_TYPE_PREPAID => PhoneNumber::prepaidPriceRangeOptions(),
        ];

        $planOptionKey = $selectedServiceType !== '' ? $selectedServiceType : 'all';
        $plans = collect($planOptionsByServiceType[$planOptionKey] ?? []);
        $allowedPlanValues = $plans->pluck('value')->all();

        if ($selectedPlan !== '' && ! in_array($selectedPlan, $allowedPlanValues, true)) {
            $selectedPlan = '';
        }

        $selectedPrepaidPriceRange = $selectedServiceType === PhoneNumber::SERVICE_TYPE_PREPAID
            ? PhoneNumber::resolvePrepaidPriceRange($selectedPlan)
            : null;
        [$selectedPlanName, $selectedPlanPrice] = $selectedServiceType === PhoneNumber::SERVICE_TYPE_PREPAID
            ? [null, null]
            : PhoneNumber::parsePackageLabel($selectedPlan);

        $applyCatalogFilters = static function ($query) use ($searchDigits, $positionPattern, $selectedPlan, $selectedServiceType, $selectedPrepaidPriceRange, $selectedPlanName, $selectedPlanPrice) {
            return $query
                ->when($searchDigits !== '', function ($builder) use ($searchDigits) {
                    $builder->where('phone_number', 'like', '%' . $searchDigits . '%');
                })
                ->matchingPattern($positionPattern)
                ->when($selectedPlan !== '', function ($builder) use ($selectedServiceType, $selectedPrepaidPriceRange, $selectedPlanName, $selectedPlanPrice) {
                    if ($selectedServiceType === PhoneNumber::SERVICE_TYPE_PREPAID && $selectedPrepaidPriceRange !== null) {
                        $min = $selectedPrepaidPriceRange['min'];
                        $max = $selectedPrepaidPriceRange['max'];

                        if ($min === null && $max !== null) {
                            $builder->where('sale_price', '<', $max);
                            return;
                        }

                        if ($min !== null && $max === null) {
                            $builder->where('sale_price', '>', $min);
                            return;
                        }

                        if ($min !== null && $max !== null) {
                            $builder->where('sale_price', '>=', $min)
                                ->where('sale_price', '<', $max);
                        }

                        return;
                    }

                    if ($selectedPlanName !== null) {
                        $builder->where('plan_name', $selectedPlanName);
                    }

                    if ($selectedPlanPrice !== null) {
                        $builder->where('sale_price', $selectedPlanPrice);
                    }
                })
                ->orderBy('sale_price')
                ->orderBy('phone_number');
        };

        $isDefaultSplitLayout = $searchDigits === ''
            && $selectedPlan === ''
            && $selectedServiceType === ''
            && $positionPattern === null;

        $defaultPrepaidNumbers = collect();
        $defaultPostpaidNumbers = collect();

        if ($isDefaultSplitLayout) {
            $perPage = 24;
            $perColumn = (int) ceil($perPage / 2);
            $currentPage = Paginator::resolveCurrentPage('page');
            $columnOffset = max(0, ($currentPage - 1) * $perColumn);

            $prepaidQuery = $applyCatalogFilters(
                (clone $baseQuery)->where('service_type', PhoneNumber::SERVICE_TYPE_PREPAID)
            );
            $postpaidQuery = $applyCatalogFilters(
                (clone $baseQuery)->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            );

            $prepaidTotal = (clone $prepaidQuery)->count();
            $postpaidTotal = (clone $postpaidQuery)->count();

            $prepaidNumbers = (clone $prepaidQuery)
                ->offset($columnOffset)
                ->limit($perColumn)
                ->get();

            $postpaidNumbers = (clone $postpaidQuery)
                ->offset($columnOffset)
                ->limit($perColumn)
                ->get();

            $defaultPrepaidNumbers = $prepaidNumbers->values();
            $defaultPostpaidNumbers = $postpaidNumbers->values();

            $overflowNumbers = collect();
            $remainingSlots = $perPage - ($prepaidNumbers->count() + $postpaidNumbers->count());

            if ($remainingSlots > 0) {
                if ($prepaidNumbers->count() < $perColumn) {
                    $overflowNumbers = $overflowNumbers->concat(
                        (clone $postpaidQuery)
                            ->offset($columnOffset + $postpaidNumbers->count())
                            ->limit($remainingSlots)
                            ->get()
                    );
                } elseif ($postpaidNumbers->count() < $perColumn) {
                    $overflowNumbers = $overflowNumbers->concat(
                        (clone $prepaidQuery)
                            ->offset($columnOffset + $prepaidNumbers->count())
                            ->limit($remainingSlots)
                            ->get()
                    );
                }
            }

            $rows = max($prepaidNumbers->count(), $postpaidNumbers->count());
            $interleavedNumbers = collect();

            for ($index = 0; $index < $rows; $index += 1) {
                if ($prepaidNumbers->has($index)) {
                    $interleavedNumbers->push($prepaidNumbers->get($index));
                }

                if ($postpaidNumbers->has($index)) {
                    $interleavedNumbers->push($postpaidNumbers->get($index));
                }
            }

            $numbers = new LengthAwarePaginator(
                $interleavedNumbers->concat($overflowNumbers)->values(),
                $prepaidTotal + $postpaidTotal,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $numbers = $applyCatalogFilters(clone $baseQuery)
                ->paginate(24)
                ->withQueryString();
        }

        return view('numbers', compact('numbers', 'plans', 'planOptionsByServiceType', 'search', 'selectedPlan', 'selectedServiceType', 'positionPattern', 'isDefaultSplitLayout', 'defaultPrepaidNumbers', 'defaultPostpaidNumbers'));
    }

    /**
     * Articles index
     */
    public function articles()
    {
        $articles = Article::query()
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->paginate(9);

        return view('articles.index', compact('articles'));
    }

    /**
     * Show single article
     */
    public function showArticle(string $slug)
    {
        // EMERGENCY CACHE CLEAR BYPASS
        if (request()->query('force_clear') == '1') {
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            return "Cache Cleared! Now try the git-sync link.";
        }

        $article = Article::query()
            ->published()
            ->with([
                'approvedComments' => fn ($query) => $query->latest('id'),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $article->increment('view_count');

        $lotteryResult = $this->resolveLotteryResultForArticle($article);
        
        // Simulation mode for admin preview
        if (request()->query('simulate_lottery') == '1') {
            $lotteryResult = \App\Models\LotteryResult::query()
                ->with('prizes')
                ->orderByRaw('COALESCE(source_draw_date, draw_date) DESC')
                ->orderByDesc('fetched_at')
                ->orderByDesc('id')
                ->first();
        }

        return view('articles.show', compact('article', 'lotteryResult'));
    }

    /**
     * Store article comment
     */
    public function storeArticleComment(Request $request, string $slug)
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $request->validate([
            'commenter_name' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        ArticleComment::query()->create([
            'article_id' => $article->id,
            'commenter_name' => trim($data['commenter_name']),
            'content' => trim($data['content']),
            'status' => ArticleComment::STATUS_PENDING,
        ]);

        return redirect()
            ->route('articles.show', $article->slug)
            ->with('comment_status_message', 'ส่งคอมเมนต์แล้ว รอแอดมินอนุมัติก่อนแสดงบนหน้าเว็บ');
    }

    /**
     * Number evaluation
     */
    public function evaluate(Request $request)
    {
        [$redirect, $phone] = $this->resolveAnalysisPhone($request);

        if ($redirect !== null) {
            return $redirect;
        }

        return view('evaluate', compact('phone'));
    }

    /**
     * Bad number evaluation
     */
    public function evaluateBad(Request $request)
    {
        [$redirect, $phone] = $this->resolveAnalysisPhone($request);

        if ($redirect !== null) {
            return $redirect;
        }

        return view('evaluate-bad-number', compact('phone'));
    }

    /**
     * Tiers info
     */
    public function tiers()
    {
        return view('tiers');
    }

    /**
     * Contact page
     */
    public function contact()
    {
        return view('contact');
    }

    /**
     * Store contact message
     */
    public function storeContact(Request $request, TurnstileVerifier $turnstileVerifier, ContactSpamFilter $spamFilter)
    {
        if (trim((string) $request->input('website', '')) !== '') {
            Log::info('Blocked contact form submission via honeypot.', [
                'ip_address' => $request->ip(),
            ]);

            return redirect()
                ->route('contact')
                ->with('contact_status_message', 'ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับตามข้อมูลที่แจ้งไว้');
        }

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'message' => ['required', 'string', 'max:2000'],
        ];

        $token = (string) $request->input('cf-turnstile-response', '');
        if ($token === '') {
            $rules['cf-turnstile-response'] = ['required'];
        }

        $data = $request->validate($rules);

        if ($token !== '') {
            $isVerified = $turnstileVerifier->verify($token, $request->ip());

            if (! $isVerified) {
                return back()
                    ->withInput()
                    ->withErrors(['cf-turnstile-response' => 'การยืนยันตัวตนไม่สำเร็จ กรุณาลองใหม่อีกครั้ง']);
            }
        }

        $spamResult = $spamFilter->inspect(
            $data['name'],
            $data['phone'],
            $data['message']
        );

        if ($spamResult['blocked']) {
            return redirect()
                ->route('contact')
                ->with('contact_status_message', 'ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับตามข้อมูลที่แจ้งไว้');
        }

        ContactMessage::query()->create([
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'message' => trim($data['message']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now(),
        ]);

        app(LineOrderNotifier::class)->notifyContactMessage(
            trim($data['name']),
            trim($data['phone']),
            trim($data['message'])
        );

        return redirect()
            ->route('contact')
            ->with('contact_status_message', 'ส่งข้อความเรียบร้อยแล้ว ทีมงานจะติดต่อกลับตามข้อมูลที่แจ้งไว้');
    }

    /**
     * Privacy policy page
     */
    public function privacy()
    {
        return view('privacy');
    }

    /**
     * Helper to resolve lottery result
     */
    protected function resolveLotteryResultForArticle(Article $article): ?LotteryResult
    {
        $slug = Str::lower(trim((string) $article->slug));
        $latestResultQuery = LotteryResult::query()
            ->with('prizes')
            ->orderByRaw('COALESCE(source_draw_date, draw_date) DESC')
            ->orderByDesc('fetched_at')
            ->orderByDesc('id');

        if ($slug === 'thai-government-lottery-latest-results') {
            return (clone $latestResultQuery)->first();
        }

        if (preg_match('/^thai-govern?ment-lottery-(\d{4})(\d{2})(first|second)$/', $slug, $matches) !== 1) {
            return null;
        }

        $year = (int) ($matches[1] ?? 0);
        $month = (int) ($matches[2] ?? 0);
        $round = (string) ($matches[3] ?? '');

        return (clone $latestResultQuery)
            ->whereYear('draw_date', $year)
            ->whereMonth('draw_date', $month)
            ->get()
            ->first(function (LotteryResult $result) use ($round): bool {
                $drawDate = $result->source_draw_date ?? $result->draw_date;

                if ($drawDate === null) {
                    return false;
                }

                return $round === 'first'
                    ? (int) $drawDate->format('j') <= 15
                    : (int) $drawDate->format('j') > 15;
            });
    }

    /**
     * Generate XML Sitemap
     */
    public function sitemap()
    {
        $urls = [];

        // Main pages
        $urls[] = ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['url' => route('numbers.index'), 'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['url' => route('numbers.index', ['service_type' => 'prepaid']), 'priority' => '0.85', 'changefreq' => 'daily'];
        $urls[] = ['url' => route('numbers.index', ['service_type' => 'postpaid']), 'priority' => '0.85', 'changefreq' => 'daily'];
        $urls[] = ['url' => route('articles.index'), 'priority' => '0.8', 'changefreq' => 'weekly'];
        $urls[] = ['url' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'];
        $urls[] = ['url' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'monthly'];

        // Articles
        $articles = Article::query()->published()->get();
        foreach ($articles as $article) {
            $urls[] = [
                'url' => route('articles.show', $article->slug),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $article->updated_at->toAtomString(),
            ];
        }

        $xml = view('sitemap', compact('urls'))->render();

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Helper to resolve analysis phone
     */
    protected function resolveAnalysisPhone(Request $request): array
    {
        $phone = preg_replace('/\D+/', '', (string) $request->query('phone', '')) ?? '';

        if (strlen($phone) !== PhoneNumber::PHONE_NUMBER_LENGTH) {
            return [
                redirect()
                    ->route('home')
                    ->withInput(['phone' => $phone])
                    ->withErrors(['phone' => 'กรุณากรอกเบอร์มือถือให้ครบ 10 หลัก']),
                null,
            ];
        }

        return [null, $phone];
    }
}
