<?php

use App\Models\PhoneNumberStatusLog;
use App\Models\PhoneNumber;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\CustomerOrder;
use App\Models\EstimateLead;
use App\Models\LotteryResult;
use App\Models\LineWebhookEvent;
use App\Models\User;
use App\Services\EnvironmentEditor;
use App\Services\AdminLogViewer;
use App\Services\LineEstimateLeadNotifier;
use App\Services\LineOrderNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;

$currentAdmin = function (): ?User {
    $userId = session('admin_user_id');

    if (! is_numeric($userId)) {
        return null;
    }

    $user = User::find((int) $userId);

    if (! $user || ! $user->canAccessAdminPanel()) {
        return null;
    }

    return $user;
};

$ensureAdmin = function (?string $requiredRole = null) use ($currentAdmin) {
    $user = $currentAdmin();

    if (! $user || !session('admin_authenticated')) {
        return redirect()->route('admin.login');
    }

    if ($requiredRole !== null) {
        $hasRequiredRole = $user->role === $requiredRole
            || ($requiredRole === User::ROLE_MANAGER && $user->role === User::ROLE_ADMIN);

        if (! $hasRequiredRole) {
            abort(403);
        }
    }

    return null;
};

$rejectAdminLogin = function (Request $request) {
    return back()
        ->withInput($request->except('password'))
        ->withErrors(['username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
};

$safelyRunLineNotification = function (callable $callback, string $message, array $context = []): bool {
    try {
        $callback();

        return true;
    } catch (\Throwable $e) {
        Log::warning($message, $context + [
            'error' => $e->getMessage(),
        ]);

        return false;
    }
};

$storeOrderPaymentSlip = function (CustomerOrder $order, UploadedFile $file): string {
    $timestamp = $order->created_at?->copy()?->timezone('Asia/Bangkok') ?? now('Asia/Bangkok');
    $directory = $timestamp->format('Ym');
    $disk = Storage::disk('public');
    $contents = (string) file_get_contents($file->getRealPath());

    if ($contents === '') {
        throw new RuntimeException('Unable to read uploaded payment slip.');
    }

    $image = null;

    if (class_exists(\Imagick::class)) {
        try {
            $imagick = new \Imagick();
            $imagick->readImageBlob($contents);
            $imagick->setImageFormat('png');
            $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $image = $imagick->getImagesBlob();
            $imagick->clear();
            $imagick->destroy();
        } catch (\Throwable) {
            $image = null;
        }
    }

    if ($image === null && function_exists('imagecreatefromstring') && function_exists('imagepng')) {
        $gdImage = @imagecreatefromstring($contents);

        if ($gdImage !== false) {
            ob_start();
            imagepng($gdImage);
            $image = (string) ob_get_clean();
            imagedestroy($gdImage);
        }
    }

    $originalExtension = strtolower(trim((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin')));
    $originalExtension = match ($originalExtension) {
        'jpeg' => 'jpg',
        default => $originalExtension !== '' ? $originalExtension : 'bin',
    };

    $path = $directory . '/' . $order->id . '.' . ($image !== null ? 'png' : $originalExtension);

    $previousPath = trim((string) $order->payment_slip_path);
    if ($previousPath !== '' && $previousPath !== $path && $disk->exists($previousPath)) {
        $disk->delete($previousPath);
    }

    $disk->put($path, $image ?? $contents);

    return $path;
};

$guessFileMimeType = function (string $path): ?string {
    if (! is_file($path) || ! is_readable($path)) {
        return null;
    }

    $mimeType = null;

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo !== false) {
            $detected = finfo_file($finfo, $path);
            finfo_close($finfo);

            if (is_string($detected) && $detected !== '') {
                $mimeType = $detected;
            }
        }
    }

    return $mimeType ?: File::mimeType($path);
};

$resolveOrderPaymentSlip = function (CustomerOrder $order) use ($guessFileMimeType): array {
    $storedPath = trim((string) $order->payment_slip_path);

    if ($storedPath === '') {
        return [
            'stored_path' => '',
            'relative_path' => null,
            'absolute_path' => null,
            'external_url' => null,
            'exists' => false,
            'mime_type' => null,
            'is_image' => false,
            'is_pdf' => false,
        ];
    }

    if (Str::startsWith($storedPath, ['http://', 'https://'])) {
        $extension = strtolower(pathinfo(parse_url($storedPath, PHP_URL_PATH) ?: $storedPath, PATHINFO_EXTENSION));

        return [
            'stored_path' => $storedPath,
            'relative_path' => null,
            'absolute_path' => null,
            'external_url' => $storedPath,
            'exists' => true,
            'mime_type' => null,
            'is_image' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'], true),
            'is_pdf' => $extension === 'pdf',
        ];
    }

    $normalizedPath = ltrim($storedPath, '/');
    $relativeCandidates = array_values(array_unique(array_filter([
        $normalizedPath,
        Str::startsWith($normalizedPath, 'storage/') ? Str::after($normalizedPath, 'storage/') : null,
        Str::startsWith($normalizedPath, 'public/') ? Str::after($normalizedPath, 'public/') : null,
        ! Str::startsWith($normalizedPath, 'payment-slips/') ? 'payment-slips/' . $normalizedPath : null,
    ])));

    foreach ($relativeCandidates as $candidate) {
        if (! Storage::disk('public')->exists($candidate)) {
            continue;
        }

        $absolutePath = Storage::disk('public')->path($candidate);
        $mimeType = $guessFileMimeType($absolutePath);

        return [
            'stored_path' => $storedPath,
            'relative_path' => $candidate,
            'absolute_path' => $absolutePath,
            'external_url' => null,
            'exists' => true,
            'mime_type' => $mimeType,
            'is_image' => is_string($mimeType) && str_starts_with($mimeType, 'image/'),
            'is_pdf' => $mimeType === 'application/pdf',
        ];
    }

    return [
        'stored_path' => $storedPath,
        'relative_path' => null,
        'absolute_path' => null,
        'external_url' => null,
        'exists' => false,
        'mime_type' => null,
        'is_image' => false,
        'is_pdf' => false,
    ];
};

$resolveEnvironmentEditor = function () {
    if (class_exists(EnvironmentEditor::class)) {
        return app(EnvironmentEditor::class);
    }

    return new class
    {
        /**
         * @param  array<int, string>  $keys
         * @return array<string, string>
         */
        public function getMany(array $keys): array
        {
            $values = $this->parseFile();
            $result = [];

            foreach ($keys as $key) {
                $result[$key] = (string) ($values[$key] ?? '');
            }

            return $result;
        }

        /**
         * @param  array<string, string>  $values
         */
        public function setMany(array $values): void
        {
            $path = $this->resolvePath();
            $contents = file_exists($path) ? (string) file_get_contents($path) : '';

            if ($contents === '' && ! file_exists($path)) {
                throw new RuntimeException('.env file was not found.');
            }

            foreach ($values as $key => $value) {
                $line = $key . '=' . $this->formatValue($value);
                $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

                if (preg_match($pattern, $contents) === 1) {
                    $contents = (string) preg_replace($pattern, $line, $contents, 1);
                    continue;
                }

                $contents = rtrim($contents, "\r\n") . PHP_EOL . $line . PHP_EOL;
            }

            if (file_put_contents($path, $contents) === false) {
                throw new RuntimeException('Unable to write updated values to .env.');
            }
        }

        /**
         * @return array<string, string>
         */
        private function parseFile(): array
        {
            $path = $this->resolvePath();

            if (! file_exists($path)) {
                return [];
            }

            $lines = file($path, FILE_IGNORE_NEW_LINES);

            if ($lines === false) {
                return [];
            }

            $values = [];

            foreach ($lines as $line) {
                $trimmed = trim($line);

                if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($line, '=')) {
                    continue;
                }

                [$key, $rawValue] = explode('=', $line, 2);
                $values[trim($key)] = $this->parseValue($rawValue);
            }

            return $values;
        }

        private function parseValue(string $value): string
        {
            $value = trim($value);

            if ($value === '') {
                return '';
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $quote = $value[0];
                $inner = substr($value, 1, -1);

                if ($quote === '"') {
                    return stripcslashes($inner);
                }

                return str_replace(["\\'", '\\\\'], ["'", '\\'], $inner);
            }

            return $value;
        }

        private function formatValue(string $value): string
        {
            if ($value === '') {
                return '';
            }

            if (preg_match('/\s|#|=|["\']/', $value) === 1) {
                return '"' . addcslashes($value, "\\\"") . '"';
            }

            return $value;
        }

        private function resolvePath(): string
        {
            return base_path('.env');
        }
    };
};

$resolveAdminLogViewer = function () {
    return app(AdminLogViewer::class);
};

$storeLineWebhookEvent = function (array $attributes): void {
    if (! Schema::hasTable('line_webhook_events')) {
        return;
    }

    if (class_exists(LineWebhookEvent::class)) {
        LineWebhookEvent::query()->create($attributes);
        return;
    }

    DB::table('line_webhook_events')->insert([
        'event_type' => $attributes['event_type'] ?? null,
        'source_type' => $attributes['source_type'] ?? null,
        'group_id' => $attributes['group_id'] ?? null,
        'room_id' => $attributes['room_id'] ?? null,
        'user_id' => $attributes['user_id'] ?? null,
        'message_type' => $attributes['message_type'] ?? null,
        'destination' => $attributes['destination'] ?? null,
        'signature_valid' => $attributes['signature_valid'] ?? null,
        'headers' => isset($attributes['headers']) ? json_encode($attributes['headers'], JSON_UNESCAPED_UNICODE) : null,
        'payload' => isset($attributes['payload']) ? json_encode($attributes['payload'], JSON_UNESCAPED_UNICODE) : null,
        'raw_body' => $attributes['raw_body'] ?? null,
        'received_at' => $attributes['received_at'] ?? now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
};

$latestLineWebhookEvents = function (int $limit = 20) {
    if (! Schema::hasTable('line_webhook_events')) {
        return collect();
    }

    if (class_exists(LineWebhookEvent::class)) {
        return LineWebhookEvent::query()
            ->latest('received_at')
            ->limit($limit)
            ->get();
    }

    return DB::table('line_webhook_events')
        ->orderByDesc('received_at')
        ->limit($limit)
        ->get();
};

$buildArticleSlug = function (string $title, ?int $ignoreId = null): string {
    $base = Str::slug(trim($title));
    $base = $base !== '' ? $base : 'article';
    $slug = $base;
    $suffix = 2;

    while (
        Article::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
    ) {
        $slug = $base . '-' . $suffix;
        $suffix++;
    }

    return $slug;
};

$resolveArticleImageMeta = function (string $slug, ?Carbon $date = null): array {
    $resolvedDate = ($date?->copy() ?? Carbon::now('Asia/Bangkok'))->timezone('Asia/Bangkok');
    $year = $resolvedDate->format('Y');
    $month = $resolvedDate->format('m');
    $day = (int) $resolvedDate->format('j');
    $normalizedSlug = Str::lower(trim($slug));

    if (str_contains($normalizedSlug, 'lottery')) {
        $articleName = sprintf(
            'thai-goverment-lottery-%s%s%s',
            $year,
            $month,
            $day === 1 ? 'first' : 'second'
        );
    } else {
        $articleName = $normalizedSlug !== '' ? $normalizedSlug : 'article';
    }

    $directory = sprintf('article/%s/%s', $year, $articleName);

    return [
        'directory' => $directory,
        'article_name' => $articleName,
        'square_path' => sprintf('%s/%s.png', $directory, $articleName),
        'cover_path' => sprintf('%s/%s_cover.png', $directory, $articleName),
    ];
};

$resolveAnalysisPhone = function (Request $request) {
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
};

$normalizeServiceType = function (?string $serviceType): string {
    return trim(strtolower((string) $serviceType)) === PhoneNumber::SERVICE_TYPE_PREPAID
        ? PhoneNumber::SERVICE_TYPE_PREPAID
        : PhoneNumber::SERVICE_TYPE_POSTPAID;
};

$defaultOrderStatus = function (string $serviceType): string {
    return $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
        ? 'pending_review'
        : 'submitted';
};

$syncPhoneNumberStatusFromOrder = function (CustomerOrder $order, ?int $userId = null) use ($normalizeServiceType) {
    if ($normalizeServiceType($order->service_type) !== PhoneNumber::SERVICE_TYPE_PREPAID) {
        return;
    }

    $orderedNumber = preg_replace('/\D+/', '', (string) $order->ordered_number) ?? '';
    if ($orderedNumber === '') {
        return;
    }

    $phoneNumber = PhoneNumber::query()
        ->where('phone_number', $orderedNumber)
        ->first();

    if (! $phoneNumber) {
        return;
    }

    $normalizedStatus = Str::of((string) $order->status)->trim()->lower()->toString();

    $targetStatus = match ($normalizedStatus) {
        'sold', 'completed' => PhoneNumber::STATUS_SOLD,
        'rejected', 'cancelled' => PhoneNumber::STATUS_ACTIVE,
        default => PhoneNumber::STATUS_HOLD,
    };

    if ($phoneNumber->status === $targetStatus) {
        return;
    }

    $action = match ($targetStatus) {
        PhoneNumber::STATUS_SOLD => 'sell',
        PhoneNumber::STATUS_ACTIVE => 'release',
        default => 'hold',
    };

    $fromStatus = $phoneNumber->status;

    $phoneNumber->update([
        'status' => $targetStatus,
    ]);

    PhoneNumberStatusLog::query()->create([
        'phone_number_id' => $phoneNumber->id,
        'user_id' => $userId,
        'action' => $action,
        'from_status' => $fromStatus,
        'to_status' => $targetStatus,
    ]);
};

Route::redirect('/', '/under-construction')->name('home');
Route::view('/under-construction', 'under-construction')->name('under-construction');
Route::redirect('/underconsturter', '/under-construction');

Route::get('/numbers', function () {
    $search = trim((string) request('q', ''));
    $searchDigits = preg_replace('/[^0-9]/', '', $search);
    $selectedPlan = trim((string) request('plan', ''));
    $selectedServiceType = trim((string) request('service_type', ''));
    $positionPattern = PhoneNumber::buildSearchPattern(request()->query());

    if (! in_array($selectedServiceType, [PhoneNumber::SERVICE_TYPE_POSTPAID, PhoneNumber::SERVICE_TYPE_PREPAID], true)) {
        $selectedServiceType = '';
    }

    $baseQuery = PhoneNumber::query()
        ->available()
        ->where('network_code', 'true_dtac')
        ->when($selectedServiceType !== '', function ($query) use ($selectedServiceType) {
            $query->where('service_type', $selectedServiceType);
        });

    $plans = collect(PhoneNumber::packageLabelsForQuery($baseQuery));

    [$selectedPlanName, $selectedPlanPrice] = PhoneNumber::parsePackageLabel($selectedPlan);

    $numbers = (clone $baseQuery)
        ->when($searchDigits !== '', function ($query) use ($searchDigits) {
            $query->where('phone_number', 'like', '%' . $searchDigits . '%');
        })
        ->matchingPattern($positionPattern)
        ->when($selectedPlan !== '', function ($query) use ($selectedPlanName, $selectedPlanPrice) {
            if ($selectedPlanName !== null) {
                $query->where('plan_name', $selectedPlanName);
            }

            if ($selectedPlanPrice !== null) {
                $query->where('sale_price', $selectedPlanPrice);
            }
        })
        ->orderBy('sale_price')
        ->orderBy('phone_number')
        ->paginate(24)
        ->withQueryString();

    return view('numbers', compact('numbers', 'plans', 'search', 'selectedPlan', 'selectedServiceType', 'positionPattern'));
})->name('numbers.index');

Route::get('/articles', function () {
    $articles = Article::query()
        ->published()
        ->latest('published_at')
        ->latest('id')
        ->paginate(9);

    return view('articles.index', compact('articles'));
})->name('articles.index');

Route::get('/articles/{slug}', function (string $slug) {
    $article = Article::query()
        ->published()
        ->with([
            'approvedComments' => fn ($query) => $query->latest('id'),
        ])
        ->where('slug', $slug)
        ->firstOrFail();

    $lotteryResult = null;
    if (Schema::hasTable('lottery_results') && Schema::hasTable('lottery_result_prizes')) {
        $lotteryResult = LotteryResult::query()
            ->with('prizes')
            ->orderByRaw('COALESCE(source_draw_date, draw_date) DESC')
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->first();
    }

    return view('articles.show', compact('article', 'lotteryResult'));
})->name('articles.show');

Route::post('/articles/{slug}/comments', function (Request $request, string $slug) {
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
})->name('articles.comments.store');

Route::get('/evaluate', function (Request $request) use ($resolveAnalysisPhone) {
    [$redirect, $phone] = $resolveAnalysisPhone($request);

    if ($redirect !== null) {
        return $redirect;
    }

    return view('evaluate', compact('phone'));
})->name('evaluate');

Route::get('/evaluateBadNumber', function (Request $request) use ($resolveAnalysisPhone) {
    [$redirect, $phone] = $resolveAnalysisPhone($request);

    if ($redirect !== null) {
        return $redirect;
    }

    return view('evaluate-bad-number', compact('phone'));
})->name('evaluate.bad');

Route::get('/tiers', function () {
    return view('tiers');
})->name('tiers');

Route::get('/estimate', function () {
    return view('estimate');
})->name('estimate');

Route::post('/estimate', function (Request $request) use ($safelyRunLineNotification) {
    $data = $request->validate([
        'first_name' => ['required', 'string', 'max:120'],
        'last_name' => ['required', 'string', 'max:120'],
        'gender' => ['nullable', Rule::in(['male', 'female'])],
        'birthday' => ['nullable', 'date'],
        'work_type' => ['nullable', Rule::in(['sales', 'service', 'office', 'online'])],
        'current_phone' => ['nullable', 'string', 'max:20'],
        'main_phone' => ['required', 'string', 'max:20'],
        'email' => ['required', 'email', 'max:255'],
        'goal' => ['nullable', Rule::in(['work', 'money', 'love', 'balance'])],
    ]);

    $digitsOnly = static fn (?string $value): string => preg_replace('/\D+/', '', (string) $value) ?? '';
    $currentPhone = $digitsOnly($data['current_phone'] ?? null);
    $mainPhone = $digitsOnly($data['main_phone'] ?? null);

    if ($mainPhone === '' || strlen($mainPhone) !== PhoneNumber::PHONE_NUMBER_LENGTH) {
        return redirect()
            ->route('estimate')
            ->withInput($request->except('_token'))
            ->withErrors(['main_phone' => 'กรุณากรอกเบอร์ที่ใช้งานมากที่สุดให้ครบ 10 หลัก']);
    }

    $lead = EstimateLead::query()->create([
        'first_name' => trim((string) $data['first_name']),
        'last_name' => trim((string) $data['last_name']),
        'gender' => $data['gender'] ?? null,
        'birthday' => $data['birthday'] ?? null,
        'work_type' => $data['work_type'] ?? null,
        'current_phone' => $currentPhone !== '' ? $currentPhone : null,
        'main_phone' => $mainPhone,
        'email' => trim((string) $data['email']),
        'goal' => $data['goal'] ?? null,
        'ip_address' => $request->ip(),
        'user_agent' => substr((string) $request->userAgent(), 0, 65535),
        'submitted_at' => Carbon::now(),
    ]);

    $safelyRunLineNotification(
        fn () => app(LineEstimateLeadNotifier::class)->sendSubmitted($lead),
        'Estimate lead LINE notification failed.',
        ['estimate_lead_id' => $lead->id]
    );

    return redirect()
        ->route('estimate')
        ->with('estimate_status_message', 'บันทึกข้อมูลเรียบร้อยแล้ว ทีมงานจะใช้ข้อมูลนี้เพื่อแนะนำเบอร์ที่เหมาะกับคุณ');
})->name('estimate.store');

Route::redirect('/good-number', '/numbers');

Route::get('/book', function () {
    $orderedNumber = preg_replace('/\D+/', '', (string) request('number', '')) ?? '';
    if ($orderedNumber === '') {
        abort(404);
    }

    $currentNumber = PhoneNumber::query()
        ->where('phone_number', $orderedNumber)
        ->firstOrFail();

    if (! in_array($currentNumber->status, [PhoneNumber::STATUS_ACTIVE, PhoneNumber::STATUS_HOLD], true)) {
        abort(404);
    }

    $packagePlanName = trim((string) $currentNumber->plan_name) ?: PhoneNumber::PACKAGE_NAME;
    $serviceType = $currentNumber->service_type;

    return view('book', compact('currentNumber', 'packagePlanName', 'serviceType'));
})->name('book');

Route::post('/book/save-step2', function (Request $request) use ($defaultOrderStatus, $normalizeServiceType, $storeOrderPaymentSlip, $safelyRunLineNotification, $syncPhoneNumberStatusFromOrder) {
    $data = $request->validate([
        'saved_order_id' => ['nullable', 'integer'],
        'ordered_number' => ['required', 'string', 'max:20'],
        'selected_package' => ['required', 'integer', 'min:1'],
        'title_prefix' => ['nullable', 'string', 'max:50'],
        'first_name' => ['nullable', 'string', 'max:120'],
        'last_name' => ['nullable', 'string', 'max:120'],
        'email' => ['nullable', 'email', 'max:255'],
        'current_phone' => ['nullable', 'string', 'max:20'],
        'shipping_address_line' => ['nullable', 'string', 'max:255'],
        'district' => ['nullable', 'string', 'max:120'],
        'amphoe' => ['nullable', 'string', 'max:120'],
        'province' => ['nullable', 'string', 'max:120'],
        'zipcode' => ['nullable', 'string', 'max:10'],
        'appointment_date' => ['nullable', 'date'],
        'appointment_time_slot' => ['nullable', 'string', 'max:40'],
        'payment_slip' => ['required', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240'],
    ]);

    $digitsOnly = static fn (?string $value): string => preg_replace('/\D+/', '', (string) $value) ?? '';
    $orderedNumber = $digitsOnly($data['ordered_number']);
    $currentPhone = $digitsOnly($data['current_phone'] ?? null);
    $zipcode = $digitsOnly($data['zipcode'] ?? null);

    $orderId = isset($data['saved_order_id']) ? (int) $data['saved_order_id'] : 0;
    $order = $orderId > 0 ? CustomerOrder::query()->find($orderId) : null;
    if (! $order) {
        $order = new CustomerOrder();
    }
    $isNewOrder = ! $order->exists;

    $currentNumber = PhoneNumber::query()
        ->where('phone_number', $orderedNumber)
        ->first();

    if (! $currentNumber) {
        throw ValidationException::withMessages([
            'ordered_number' => 'ไม่พบเบอร์ในระบบ',
        ]);
    }

    $serviceType = $normalizeServiceType($currentNumber->service_type);
    $isSameSavedOrder = $order->exists
        && $orderedNumber !== ''
        && $orderedNumber === (preg_replace('/\D+/', '', (string) $order->ordered_number) ?? '');

    if (! $isSameSavedOrder && $currentNumber->status !== PhoneNumber::STATUS_ACTIVE) {
        throw ValidationException::withMessages([
            'ordered_number' => 'เบอร์นี้ไม่พร้อมสั่งซื้อแล้ว',
        ]);
    }

    $selectedPackage = $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
        ? (int) ($currentNumber->sale_price ?? 0)
        : (int) $data['selected_package'];

    if ($selectedPackage < 1) {
        throw ValidationException::withMessages([
            'selected_package' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
                ? 'ไม่พบราคาขายของเบอร์เติมเงิน'
                : 'กรุณาเลือกแพ็กเกจ',
        ]);
    }

    $order->fill([
        'ordered_number' => $orderedNumber !== '' ? $orderedNumber : trim((string) $data['ordered_number']),
        'service_type' => $serviceType,
        'selected_package' => $selectedPackage,
        'title_prefix' => trim((string) ($data['title_prefix'] ?? '')) ?: null,
        'first_name' => trim((string) ($data['first_name'] ?? '')) ?: null,
        'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
        'email' => trim((string) ($data['email'] ?? '')) ?: null,
        'current_phone' => $currentPhone !== '' ? $currentPhone : null,
        'shipping_address_line' => trim((string) ($data['shipping_address_line'] ?? '')) ?: null,
        'district' => trim((string) ($data['district'] ?? '')) ?: null,
        'amphoe' => trim((string) ($data['amphoe'] ?? '')) ?: null,
        'province' => trim((string) ($data['province'] ?? '')) ?: null,
        'zipcode' => $zipcode !== '' ? $zipcode : null,
        'appointment_date' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? null : ($data['appointment_date'] ?? null),
        'appointment_time_slot' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
            ? null
            : (trim((string) ($data['appointment_time_slot'] ?? '')) ?: null),
        'payment_slip_path' => trim((string) $order->payment_slip_path) !== '' ? $order->payment_slip_path : '__pending__',
        'status' => $defaultOrderStatus($serviceType),
    ]);
    $order->save();
    $order->payment_slip_path = $storeOrderPaymentSlip($order, $request->file('payment_slip'));
    $order->save();

    $syncPhoneNumberStatusFromOrder($order);

    if ($isNewOrder) {
        $safelyRunLineNotification(
            fn () => app(LineOrderNotifier::class)->sendOrderSubmitted($order),
            'Order LINE notification failed during step 2 save.',
            ['order_id' => $order->id]
        );
    }

    return response()->json([
        'ok' => true,
        'order_id' => $order->id,
        'message' => 'บันทึกคำสั่งซื้อเรียบร้อยแล้ว',
    ]);
})->name('book.save-step2');

Route::post('/book', function (Request $request) use ($defaultOrderStatus, $normalizeServiceType, $storeOrderPaymentSlip, $safelyRunLineNotification, $syncPhoneNumberStatusFromOrder) {
    $data = $request->validate([
        'saved_order_id' => ['nullable', 'integer'],
        'ordered_number' => ['required', 'string', 'max:20'],
        'selected_package' => ['required', 'integer', 'min:1'],
        'title_prefix' => ['nullable', 'string', 'max:50'],
        'first_name' => ['nullable', 'string', 'max:120'],
        'last_name' => ['nullable', 'string', 'max:120'],
        'email' => ['nullable', 'email', 'max:255'],
        'current_phone' => ['nullable', 'string', 'max:20'],
        'shipping_address_line' => ['nullable', 'string', 'max:255'],
        'district' => ['nullable', 'string', 'max:120'],
        'amphoe' => ['nullable', 'string', 'max:120'],
        'province' => ['nullable', 'string', 'max:120'],
        'zipcode' => ['nullable', 'string', 'max:10'],
        'appointment_date' => ['nullable', 'date'],
        'appointment_time_slot' => ['nullable', 'string', 'max:40'],
        'payment_slip' => ['nullable', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240', 'required_without:saved_order_id'],
    ]);

    $digitsOnly = static fn (?string $value): string => preg_replace('/\D+/', '', (string) $value) ?? '';
    $orderedNumber = $digitsOnly($data['ordered_number']);
    $currentPhone = $digitsOnly($data['current_phone'] ?? null);
    $zipcode = $digitsOnly($data['zipcode'] ?? null);

    $orderId = isset($data['saved_order_id']) ? (int) $data['saved_order_id'] : 0;
    $order = $orderId > 0 ? CustomerOrder::query()->find($orderId) : null;
    if (! $order) {
        $order = new CustomerOrder();
    }
    $isNewOrder = ! $order->exists;

    $currentNumber = PhoneNumber::query()
        ->where('phone_number', $orderedNumber)
        ->first();

    if (! $currentNumber) {
        throw ValidationException::withMessages([
            'ordered_number' => 'ไม่พบเบอร์ในระบบ',
        ]);
    }

    $serviceType = $normalizeServiceType($currentNumber->service_type);
    $isSameSavedOrder = $order->exists
        && $orderedNumber !== ''
        && $orderedNumber === (preg_replace('/\D+/', '', (string) $order->ordered_number) ?? '');

    if (! $isSameSavedOrder && $currentNumber->status !== PhoneNumber::STATUS_ACTIVE) {
        throw ValidationException::withMessages([
            'ordered_number' => 'เบอร์นี้ไม่พร้อมสั่งซื้อแล้ว',
        ]);
    }

    $selectedPackage = $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
        ? (int) ($currentNumber->sale_price ?? 0)
        : (int) $data['selected_package'];

    if ($selectedPackage < 1) {
        throw ValidationException::withMessages([
            'selected_package' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
                ? 'ไม่พบราคาขายของเบอร์เติมเงิน'
                : 'กรุณาเลือกแพ็กเกจ',
        ]);
    }

    $slipPath = $order->payment_slip_path;
    if (! $slipPath && ! $request->hasFile('payment_slip')) {
        return back()
            ->withErrors(['payment_slip' => 'โปรดแนบหลักฐานการโอนเงินเพื่อสั่งซื้อเบอร์'])
            ->withInput();
    }

    $order->fill([
        'ordered_number' => $orderedNumber !== '' ? $orderedNumber : trim((string) $data['ordered_number']),
        'service_type' => $serviceType,
        'selected_package' => $selectedPackage,
        'title_prefix' => trim((string) ($data['title_prefix'] ?? '')) ?: null,
        'first_name' => trim((string) ($data['first_name'] ?? '')) ?: null,
        'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
        'email' => trim((string) ($data['email'] ?? '')) ?: null,
        'current_phone' => $currentPhone !== '' ? $currentPhone : null,
        'shipping_address_line' => trim((string) ($data['shipping_address_line'] ?? '')) ?: null,
        'district' => trim((string) ($data['district'] ?? '')) ?: null,
        'amphoe' => trim((string) ($data['amphoe'] ?? '')) ?: null,
        'province' => trim((string) ($data['province'] ?? '')) ?: null,
        'zipcode' => $zipcode !== '' ? $zipcode : null,
        'appointment_date' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? null : ($data['appointment_date'] ?? null),
        'appointment_time_slot' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID
            ? null
            : (trim((string) ($data['appointment_time_slot'] ?? '')) ?: null),
        'payment_slip_path' => $slipPath ?: '__pending__',
        'status' => $defaultOrderStatus($serviceType),
    ]);
    $order->save();

    if ($request->hasFile('payment_slip')) {
        $order->payment_slip_path = $storeOrderPaymentSlip($order, $request->file('payment_slip'));
        $order->save();
    }

    $syncPhoneNumberStatusFromOrder($order);

    if ($isNewOrder) {
        $safelyRunLineNotification(
            fn () => app(LineOrderNotifier::class)->sendOrderSubmitted($order),
            'Order LINE notification failed during final submit.',
            ['order_id' => $order->id]
        );
    }

    return redirect()
        ->route('book', [
            'number' => $data['ordered_number'],
            'package' => (int) $data['selected_package'],
        ])
        ->with('status_message', 'บันทึกคำสั่งซื้อเรียบร้อยแล้ว เจ้าหน้าที่จะติดต่อกลับโดยเร็วที่สุด');
})->name('book.submit');

Route::post('/line/webhook', function (Request $request) use ($storeLineWebhookEvent) {
    $rawBody = (string) $request->getContent();
    $headers = $request->headers->all();
    $channelSecret = trim((string) config('services.line.channel_secret', ''));
    $signature = (string) $request->header('x-line-signature', '');
    $signatureValid = null;

    if ($channelSecret !== '') {
        $expectedSignature = base64_encode(hash_hmac('sha256', $rawBody, $channelSecret, true));
        $signatureValid = hash_equals($expectedSignature, $signature);
    }

    $payload = json_decode($rawBody, true);
    $payload = is_array($payload) ? $payload : [];

    if (Schema::hasTable('line_webhook_events')) {
        $events = is_array($payload['events'] ?? null) && $payload['events'] !== []
            ? $payload['events']
            : [null];

        foreach ($events as $event) {
            $eventPayload = is_array($event) ? $event : [];
            $source = is_array($eventPayload['source'] ?? null) ? $eventPayload['source'] : [];
            $message = is_array($eventPayload['message'] ?? null) ? $eventPayload['message'] : [];

            $storeLineWebhookEvent([
                'event_type' => is_string($eventPayload['type'] ?? null) ? $eventPayload['type'] : ($signatureValid === false ? 'invalid_signature' : 'webhook_call'),
                'source_type' => is_string($source['type'] ?? null) ? $source['type'] : null,
                'group_id' => is_string($source['groupId'] ?? null) ? $source['groupId'] : null,
                'room_id' => is_string($source['roomId'] ?? null) ? $source['roomId'] : null,
                'user_id' => is_string($source['userId'] ?? null) ? $source['userId'] : null,
                'message_type' => is_string($message['type'] ?? null) ? $message['type'] : null,
                'destination' => is_string($payload['destination'] ?? null) ? $payload['destination'] : null,
                'signature_valid' => $signatureValid,
                'headers' => $headers,
                'payload' => $eventPayload !== [] ? $eventPayload : $payload,
                'raw_body' => $rawBody,
                'received_at' => now(),
            ]);
        }
    }

    if ($channelSecret !== '' && $signatureValid === false) {
        return response()->json(['ok' => false, 'message' => 'Invalid signature.'], 403);
    }

    return response()->json(['ok' => true]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('line.webhook');

Route::get('/line/payment-slips/{order}', function (Request $request, CustomerOrder $order) use ($resolveOrderPaymentSlip) {
    if (! $request->hasValidSignature(false)) {
        abort(403);
    }

    $paymentSlip = $resolveOrderPaymentSlip($order);

    if ($paymentSlip['external_url']) {
        return redirect()->away($paymentSlip['external_url']);
    }

    if (! $paymentSlip['exists'] || ! $paymentSlip['absolute_path']) {
        abort(404);
    }

    $headers = [];

    if ($paymentSlip['mime_type']) {
        $headers['Content-Type'] = $paymentSlip['mime_type'];
    }

    return response()->file($paymentSlip['absolute_path'], $headers);
})->name('line.payment-slip');

Route::prefix('admin')->name('admin.')->group(function () use (
    $currentAdmin,
    $ensureAdmin,
    $buildArticleSlug,
    $resolveArticleImageMeta,
    $resolveOrderPaymentSlip,
    $rejectAdminLogin,
    $safelyRunLineNotification,
    $resolveEnvironmentEditor,
    $resolveAdminLogViewer,
    $latestLineWebhookEvents,
    $storeOrderPaymentSlip,
    $syncPhoneNumberStatusFromOrder
) {
    Route::get('/login', function (Request $request) use ($currentAdmin) {
        if ($currentAdmin()) {
            return redirect()->route('admin.numbers');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()
            ->view('admin.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    })->name('login');

    Route::post('/login', function (Request $request) use ($rejectAdminLogin) {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($credentials['username']);
        $user = User::query()
            ->where('username', $username)
            ->first();

        if (! $user) {
            return $rejectAdminLogin($request);
        }

        if (! $user->canAccessAdminPanel()) {
            return $rejectAdminLogin($request);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return $rejectAdminLogin($request);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ]);

        return redirect()->route('admin.numbers');
    })->name('login.attempt');

    Route::post('/logout', function (Request $request) {
        $request->session()->forget([
            'admin_authenticated',
            'admin_user_id',
            'admin_user_name',
            'admin_user_role',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('logout');

    Route::get('/numbers', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $search = trim((string) $request->query('q', ''));
        $searchDigits = preg_replace('/\D+/', '', $search) ?? '';
        $searchTerm = mb_strtolower($search);

        $numbers = PhoneNumber::query()
            ->when($search !== '', function ($query) use ($search, $searchDigits, $searchTerm) {
                $query->where(function ($innerQuery) use ($search, $searchDigits, $searchTerm) {
                    if ($searchDigits !== '') {
                        $innerQuery->where('phone_number', 'like', '%' . $searchDigits . '%')
                            ->orWhere('display_number', 'like', '%' . $searchDigits . '%');
                    }

                    $innerQuery->orWhereRaw('LOWER(network_code) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(service_type) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(plan_name) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(status) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('CAST(sale_price AS CHAR) like ?', ['%' . $search . '%']);
                });
            })
            ->orderBy('phone_number')
            ->paginate(20);

        return view('admin.numbers', compact('numbers', 'search'));
    })->name('numbers');

    Route::get('/orders', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $orders = CustomerOrder::query()
            ->latest()
            ->paginate(50);

        return view('admin.orders', compact('orders'));
    })->name('orders');

    Route::get('/orders/{order}', function (CustomerOrder $order) use ($ensureAdmin, $resolveOrderPaymentSlip) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        if (Schema::hasTable('line_notification_logs')) {
            $order->load([
                'lineNotificationLogs' => fn ($query) => $query->latest()->limit(20),
            ]);
        } else {
            $order->setRelation('lineNotificationLogs', collect());
        }

        $paymentSlip = $resolveOrderPaymentSlip($order);

        return view('admin.orders-show', compact('order', 'paymentSlip'));
    })->name('orders.show');

    Route::get('/orders/{order}/payment-slip', function (CustomerOrder $order) use ($ensureAdmin, $resolveOrderPaymentSlip) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $paymentSlip = $resolveOrderPaymentSlip($order);

        if ($paymentSlip['external_url']) {
            return redirect()->away($paymentSlip['external_url']);
        }

        if (! $paymentSlip['exists'] || ! $paymentSlip['absolute_path']) {
            abort(404);
        }

        $headers = [];

        if ($paymentSlip['mime_type']) {
            $headers['Content-Type'] = $paymentSlip['mime_type'];
        }

        return response()->file($paymentSlip['absolute_path'], $headers);
    })->name('orders.payment-slip');

    Route::get('/orders/{order}/payment-slip/view', function (CustomerOrder $order) use ($ensureAdmin, $resolveOrderPaymentSlip) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $paymentSlip = $resolveOrderPaymentSlip($order);

        if (! $paymentSlip['exists'] && ! $paymentSlip['external_url']) {
            abort(404);
        }

        return view('admin.orders-payment-slip', [
            'order' => $order,
            'paymentSlip' => $paymentSlip,
            'slipUrl' => route('admin.orders.payment-slip', $order),
        ]);
    })->name('orders.payment-slip.view');

    Route::get('/orders/{order}/edit', function (CustomerOrder $order) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        return view('admin.orders-edit', compact('order'));
    })->name('orders.edit');

    Route::put('/orders/{order}', function (Request $request, CustomerOrder $order) use ($ensureAdmin, $storeOrderPaymentSlip, $safelyRunLineNotification, $syncPhoneNumberStatusFromOrder) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'ordered_number' => ['required', 'string', 'max:20'],
            'selected_package' => ['required', 'integer', 'min:1'],
            'title_prefix' => ['nullable', 'string', 'max:50'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'current_phone' => ['nullable', 'string', 'max:20'],
            'shipping_address_line' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:120'],
            'amphoe' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'zipcode' => ['nullable', 'string', 'max:10'],
            'appointment_date' => ['nullable', 'date'],
            'appointment_time_slot' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'string', 'max:30'],
            'payment_slip' => ['nullable', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240'],
        ]);

        $digitsOnly = static fn (?string $value): string => preg_replace('/\D+/', '', (string) $value) ?? '';

        $orderedNumber = $digitsOnly($data['ordered_number']);
        $currentPhone = $digitsOnly($data['current_phone'] ?? null);
        $zipcode = $digitsOnly($data['zipcode'] ?? null);
        $previousStatus = (string) $order->status;
        $adminUserId = session('admin_user_id');

        $order->fill([
            'ordered_number' => $orderedNumber !== '' ? $orderedNumber : trim((string) $data['ordered_number']),
            'selected_package' => (int) $data['selected_package'],
            'title_prefix' => trim((string) ($data['title_prefix'] ?? '')) ?: null,
            'first_name' => trim((string) ($data['first_name'] ?? '')) ?: null,
            'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'current_phone' => $currentPhone !== '' ? $currentPhone : null,
            'shipping_address_line' => trim((string) ($data['shipping_address_line'] ?? '')) ?: null,
            'district' => trim((string) ($data['district'] ?? '')) ?: null,
            'amphoe' => trim((string) ($data['amphoe'] ?? '')) ?: null,
            'province' => trim((string) ($data['province'] ?? '')) ?: null,
            'zipcode' => $zipcode !== '' ? $zipcode : null,
            'appointment_date' => $data['appointment_date'] ?? null,
            'appointment_time_slot' => trim((string) ($data['appointment_time_slot'] ?? '')) ?: null,
            'status' => trim((string) $data['status']),
        ]);
        $order->save();

        if ($request->hasFile('payment_slip')) {
            $order->payment_slip_path = $storeOrderPaymentSlip($order, $request->file('payment_slip'));
            $order->save();
        }

        $syncPhoneNumberStatusFromOrder(
            $order,
            is_numeric($adminUserId) ? (int) $adminUserId : null
        );

        if (trim($previousStatus) !== trim((string) $order->status)) {
            $safelyRunLineNotification(
                fn () => app(LineOrderNotifier::class)->sendStatusUpdated($order, $previousStatus),
                'Order status LINE notification failed.',
                ['order_id' => $order->id, 'previous_status' => $previousStatus, 'current_status' => $order->status]
            );
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status_message', 'อัปเดตคำสั่งซื้อเรียบร้อยแล้ว');
    })->name('orders.update');

    Route::post('/orders/{order}/line-test', function (CustomerOrder $order) use ($ensureAdmin, $safelyRunLineNotification) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $lineNotifier = app(\App\Services\LineNotifier::class);

        if (! $lineNotifier->isConfigured('admin_test')) {
            return back()->withErrors([
                'line' => 'ยังไม่ได้ตั้งค่า LINE_CHANNEL_ACCESS_TOKEN หรือ LINE_TEST_GROUP_ID/LINE_GROUP_ID',
            ]);
        }

        $sent = $safelyRunLineNotification(
            fn () => app(LineOrderNotifier::class)->sendAdminTest($order),
            'Admin LINE test notification failed.',
            ['order_id' => $order->id]
        );

        if (! $sent) {
            return back()->withErrors([
                'line' => 'ส่ง LINE ไม่สำเร็จ กรุณาตรวจสอบ LINE Settings หรือ Application Logs',
            ]);
        }

        return back()->with('status_message', 'ส่งคำสั่งทดสอบ LINE เรียบร้อยแล้ว');
    })->name('orders.line-test');

    Route::get('/line-settings', function () use ($ensureAdmin, $resolveEnvironmentEditor, $latestLineWebhookEvents) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $environmentEditor = $resolveEnvironmentEditor();

        $settings = $environmentEditor->getMany([
            'LINE_CHANNEL_ACCESS_TOKEN',
            'LINE_CHANNEL_SECRET',
            'LINE_GROUP_ID',
        ]);
        $baseUrl = rtrim((string) config('app.url', url('/')), '/');
        $webhookUrl = $baseUrl . route('line.webhook', [], false);
        $webhookEvents = $latestLineWebhookEvents();

        return view('admin.line-settings', compact('settings', 'webhookUrl', 'webhookEvents'));
    })->name('line-settings');

    Route::post('/line-settings', function (Request $request) use ($ensureAdmin, $resolveEnvironmentEditor) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $environmentEditor = $resolveEnvironmentEditor();

        $data = $request->validate([
            'line_channel_access_token' => ['nullable', 'string', 'max:4000'],
            'line_channel_secret' => ['nullable', 'string', 'max:255'],
            'line_group_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $token = trim((string) ($data['line_channel_access_token'] ?? ''));
            $channelSecret = trim((string) ($data['line_channel_secret'] ?? ''));
            $groupId = trim((string) ($data['line_group_id'] ?? ''));

            $environmentEditor->setMany([
                'LINE_CHANNEL_ACCESS_TOKEN' => $token,
                'LINE_CHANNEL_SECRET' => $channelSecret,
                'LINE_GROUP_ID' => $groupId,
            ]);

            config()->set('services.line.channel_access_token', $token);
            config()->set('services.line.channel_secret', $channelSecret);
            config()->set('services.line.group_id', $groupId);

            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['line_settings' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.line-settings')
            ->with('status_message', 'บันทึก LINE settings เรียบร้อยแล้ว');
    })->name('line-settings.update');

    Route::post('/line-settings/group-id', function (Request $request) use ($ensureAdmin, $resolveEnvironmentEditor) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $environmentEditor = $resolveEnvironmentEditor();

        $data = $request->validate([
            'group_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            $groupId = trim((string) $data['group_id']);

            $environmentEditor->setMany([
                'LINE_GROUP_ID' => $groupId,
            ]);

            config()->set('services.line.group_id', $groupId);

            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            return back()->withErrors(['line_group_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.line-settings')
            ->with('status_message', 'อัปเดต LINE_GROUP_ID จาก webhook เรียบร้อยแล้ว');
    })->name('line-settings.apply-group-id');

    Route::get('/logs', function (Request $request) use ($ensureAdmin, $resolveAdminLogViewer) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $logViewer = $resolveAdminLogViewer();

        $file = trim((string) $request->query('file', ''));
        $level = trim((string) $request->query('level', ''));
        $date = trim((string) $request->query('date', ''));
        $search = trim((string) $request->query('search', ''));

        $availableFiles = $logViewer->availableFiles();
        $selectedFile = $logViewer->resolveFile($file);
        $logContent = $logViewer->readTail($selectedFile['path']);
        $allEntries = $logViewer->parseEntries($logContent);
        $filteredEntries = $logViewer->filterEntries($allEntries, $level, $date, $search);
        $perPage = 5;
        $currentPage = max(1, (int) $request->query('page', 1));
        $pagedEntries = array_slice($filteredEntries, ($currentPage - 1) * $perPage, $perPage);
        $entries = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedEntries,
            count($filteredEntries),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.logs', [
            'availableFiles' => $availableFiles,
            'selectedFile' => $selectedFile,
            'logExists' => $selectedFile['exists'] && $selectedFile['readable'],
            'logPath' => $selectedFile['path'],
            'logSize' => $selectedFile['size'],
            'entries' => $entries,
            'totalEntryCount' => count($allEntries),
            'displayedEntryCount' => count($filteredEntries),
            'displayedByteCount' => strlen($logContent),
            'availableLevels' => $logViewer->availableLevels($allEntries),
            'availableDates' => $logViewer->availableDates($allEntries),
            'filters' => [
                'file' => $selectedFile['name'],
                'level' => $level,
                'date' => $date,
                'search' => $search,
            ],
        ]);
    })->name('logs');

    Route::post('/logs/clear', function (Request $request) use ($ensureAdmin, $resolveAdminLogViewer) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $logViewer = $resolveAdminLogViewer();

        $data = $request->validate([
            'file' => ['nullable', 'string', 'max:255'],
        ]);

        $selectedFile = $logViewer->resolveFile($data['file'] ?? null);

        if (! $selectedFile['exists']) {
            return redirect()
                ->route('admin.logs', ['file' => $selectedFile['name']])
                ->withErrors(['log_file' => 'ไม่พบไฟล์ log ที่เลือก']);
        }

        $logViewer->clearFile($selectedFile['path']);

        return redirect()
            ->route('admin.logs', ['file' => $selectedFile['name']])
            ->with('status_message', 'ล้างไฟล์ log เรียบร้อยแล้ว');
    })->name('logs.clear');

    Route::get('/articles', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $articles = Article::query()
            ->with('author')
            ->latest('created_at')
            ->paginate(20);

        return view('admin.articles', compact('articles'));
    })->name('articles');

    Route::post('/articles', function (Request $request) use ($ensureAdmin, $buildArticleSlug, $resolveArticleImageMeta) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image_landscape' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image_square' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slug = $slugInput !== '' ? Str::slug($slugInput) : $buildArticleSlug($data['title']);
        if ($slug === '') {
            $slug = $buildArticleSlug($data['title']);
        } elseif (Article::query()->where('slug', $slug)->exists()) {
            $slug = $buildArticleSlug($slug);
        }

        $isPublished = $request->boolean('is_published');
        $publishedAt = $data['published_at'] ?? null;
        if ($isPublished && $publishedAt === null) {
            $publishedAt = now();
        }

        $imageDate = $publishedAt ? Carbon::parse((string) $publishedAt, 'Asia/Bangkok') : Carbon::now('Asia/Bangkok');
        $imageMeta = $resolveArticleImageMeta($slug, $imageDate);

        $coverImagePath = null;
        $coverImageLandscapePath = null;
        $coverImageSquarePath = null;
        if ($request->hasFile('cover_image')) {
            $contents = file_get_contents((string) $request->file('cover_image')->getRealPath());
            if ($contents !== false) {
                $coverImagePath = $imageMeta['square_path'];
                Storage::disk('public')->put($coverImagePath, $contents);
                $coverImageSquarePath = $coverImagePath;
            }
        }
        if ($request->hasFile('cover_image_landscape')) {
            $contents = file_get_contents((string) $request->file('cover_image_landscape')->getRealPath());
            if ($contents !== false) {
                $coverImageLandscapePath = $imageMeta['cover_path'];
                Storage::disk('public')->put($coverImageLandscapePath, $contents);
            }
        }
        if ($request->hasFile('cover_image_square')) {
            $contents = file_get_contents((string) $request->file('cover_image_square')->getRealPath());
            if ($contents !== false) {
                $coverImageSquarePath = $imageMeta['square_path'];
                Storage::disk('public')->put($coverImageSquarePath, $contents);
            }
        }

        if ($coverImagePath === null && $coverImageSquarePath !== null) {
            $coverImagePath = $coverImageSquarePath;
        }

        Article::query()->create([
            'title' => trim($data['title']),
            'slug' => $slug,
            'excerpt' => trim((string) ($data['excerpt'] ?? '')) ?: null,
            'content' => trim($data['content']),
            'meta_description' => trim((string) ($data['meta_description'] ?? '')) ?: null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? $publishedAt : null,
            'cover_image_path' => $coverImagePath,
            'cover_image_landscape_path' => $coverImageLandscapePath,
            'cover_image_square_path' => $coverImageSquarePath,
            'author_user_id' => is_numeric(session('admin_user_id')) ? (int) session('admin_user_id') : null,
        ]);

        return redirect()
            ->route('admin.articles')
            ->with('status_message', 'สร้างบทความเรียบร้อย');
    })->name('articles.store');

    Route::get('/articles/{article}/edit', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $comments = ArticleComment::query()
            ->where('article_id', $article->id)
            ->latest('id')
            ->get();

        return view('admin.article-edit', compact('article', 'comments'));
    })->name('articles.edit');

    Route::get('/articles/{article}/preview', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $article->load([
            'approvedComments' => fn ($query) => $query->latest('id'),
        ]);

        return view('articles.show', compact('article'));
    })->name('articles.preview');

    Route::put('/articles/{article}', function (Request $request, Article $article) use ($ensureAdmin, $buildArticleSlug, $resolveArticleImageMeta) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('articles', 'slug')->ignore($article->id),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image_landscape' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image_square' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slug = $slugInput !== '' ? Str::slug($slugInput) : $buildArticleSlug($data['title'], $article->id);
        if ($slug === '') {
            $slug = $buildArticleSlug($data['title'], $article->id);
        }

        $isCurrentlyPublished = (bool) $article->is_published;
        $isPublished = $request->boolean('is_published');
        $publishedAt = $isCurrentlyPublished ? $article->published_at : ($data['published_at'] ?? null);
        if (! $isPublished) {
            $publishedAt = null;
        }
        if ($isPublished && $publishedAt === null) {
            $publishedAt = now();
        }

        $imageDate = $publishedAt
            ? Carbon::parse((string) $publishedAt, 'Asia/Bangkok')
            : ($article->published_at?->copy() ?? $article->created_at?->copy() ?? Carbon::now('Asia/Bangkok'));
        $imageMeta = $resolveArticleImageMeta($slug, $imageDate);

        $coverImagePath = $article->cover_image_path;
        $coverImageLandscapePath = $article->cover_image_landscape_path;
        $coverImageSquarePath = $article->cover_image_square_path;

        if ($request->hasFile('cover_image')) {
            $targetPath = $imageMeta['square_path'];
            if ($coverImagePath && $coverImagePath !== $targetPath) {
                Storage::disk('public')->delete($coverImagePath);
            }

            $contents = file_get_contents((string) $request->file('cover_image')->getRealPath());
            if ($contents !== false) {
                Storage::disk('public')->put($targetPath, $contents);
                $coverImagePath = $targetPath;
                $coverImageSquarePath = $targetPath;
            }
        }
        if ($request->hasFile('cover_image_landscape')) {
            $targetPath = $imageMeta['cover_path'];
            if ($coverImageLandscapePath && $coverImageLandscapePath !== $targetPath) {
                Storage::disk('public')->delete($coverImageLandscapePath);
            }

            $contents = file_get_contents((string) $request->file('cover_image_landscape')->getRealPath());
            if ($contents !== false) {
                Storage::disk('public')->put($targetPath, $contents);
                $coverImageLandscapePath = $targetPath;
            }
        }
        if ($request->hasFile('cover_image_square')) {
            $targetPath = $imageMeta['square_path'];
            if ($coverImageSquarePath && $coverImageSquarePath !== $targetPath) {
                Storage::disk('public')->delete($coverImageSquarePath);
            }

            $contents = file_get_contents((string) $request->file('cover_image_square')->getRealPath());
            if ($contents !== false) {
                Storage::disk('public')->put($targetPath, $contents);
                $coverImageSquarePath = $targetPath;
                $coverImagePath = $targetPath;
            }
        }

        $article->update([
            'title' => trim($data['title']),
            'slug' => $slug,
            'excerpt' => trim((string) ($data['excerpt'] ?? '')) ?: null,
            'content' => trim($data['content']),
            'meta_description' => trim((string) ($data['meta_description'] ?? '')) ?: null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? $publishedAt : null,
            'cover_image_path' => $coverImagePath,
            'cover_image_landscape_path' => $coverImageLandscapePath,
            'cover_image_square_path' => $coverImageSquarePath,
        ]);

        return redirect()
            ->route('admin.articles')
            ->with('status_message', 'อัปเดตบทความเรียบร้อย');
    })->name('articles.update');

    Route::post('/articles/{article}/cover/remove', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $paths = array_values(array_unique(array_filter([
            $article->cover_image_path,
            $article->cover_image_landscape_path,
            $article->cover_image_square_path,
        ])));

        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }

        $article->update([
            'cover_image_path' => null,
            'cover_image_landscape_path' => null,
            'cover_image_square_path' => null,
        ]);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status_message', 'ลบรูปปกเรียบร้อย');
    })->name('articles.cover.remove');

    Route::post('/articles/{article}/archive', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $article->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        ArticleComment::query()
            ->where('article_id', $article->id)
            ->whereIn('status', [ArticleComment::STATUS_PENDING, ArticleComment::STATUS_APPROVED])
            ->update([
                'status' => ArticleComment::STATUS_REJECTED,
                'approved_at' => null,
                'approved_by_user_id' => null,
            ]);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status_message', 'Archive บทความและคอมเมนต์เรียบร้อย');
    })->name('articles.archive');

    Route::post('/articles/{article}/comments/{comment}/archive', function (Article $article, ArticleComment $comment) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        if ($comment->article_id !== $article->id) {
            abort(404);
        }

        $comment->update([
            'status' => ArticleComment::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status_message', 'Archive คอมเมนต์เรียบร้อย');
    })->name('articles.comments.archive');

    Route::post('/articles/{article}/comments/{comment}/unarchive', function (Article $article, ArticleComment $comment) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        if ($comment->article_id !== $article->id) {
            abort(404);
        }

        $comment->update([
            'status' => ArticleComment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => is_numeric(session('admin_user_id')) ? (int) session('admin_user_id') : null,
        ]);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status_message', 'Unarchive คอมเมนต์เรียบร้อย');
    })->name('articles.comments.unarchive');

    Route::delete('/articles/{article}', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $paths = array_values(array_unique(array_filter([
            $article->cover_image_path,
            $article->cover_image_landscape_path,
            $article->cover_image_square_path,
        ])));
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }

        $article->delete();

        return redirect()
            ->route('admin.articles')
            ->with('status_message', 'ลบบทความเรียบร้อย');
    })->name('articles.delete');

    Route::get('/comments', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $comments = ArticleComment::query()
            ->with(['article', 'approver'])
            ->latest('id')
            ->paginate(40);

        return view('admin.comments', compact('comments'));
    })->name('comments');

    Route::post('/comments/{comment}/approve', function (ArticleComment $comment) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $comment->update([
            'status' => ArticleComment::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => is_numeric(session('admin_user_id')) ? (int) session('admin_user_id') : null,
        ]);

        return back()->with('status_message', 'อนุมัติคอมเมนต์เรียบร้อย');
    })->name('comments.approve');

    Route::post('/comments/{comment}/reject', function (ArticleComment $comment) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $comment->update([
            'status' => ArticleComment::STATUS_REJECTED,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        return back()->with('status_message', 'เปลี่ยนสถานะคอมเมนต์เป็นปฏิเสธแล้ว');
    })->name('comments.reject');

    Route::delete('/comments/{comment}', function (ArticleComment $comment) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $comment->delete();

        return back()->with('status_message', 'ลบคอมเมนต์เรียบร้อย');
    })->name('comments.delete');

    Route::get('/hold-numbers', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $numbers = PhoneNumber::query()
            ->where('status', PhoneNumber::STATUS_HOLD)
            ->orderByDesc('id')
            ->get();

        return view('admin.hold-numbers', compact('numbers'));
    })->name('hold-numbers');

    Route::post('/hold-numbers/add', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $rawPhoneNumber = trim((string) $request->input('phone_number', ''));
        $phoneNumberDigits = preg_replace('/[^0-9]/', '', $rawPhoneNumber);

        if (strlen($phoneNumberDigits) !== PhoneNumber::PHONE_NUMBER_LENGTH) {
            return back()
                ->withInput()
                ->with('error_message', 'กรุณากรอกเบอร์ให้ครบ 10 หลัก');
        }

        $phoneNumber = PhoneNumber::query()
            ->where('phone_number', $phoneNumberDigits)
            ->first();

        if (! $phoneNumber) {
            return back()
                ->withInput()
                ->with('error_message', 'ไม่พบเบอร์ในระบบ');
        }

        if ($phoneNumber->status !== PhoneNumber::STATUS_ACTIVE) {
            return back()
                ->withInput()
                ->with('error_message', 'ไม่สามารถ hold ได้: ต้องเป็นเบอร์สถานะ active เท่านั้น');
        }

        $adminUserId = session('admin_user_id');

        $phoneNumber->update([
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        PhoneNumberStatusLog::query()->create([
            'phone_number_id' => $phoneNumber->id,
            'user_id' => is_numeric($adminUserId) ? (int) $adminUserId : null,
            'action' => 'hold',
            'from_status' => PhoneNumber::STATUS_ACTIVE,
            'to_status' => PhoneNumber::STATUS_HOLD,
        ]);

        return redirect()
            ->route('admin.hold-numbers')
            ->with('status_message', 'เปลี่ยนสถานะเป็น hold เรียบร้อย');
    })->name('hold-numbers.add');

    Route::post('/hold-numbers/{phoneNumber}/activate', function (PhoneNumber $phoneNumber) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $adminUserId = session('admin_user_id');
        $fromStatus = $phoneNumber->status;

        if ($fromStatus !== PhoneNumber::STATUS_ACTIVE) {
            $phoneNumber->update([
                'status' => PhoneNumber::STATUS_ACTIVE,
            ]);

            PhoneNumberStatusLog::query()->create([
                'phone_number_id' => $phoneNumber->id,
                'user_id' => is_numeric($adminUserId) ? (int) $adminUserId : null,
                'action' => 'activate',
                'from_status' => $fromStatus,
                'to_status' => PhoneNumber::STATUS_ACTIVE,
            ]);
        }

        return back()->with('status_message', 'เปลี่ยนสถานะเป็น active เรียบร้อย');
    })->name('hold-numbers.activate');

    Route::get('/users', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $users = User::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $roleOptions = User::roleOptions();

        return view('admin.users', compact('users', 'roleOptions'));
    })->name('users');

    Route::post('/users', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in(User::roleOptions())],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::query()->create([
            'name' => trim($data['name']),
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'role' => $data['role'],
            'is_active' => true,
            'password' => $data['password'],
        ]);

        return redirect()
            ->route('admin.users')
            ->with('status_message', 'สร้างผู้ใช้เรียบร้อย');
    })->name('users.store');

    Route::get('/activity-logs', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $logs = PhoneNumberStatusLog::query()
            ->with(['phoneNumber', 'user'])
            ->latest()
            ->paginate(50);

        return view('admin.activity-logs', compact('logs'));
    })->name('activity-logs');
});
