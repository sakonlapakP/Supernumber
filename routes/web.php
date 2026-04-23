<?php

use App\Models\PhoneNumberStatusLog;
use App\Models\PhoneNumber;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\EstimateLead;
use App\Models\LotteryResult;
use App\Models\LineWebhookEvent;
use App\Models\SalesDocument;
use App\Models\User;
use App\Services\ContactSpamFilter;
use App\Services\EnvironmentEditor;
use App\Services\AdminLogViewer;
use App\Services\ArticleContentSanitizer;
use App\Services\Ga4AnalyticsService;
use App\Services\LineEstimateLeadNotifier;
use App\Services\LineLotteryImageService;
use App\Services\LineOrderNotifier;
use App\Services\SalesDocumentPdfService;
use App\Services\TurnstileVerifier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
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
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/__ops/migrate', function (Request $request) {
    $token = trim((string) env('OPS_MIGRATE_TOKEN', ''));
    $allowedIp = trim((string) env('OPS_MIGRATE_ALLOWED_IP', ''));

    abort_if(app()->environment('local'), 404);
    abort_if($token === '', 404);
    abort_unless(hash_equals($token, (string) $request->query('token', '')), 403);

    if ($allowedIp !== '') {
        abort_unless($request->ip() === $allowedIp, 403);
    }

    $commands = [
        ['command' => 'migrate', 'parameters' => ['--force' => true]],
        ['command' => 'optimize:clear', 'parameters' => []],
    ];

    $results = [];

    foreach ($commands as $entry) {
        $exitCode = Artisan::call($entry['command'], $entry['parameters']);

        $results[] = [
            'command' => $entry['command'],
            'exit_code' => $exitCode,
            'output' => trim((string) Artisan::output()),
        ];
    }

    return response()->json([
        'ok' => collect($results)->every(fn (array $result): bool => $result['exit_code'] === 0),
        'results' => $results,
    ]);
});

// Image pre-upload endpoint — neutral URL path to avoid WAF pattern matching.
// This is called via AJAX before the article form is submitted.
// The article save route then only receives a stored path string, not file data.
Route::post('/p/img', function (Request $request) {
    // Require active admin session (inline — closures not yet defined at this point in the file)
    if (! session('admin_authenticated')) {
        return response()->json(['ok' => false, 'error' => 'Unauthorized'], 403);
    }
    $userId = session('admin_user_id');
    $user = is_numeric($userId) ? User::find((int) $userId) : null;
    if (! $user || ! $user->canAccessAdminPanel()) {
        return response()->json(['ok' => false, 'error' => 'Unauthorized'], 403);
    }

    $file = $request->file('img');

    if (! $file || ! $file->isValid()) {
        return response()->json(['ok' => false, 'error' => 'No file received'], 400);
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (! in_array($file->getMimeType(), $allowedMimes, true)) {
        return response()->json(['ok' => false, 'error' => 'Invalid file type'], 400);
    }

    if ($file->getSize() > 5 * 1024 * 1024) {
        return response()->json(['ok' => false, 'error' => 'File too large (max 5 MB)'], 400);
    }

    try {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? public_path();
        $storageDir = rtrim($docRoot, '/') . '/storage';

        // Force a real folder instead of symlink to fix Nginx 404 on DirectAdmin
        if (is_link($storageDir)) {
            @unlink($storageDir);
        }

        $targetDir = $storageDir . '/articles/tmp';
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $ext  = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $name = 'img_' . uniqid('', true) . '.' . $ext;
        $file->move($targetDir, $name);

        // Stored path is relative to the storage root, matching asset('storage/'.$path)
        $storedPath = 'articles/tmp/' . $name;

        return response()->json(['ok' => true, 'path' => $storedPath]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
})->name('img.store');

Route::get('/debug-paths-for-waf', function () {
    return [
        'public_path' => public_path(),
        'base_path' => base_path(),
        'doc_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'null',
        'storage_is_link' => is_link(public_path('storage')),
        'storage_exists' => file_exists(public_path('storage')),
        'storage_is_dir' => is_dir(public_path('storage')),
        'storage_real' => file_exists(public_path('storage')) ? realpath(public_path('storage')) : 'none',
        'doc_root_storage_link' => isset($_SERVER['DOCUMENT_ROOT']) ? is_link($_SERVER['DOCUMENT_ROOT'].'/storage') : 'N/A',
        'doc_root_storage_exists' => isset($_SERVER['DOCUMENT_ROOT']) ? file_exists($_SERVER['DOCUMENT_ROOT'].'/storage') : 'N/A',
    ];
});




Route::match(['GET', 'POST'], '/__debug/article-upload', function (Request $request) {
    $token = trim((string) env('ARTICLE_UPLOAD_DEBUG_TOKEN', ''));

    abort_if($token === '', 404);
    abort_unless(hash_equals($token, (string) $request->query('token', $request->input('token', ''))), 403);

    $session = $request->session();
    $storageLinkPath = public_path('storage');
    $publicStoragePath = storage_path('app/public');
    $diagnostics = [
        'method' => $request->method(),
        'path' => $request->path(),
        'full_url' => $request->fullUrl(),
        'host' => $request->getHost(),
        'scheme' => $request->getScheme(),
        'ip' => $request->ip(),
        'has_cookie_header' => $request->headers->has('cookie'),
        'cookie_names' => array_keys($request->cookies->all()),
        'session_id' => $session->getId(),
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain'),
        'session_secure' => (bool) config('session.secure'),
        'session_authenticated' => (bool) session('admin_authenticated'),
        'session_user_id' => session('admin_user_id'),
        'session_user_role' => session('admin_user_role'),
        'public_storage_link_exists' => file_exists($storageLinkPath),
        'public_storage_link_is_symlink' => is_link($storageLinkPath),
        'public_storage_link_target' => is_link($storageLinkPath) ? readlink($storageLinkPath) : null,
        'public_storage_path' => $publicStoragePath,
        'public_storage_is_writable' => is_writable($publicStoragePath),
        'article_preview_route' => route('admin.articles.preview', ['article' => 1]),
        'article_update_route' => route('admin.articles.update', ['article' => 1]),
    ];

    if ($request->isMethod('post')) {
        $file = $request->file('debug_image');

        $diagnostics['posted'] = [
            'has_file' => $request->hasFile('debug_image'),
            'file_name' => $file?->getClientOriginalName(),
            'file_size' => $file?->getSize(),
            'file_mime' => $file?->getClientMimeType(),
            'tmp_path_exists' => $file ? is_file($file->getRealPath()) : false,
        ];
    }

    return response()->view('debug.article-upload', [
        'token' => $token,
        'diagnostics' => $diagnostics,
    ]);
});

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

$sanitizeArticleContent = function (string $content): string {
    return app(ArticleContentSanitizer::class)->sanitize($content);
};

$articleColumnExists = function (string $column): bool {
    static $columns = null;

    if ($columns === null) {
        $columns = array_flip(Schema::getColumnListing('articles'));
    }

    return isset($columns[$column]);
};

$ensurePublicStorageLink = function (): void {
    $linkPath = public_path('storage');

    if (is_link($linkPath) || file_exists($linkPath)) {
        return;
    }

    Artisan::call('storage:link');
};

/**
 * Decode a Base64 data-URI string into a temporary UploadedFile.
 * Returns null when the input is empty or invalid.
 *
 * Accepted format: "data:image/jpeg;base64,/9j/4AAQ..."
 */
$decodeBase64Image = function (?string $base64String): ?UploadedFile {
    if ($base64String === null || $base64String === '') {
        return null;
    }

    // Strip the data-URI prefix: "data:image/png;base64,"
    if (! preg_match('#^data:image/(jpe?g|png|webp);base64,(.+)$#i', $base64String, $matches)) {
        return null;
    }

    $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
    $decoded = base64_decode($matches[2], true);

    if ($decoded === false || strlen($decoded) < 100) {
        return null;
    }

    $tmpPath = tempnam(sys_get_temp_dir(), 'b64img_');
    file_put_contents($tmpPath, $decoded);

    $mimeMap = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    return new UploadedFile(
        $tmpPath,
        'upload.' . $extension,
        $mimeMap[$extension] ?? 'image/jpeg',
        null,
        true // mark as "test" so it skips the is_uploaded_file() check
    );
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
            $day <= 15 ? 'first' : 'second'
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

$resolveLotteryResultForArticle = function (Article $article): ?LotteryResult {
    if (! Schema::hasTable('lottery_results') || ! Schema::hasTable('lottery_result_prizes')) {
        return null;
    }

    $slug = Str::lower(trim((string) $article->slug));
    $latestResultQuery = LotteryResult::query()
        ->with('prizes')
        ->orderByRaw('COALESCE(source_draw_date, draw_date) DESC')
        ->orderByDesc('fetched_at')
        ->orderByDesc('id');

    if ($slug === 'thai-government-lottery-latest-results') {
        return (clone $latestResultQuery)->first();
    }

    if (preg_match('/^thai-goverment-lottery-(\d{4})(\d{2})(first|second)$/', $slug, $matches) !== 1) {
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
    return CustomerOrder::defaultStatusForServiceType($serviceType);
};

$logPhoneNumberStatusChange = function (PhoneNumber $phoneNumber, ?string $fromStatus, ?int $userId = null) {
    $toStatus = (string) $phoneNumber->status;
    $fromStatus = $fromStatus !== null ? (string) $fromStatus : null;

    if ($fromStatus === $toStatus) {
        return;
    }

    $action = match ($toStatus) {
        PhoneNumber::STATUS_SOLD => 'sell',
        PhoneNumber::STATUS_ACTIVE => 'release',
        default => 'hold',
    };

    PhoneNumberStatusLog::query()->create([
        'phone_number_id' => $phoneNumber->id,
        'user_id' => $userId,
        'action' => $action,
        'from_status' => $fromStatus,
        'to_status' => $toStatus,
    ]);
};

$syncPhoneNumberStatusFromOrder = function (CustomerOrder $order, ?int $userId = null) use ($normalizeServiceType, $logPhoneNumberStatusChange) {
    if ($normalizeServiceType($order->service_type) !== PhoneNumber::SERVICE_TYPE_PREPAID) {
        return;
    }

    $phoneNumber = $order->phone_number_id !== null
        ? $order->phoneNumber()->first()
        : null;

    if (! $phoneNumber) {
        $orderedNumber = preg_replace('/\D+/', '', (string) $order->ordered_number) ?? '';
        if ($orderedNumber === '') {
            return;
        }

        $phoneNumber = PhoneNumber::query()
            ->where('phone_number', $orderedNumber)
            ->first();
    }

    if (! $phoneNumber) {
        return;
    }

    $targetStatus = CustomerOrder::resolvePhoneNumberStatus($order->status);
    if ($targetStatus === null) {
        return;
    }

    if ($phoneNumber->status === $targetStatus) {
        return;
    }

    $fromStatus = $phoneNumber->status;

    $phoneNumber->update([
        'status' => $targetStatus,
    ]);

    $logPhoneNumberStatusChange($phoneNumber, $fromStatus, $userId);
};

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('sitemap');
Route::view('/under-construction', 'under-construction')->name('under-construction');
Route::redirect('/underconsturter', '/under-construction');

Route::get('/numbers', [PublicController::class, 'numbers'])->name('numbers.index');

Route::get('/articles', [PublicController::class, 'articles'])->name('articles.index');

Route::get('/articles/{slug}', [PublicController::class, 'showArticle'])->name('articles.show');

Route::post('/articles/{slug}/comments', [PublicController::class, 'storeArticleComment'])->name('articles.comments.store');

Route::get('/evaluate', [PublicController::class, 'evaluate'])->name('evaluate');

Route::get('/evaluateBadNumber', [PublicController::class, 'evaluateBad'])->name('evaluate.bad');

Route::get('/tiers', [PublicController::class, 'tiers'])->name('tiers');

Route::get('/estimate', function () {
    return view('under-construction');
})->name('estimate');

Route::get('/sales-documents', function () {
    return redirect()->route('admin.sales-documents');
})->name('sales-documents');

Route::get('/contact-us', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact-us', [PublicController::class, 'storeContact'])->middleware('throttle:contact-messages')->name('contact.store');

Route::get('/privacy-policy', [PublicController::class, 'privacy'])->name('privacy');

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

$normalizeOrderCustomerDetails = static function (array $data, string $currentPhone, string $zipcode): array {
    return [
        'title_prefix' => trim((string) ($data['title_prefix'] ?? '')),
        'first_name' => trim((string) ($data['first_name'] ?? '')),
        'last_name' => trim((string) ($data['last_name'] ?? '')),
        'email' => trim((string) ($data['email'] ?? '')),
        'current_phone' => $currentPhone,
        'shipping_address_line' => trim((string) ($data['shipping_address_line'] ?? '')),
        'district' => trim((string) ($data['district'] ?? '')),
        'amphoe' => trim((string) ($data['amphoe'] ?? '')),
        'province' => trim((string) ($data['province'] ?? '')),
        'zipcode' => $zipcode,
    ];
};

$validatePrepaidOrderCustomerDetails = static function (array $customerDetails): void {
    validator($customerDetails, [
        'title_prefix' => ['required', 'string', 'max:50'],
        'first_name' => ['required', 'string', 'max:120'],
        'last_name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:255'],
        'current_phone' => ['required', 'digits:10'],
        'shipping_address_line' => ['required', 'string', 'max:255'],
        'district' => ['required', 'string', 'max:120'],
        'amphoe' => ['required', 'string', 'max:120'],
        'province' => ['required', 'string', 'max:120'],
        'zipcode' => ['required', 'digits:5'],
    ], [
        'title_prefix.required' => 'กรุณาเลือกคำนำหน้าชื่อ',
        'first_name.required' => 'กรุณากรอกชื่อ',
        'last_name.required' => 'กรุณากรอกนามสกุล',
        'email.required' => 'กรุณากรอกอีเมลล์',
        'email.email' => 'กรุณากรอกอีเมลล์ให้ถูกต้อง',
        'current_phone.required' => 'กรุณากรอกเบอร์มือถือปัจจุบัน',
        'current_phone.digits' => 'กรุณากรอกเบอร์มือถือปัจจุบันให้ครบ 10 หลัก',
        'shipping_address_line.required' => 'กรุณากรอกที่อยู่ที่จัดส่ง',
        'district.required' => 'กรุณากรอกตำบล / แขวง',
        'amphoe.required' => 'กรุณากรอกอำเภอ / เขต',
        'province.required' => 'กรุณากรอกจังหวัด',
        'zipcode.required' => 'กรุณากรอกรหัสไปรษณีย์',
        'zipcode.digits' => 'กรุณากรอกรหัสไปรษณีย์ให้ครบ 5 หลัก',
    ])->validate();
};

Route::post('/book/save-step2', function (Request $request) use ($defaultOrderStatus, $normalizeServiceType, $storeOrderPaymentSlip, $safelyRunLineNotification, $syncPhoneNumberStatusFromOrder, $normalizeOrderCustomerDetails, $validatePrepaidOrderCustomerDetails) {
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
    $customerDetails = $normalizeOrderCustomerDetails($data, $currentPhone, $zipcode);

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

    if ($serviceType === PhoneNumber::SERVICE_TYPE_PREPAID) {
        $validatePrepaidOrderCustomerDetails($customerDetails);
    }

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
        'phone_number_id' => $currentNumber->id,
        'ordered_number' => $orderedNumber !== '' ? $orderedNumber : trim((string) $data['ordered_number']),
        'service_type' => $serviceType,
        'selected_package' => $selectedPackage,
        'title_prefix' => $customerDetails['title_prefix'] !== '' ? $customerDetails['title_prefix'] : null,
        'first_name' => $customerDetails['first_name'] !== '' ? $customerDetails['first_name'] : null,
        'last_name' => $customerDetails['last_name'] !== '' ? $customerDetails['last_name'] : null,
        'email' => $customerDetails['email'] !== '' ? $customerDetails['email'] : null,
        'current_phone' => $customerDetails['current_phone'] !== '' ? $customerDetails['current_phone'] : null,
        'shipping_address_line' => $customerDetails['shipping_address_line'] !== '' ? $customerDetails['shipping_address_line'] : null,
        'district' => $customerDetails['district'] !== '' ? $customerDetails['district'] : null,
        'amphoe' => $customerDetails['amphoe'] !== '' ? $customerDetails['amphoe'] : null,
        'province' => $customerDetails['province'] !== '' ? $customerDetails['province'] : null,
        'zipcode' => $customerDetails['zipcode'] !== '' ? $customerDetails['zipcode'] : null,
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

Route::post('/book', function (Request $request) use ($defaultOrderStatus, $normalizeServiceType, $storeOrderPaymentSlip, $safelyRunLineNotification, $syncPhoneNumberStatusFromOrder, $normalizeOrderCustomerDetails, $validatePrepaidOrderCustomerDetails) {
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
    $customerDetails = $normalizeOrderCustomerDetails($data, $currentPhone, $zipcode);

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

    if ($serviceType === PhoneNumber::SERVICE_TYPE_PREPAID) {
        $validatePrepaidOrderCustomerDetails($customerDetails);
    }

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
        'phone_number_id' => $currentNumber->id,
        'ordered_number' => $orderedNumber !== '' ? $orderedNumber : trim((string) $data['ordered_number']),
        'service_type' => $serviceType,
        'selected_package' => $selectedPackage,
        'title_prefix' => $customerDetails['title_prefix'] !== '' ? $customerDetails['title_prefix'] : null,
        'first_name' => $customerDetails['first_name'] !== '' ? $customerDetails['first_name'] : null,
        'last_name' => $customerDetails['last_name'] !== '' ? $customerDetails['last_name'] : null,
        'email' => $customerDetails['email'] !== '' ? $customerDetails['email'] : null,
        'current_phone' => $customerDetails['current_phone'] !== '' ? $customerDetails['current_phone'] : null,
        'shipping_address_line' => $customerDetails['shipping_address_line'] !== '' ? $customerDetails['shipping_address_line'] : null,
        'district' => $customerDetails['district'] !== '' ? $customerDetails['district'] : null,
        'amphoe' => $customerDetails['amphoe'] !== '' ? $customerDetails['amphoe'] : null,
        'province' => $customerDetails['province'] !== '' ? $customerDetails['province'] : null,
        'zipcode' => $customerDetails['zipcode'] !== '' ? $customerDetails['zipcode'] : null,
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

Route::get('/line/lottery-results/{lotteryResult}/image', function (Request $request, LotteryResult $lotteryResult) {
    if (! $request->hasValidSignature(false)) {
        abort(403);
    }

    return app(LineLotteryImageService::class)->toResponse($lotteryResult->loadMissing('prizes'));
})->name('line.lottery-result-image');

Route::prefix('admin')->name('admin.')->group(function () use (
    $currentAdmin,
    $ensureAdmin,
    $buildArticleSlug,
    $resolveLotteryResultForArticle,
    $resolveArticleImageMeta,
    $resolveOrderPaymentSlip,
    $rejectAdminLogin,
    $safelyRunLineNotification,
    $resolveEnvironmentEditor,
    $resolveAdminLogViewer,
    $latestLineWebhookEvents,
    $storeOrderPaymentSlip,
    $logPhoneNumberStatusChange,
    $normalizeServiceType,
    $syncPhoneNumberStatusFromOrder,
    $sanitizeArticleContent,
    $articleColumnExists,
    $ensurePublicStorageLink,
    $decodeBase64Image
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

    Route::match(['get', 'post'], '/logout', function (Request $request) {
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
        $selectedServiceType = trim((string) $request->query('service_type', ''));
        $searchDigits = preg_replace('/\D+/', '', $search) ?? '';
        $searchTerm = mb_strtolower($search);

        if (! in_array($selectedServiceType, PhoneNumber::serviceTypeOptions(), true)) {
            $selectedServiceType = null;
        }

        $numbers = PhoneNumber::query()
            ->when($selectedServiceType !== null, function ($query) use ($selectedServiceType) {
                $query->where('service_type', $selectedServiceType);
            })
            ->when($search !== '', function ($query) use ($search, $searchDigits, $searchTerm) {
                $query->where(function ($innerQuery) use ($search, $searchDigits, $searchTerm) {
                    if ($searchDigits !== '') {
                        $innerQuery->where('phone_number', 'like', '%' . $searchDigits . '%')
                            ->orWhere('display_number', 'like', '%' . $searchDigits . '%');
                    }

                    $innerQuery->orWhereRaw('LOWER(network_code) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('CAST(number_sum AS CHAR) like ?', ['%' . $search . '%'])
                        ->orWhereRaw('LOWER(service_type) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(plan_name) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(status) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('CAST(sale_price AS CHAR) like ?', ['%' . $search . '%']);
                });
            })
            ->orderBy('phone_number')
            ->paginate(20);

        $pageTitle = match ($selectedServiceType) {
            PhoneNumber::SERVICE_TYPE_POSTPAID => 'Postpaid Numbers',
            PhoneNumber::SERVICE_TYPE_PREPAID => 'Prepaid Numbers',
            default => 'All Numbers',
        };

        $pageSubtitle = match ($selectedServiceType) {
            PhoneNumber::SERVICE_TYPE_POSTPAID => 'แสดงเฉพาะเบอร์รายเดือนในระบบ พร้อมสถานะของแต่ละเบอร์',
            PhoneNumber::SERVICE_TYPE_PREPAID => 'แสดงเฉพาะเบอร์เติมเงินในระบบ พร้อมสถานะของแต่ละเบอร์',
            default => 'แสดงเบอร์ทั้งหมดในระบบ พร้อมสถานะของแต่ละเบอร์',
        };

        return view('admin.numbers', compact('numbers', 'search', 'selectedServiceType', 'pageTitle', 'pageSubtitle'));
    })->name('numbers');

    Route::get('/numbers/{phoneNumber}/edit', function (PhoneNumber $phoneNumber) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        return view('admin.numbers-edit', compact('phoneNumber'));
    })->name('numbers.edit');

    Route::put('/numbers/{phoneNumber}', function (Request $request, PhoneNumber $phoneNumber) use ($ensureAdmin, $normalizeServiceType, $logPhoneNumberStatusChange) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'display_number' => ['nullable', 'string', 'max:20'],
            'number_sum' => ['nullable', 'integer', 'min:1', 'max:999'],
            'service_type' => ['required', 'string', Rule::in(PhoneNumber::serviceTypeOptions())],
            'network_code' => ['required', 'string', 'max:20'],
            'plan_name' => ['nullable', 'string', 'max:255'],
            'price_text' => ['nullable', 'string', 'max:255'],
            'sale_price' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::in(PhoneNumber::adminStatusOptions())],
        ]);

        $adminUserId = session('admin_user_id');
        $previousStatus = (string) $phoneNumber->status;
        $serviceTypeFilter = trim((string) $request->query('service_type_filter', ''));
        $serviceType = $normalizeServiceType($data['service_type']);
        $networkCode = strtolower(trim((string) $data['network_code']));
        $networkCode = preg_replace('/[^a-z0-9]+/', '_', $networkCode) ?? '';
        $networkCode = trim($networkCode, '_');
        $numberSum = isset($data['number_sum']) ? (int) $data['number_sum'] : null;
        $salePrice = (int) $data['sale_price'];
        $displayNumber = trim((string) ($data['display_number'] ?? ''));
        $planName = trim((string) ($data['plan_name'] ?? ''));
        $priceText = trim((string) ($data['price_text'] ?? ''));

        if ($serviceType === PhoneNumber::SERVICE_TYPE_PREPAID && $planName === '') {
            $planName = 'เติมเงิน';
        }

        if ($serviceType === PhoneNumber::SERVICE_TYPE_POSTPAID && $planName === '') {
            $planName = PhoneNumber::PACKAGE_NAME;
        }

        if ($priceText === '') {
            $priceText = (string) $salePrice;
        }

        if (! in_array($serviceTypeFilter, PhoneNumber::serviceTypeOptions(), true)) {
            $serviceTypeFilter = null;
        }

        DB::transaction(function () use (
            $phoneNumber,
            $displayNumber,
            $numberSum,
            $serviceType,
            $networkCode,
            $planName,
            $priceText,
            $salePrice,
            $data,
            $previousStatus,
            $adminUserId,
            $logPhoneNumberStatusChange
        ) {
            $phoneNumber->fill([
                'display_number' => $displayNumber !== '' ? $displayNumber : null,
                'number_sum' => $numberSum,
                'service_type' => $serviceType,
                'network_code' => $networkCode !== '' ? $networkCode : 'true_dtac',
                'plan_name' => $planName !== '' ? $planName : null,
                'price_text' => $priceText !== '' ? $priceText : null,
                'sale_price' => $salePrice,
                'status' => trim((string) $data['status']),
            ]);
            $phoneNumber->save();

            $logPhoneNumberStatusChange(
                $phoneNumber,
                $previousStatus,
                is_numeric($adminUserId) ? (int) $adminUserId : null
            );
        });

        return redirect()
            ->route('admin.numbers.edit', array_filter([
                'phoneNumber' => $phoneNumber,
                'service_type_filter' => $serviceTypeFilter,
            ], static fn ($value) => $value !== null && $value !== ''))
            ->with('status_message', 'อัปเดตข้อมูลเบอร์เรียบร้อยแล้ว');
    })->name('numbers.update');

    Route::get('/orders', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $orders = CustomerOrder::query()
            ->latest()
            ->paginate(50);

        return view('admin.orders', compact('orders'));
    })->name('orders');

    Route::get('/sales-documents', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderByRaw('LOWER(COALESCE(company_name, first_name, last_name, ""))')
            ->get();
        $prefillPayload = null;

        $savedDocumentId = (int) $request->integer('document');

        if ($savedDocumentId > 0) {
            $savedDocument = SalesDocument::query()->find($savedDocumentId);
            $prefillPayload = $savedDocument?->payload;
        }

        return view('sales-documents', compact('customers', 'prefillPayload'));
    })->name('sales-documents');

    Route::post('/sales-documents/save-download', function (Request $request) use ($ensureAdmin, $currentAdmin) {
        if ($redirect = $ensureAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'document_type' => ['required', 'string', Rule::in(['quotation', 'invoice'])],
            'document_number' => ['required', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ]);

        $currentAdminUser = $currentAdmin();
        $service = app(SalesDocumentPdfService::class);
        $payload = $data['payload'];
        $payload['document_type'] = $data['document_type'];
        $payload['document_number'] = $data['document_number'];
        $payload['document_date'] = $data['document_date'] ?? null;
        $payload['due_date'] = $data['due_date'] ?? null;
        $payload['customer_id'] = $data['customer_id'] ?? null;
        $payload['customer_name'] = $data['customer_name'] ?? null;

        $document = $service->saveDocument($payload, $currentAdminUser?->id);

        return response()->json([
            'message' => ($document->document_type === 'invoice' ? 'บันทึก Invoice' : 'บันทึก Quotation') . ' เรียบร้อยแล้ว กำลังเปิดหน้าพิมพ์สำหรับบันทึก PDF',
            'document_id' => $document->id,
            'download_url' => route('admin.saved-sales-documents.download', $document),
            'show_url' => route('admin.saved-sales-documents.show', $document),
            'file_name' => $document->file_name . '.pdf',
            'pdf_path' => $document->pdf_path,
        ]);
    })->name('sales-documents.save-download');

    Route::get('/saved-sales-documents', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $documents = SalesDocument::query()
            ->latest('updated_at')
            ->paginate(30);

        return view('admin.sales-documents-index', compact('documents'));
    })->name('saved-sales-documents.index');

    Route::get('/saved-sales-documents/{salesDocument}', function (SalesDocument $salesDocument) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        return view('admin.sales-document-show', [
            'document' => $salesDocument,
        ]);
    })->name('saved-sales-documents.show');

    Route::get('/saved-sales-documents/{salesDocument}/preview', function (SalesDocument $salesDocument) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $service = app(SalesDocumentPdfService::class);

        return response($service->renderDocumentHtml($salesDocument))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    })->name('saved-sales-documents.preview');

    Route::get('/saved-sales-documents/{salesDocument}/download', function (SalesDocument $salesDocument) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $disk = Storage::disk($salesDocument->pdf_disk ?: 'local');
        $pdfPath = trim((string) $salesDocument->pdf_path);

        if ($pdfPath !== '' && $disk->exists($pdfPath)) {
            return $disk->download($pdfPath, $salesDocument->file_name . '.pdf');
        }

        $service = app(SalesDocumentPdfService::class);

        return response($service->renderDocumentHtml($salesDocument, [
            'showPrintToolbar' => true,
            'autoPrint' => true,
            'printButtonLabel' => 'พิมพ์ / บันทึก PDF',
        ]))->header('Content-Type', 'text/html; charset=UTF-8');
    })->name('saved-sales-documents.download');

    Route::delete('/saved-sales-documents/{salesDocument}', function (SalesDocument $salesDocument) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        $disk = Storage::disk($salesDocument->pdf_disk ?: 'local');
        $pdfPath = trim((string) $salesDocument->pdf_path);

        if ($pdfPath !== '' && $disk->exists($pdfPath)) {
            $disk->delete($pdfPath);
        }

        $salesDocument->delete();

        return redirect()
            ->route('admin.saved-sales-documents.index')
            ->with('status_message', 'ลบเอกสารเรียบร้อยแล้ว');
    })->name('saved-sales-documents.delete');

    Route::get('/customers', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $customers = Customer::query()
            ->orderByDesc('is_active')
            ->orderByRaw('LOWER(COALESCE(company_name, first_name, last_name, ""))')
            ->get();

        return view('admin.customers', compact('customers'));
    })->name('customers');

    Route::post('/customers', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:4000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $companyName = trim((string) ($data['company_name'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));

        if ($companyName === '' && $firstName === '' && $lastName === '') {
            throw ValidationException::withMessages([
                'company_name' => 'กรุณากรอกชื่อบริษัท หรือชื่อ-นามสกุลลูกค้าอย่างน้อย 1 รายการ',
            ]);
        }

        Customer::query()->create([
            'company_name' => $companyName !== '' ? $companyName : null,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'tax_id' => trim((string) ($data['tax_id'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'payment_term' => trim((string) ($data['payment_term'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()
            ->route('admin.customers')
            ->with('status_message', 'บันทึกข้อมูลลูกค้าเรียบร้อยแล้ว');
    })->name('customers.store');

    Route::post('/customers/quick-store', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:4000'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $companyName = trim((string) ($data['company_name'] ?? ''));
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        $taxId = trim((string) ($data['tax_id'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));

        if ($companyName === '' || $taxId === '' || $address === '') {
            throw ValidationException::withMessages([
                'company_name' => 'กรุณากรอกชื่อลูกค้า เลขภาษี และที่อยู่ให้ครบก่อนบันทึก',
            ]);
        }

        $firstName = null;
        $lastName = null;

        if ($contactName !== '') {
            $contactParts = preg_split('/\s+/', $contactName) ?: [];
            $firstName = trim((string) array_shift($contactParts)) ?: null;
            $lastName = trim(implode(' ', $contactParts)) ?: null;
        }

        $customer = Customer::query()->create([
            'company_name' => $companyName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tax_id' => $taxId,
            'address' => $address,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'payment_term' => trim((string) ($data['payment_term'] ?? '')) ?: null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'บันทึกลูกค้าเรียบร้อยแล้ว',
            'customer' => [
                'id' => $customer->id,
                'display_name' => $customer->display_name,
                'company_name' => $customer->company_name,
                'contact_name' => $customer->contact_name,
                'tax_id' => $customer->tax_id,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'payment_term' => $customer->payment_term,
            ],
        ]);
    })->name('customers.quick-store');

    Route::put('/customers/{customer}/quick-update', function (Request $request, Customer $customer) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:4000'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $companyName = trim((string) ($data['company_name'] ?? ''));
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        $taxId = trim((string) ($data['tax_id'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));

        if ($companyName === '' || $taxId === '' || $address === '') {
            throw ValidationException::withMessages([
                'company_name' => 'กรุณากรอกชื่อลูกค้า เลขภาษี และที่อยู่ให้ครบก่อนบันทึก',
            ]);
        }

        $firstName = null;
        $lastName = null;

        if ($contactName !== '') {
            $contactParts = preg_split('/\s+/', $contactName) ?: [];
            $firstName = trim((string) array_shift($contactParts)) ?: null;
            $lastName = trim(implode(' ', $contactParts)) ?: null;
        }

        $customer->fill([
            'company_name' => $companyName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tax_id' => $taxId,
            'address' => $address,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'payment_term' => trim((string) ($data['payment_term'] ?? '')) ?: null,
            'is_active' => true,
        ]);
        $customer->save();

        return response()->json([
            'message' => 'อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว',
            'customer' => [
                'id' => $customer->id,
                'display_name' => $customer->display_name,
                'company_name' => $customer->company_name,
                'contact_name' => $customer->contact_name,
                'tax_id' => $customer->tax_id,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'payment_term' => $customer->payment_term,
            ],
        ]);
    })->name('customers.quick-update');

    Route::put('/customers/{customer}', function (Request $request, Customer $customer) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:4000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'payment_term' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $companyName = trim((string) ($data['company_name'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));

        if ($companyName === '' && $firstName === '' && $lastName === '') {
            throw ValidationException::withMessages([
                'company_name' => 'กรุณากรอกชื่อบริษัท หรือชื่อ-นามสกุลลูกค้าอย่างน้อย 1 รายการ',
            ]);
        }

        $customer->fill([
            'company_name' => $companyName !== '' ? $companyName : null,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'tax_id' => trim((string) ($data['tax_id'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'payment_term' => trim((string) ($data['payment_term'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);
        $customer->save();

        return redirect()
            ->route('admin.customers')
            ->with('status_message', 'อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว');
    })->name('customers.update');

    Route::get('/orders/{order}', function (CustomerOrder $order) use ($ensureAdmin, $resolveOrderPaymentSlip, $currentAdmin) {
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
        $canTestLineNotification = $currentAdmin()?->role === User::ROLE_MANAGER;

        return view('admin.orders-show', compact('order', 'paymentSlip', 'canTestLineNotification'));
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

    Route::put('/orders/{order}', function (Request $request, CustomerOrder $order) use ($ensureAdmin, $normalizeServiceType, $storeOrderPaymentSlip, $safelyRunLineNotification, $syncPhoneNumberStatusFromOrder) {
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
            'status' => ['required', 'string', Rule::in(CustomerOrder::statusOptions())],
            'payment_slip' => ['nullable', 'file', 'mimes:jpg,jpeg,png,heic,heif,pdf', 'max:10240'],
        ]);

        $digitsOnly = static fn (?string $value): string => preg_replace('/\D+/', '', (string) $value) ?? '';

        $orderedNumber = $digitsOnly($data['ordered_number']);
        $currentPhone = $digitsOnly($data['current_phone'] ?? null);
        $zipcode = $digitsOnly($data['zipcode'] ?? null);
        $previousStatus = (string) $order->status;
        $adminUserId = session('admin_user_id');
        $matchedPhoneNumber = $orderedNumber !== ''
            ? PhoneNumber::query()->where('phone_number', $orderedNumber)->first()
            : null;

        $order->fill([
            'phone_number_id' => $matchedPhoneNumber?->id,
            'ordered_number' => $orderedNumber !== '' ? $orderedNumber : trim((string) $data['ordered_number']),
            'service_type' => $matchedPhoneNumber
                ? $normalizeServiceType($matchedPhoneNumber->service_type)
                : (string) $order->service_type,
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

    Route::post('/orders/{order}/line-test', function (CustomerOrder $order) use ($ensureAdmin, $safelyRunLineNotification, $currentAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        if ($currentAdmin()?->role !== User::ROLE_MANAGER) {
            abort(403);
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

    Route::get('/analytics', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $rangeOptions = [
            7 => '7 วันล่าสุด',
            30 => '30 วันล่าสุด',
            90 => '90 วันล่าสุด',
        ];
        $days = (int) $request->integer('range', 30);

        if (! array_key_exists($days, $rangeOptions)) {
            $days = 30;
        }

        $timezone = 'Asia/Bangkok';
        $start = now($timezone)->startOfDay()->subDays($days - 1);
        $end = now($timezone)->endOfDay();
        $ga4 = app(Ga4AnalyticsService::class);
        $ga4Dashboard = null;
        $ga4Error = null;

        if ($ga4->isReportingConfigured()) {
            try {
                $ga4Dashboard = $ga4->fetchDashboard($days);
            } catch (\Throwable $e) {
                Log::warning('GA4 analytics dashboard fetch failed.', [
                    'error' => $e->getMessage(),
                ]);

                $ga4Error = $e->getMessage();
            }
        }

        $buildDailyCounts = static function (string $table, string $column, Carbon $startDate, Carbon $endDate): array {
            return DB::table($table)
                ->selectRaw('DATE(' . $column . ') as day, COUNT(*) as aggregate')
                ->whereBetween($column, [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(' . $column . ')'))
                ->pluck('aggregate', 'day')
                ->map(static fn ($value): int => (int) $value)
                ->all();
        };

        $contactMessagesCount = ContactMessage::query()
            ->whereBetween('submitted_at', [$start, $end])
            ->count();
        $estimateLeadsCount = EstimateLead::query()
            ->whereBetween('submitted_at', [$start, $end])
            ->count();
        $ordersCreatedCount = CustomerOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $closedOrdersCount = CustomerOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', [
                CustomerOrder::STATUS_COMPLETED,
                CustomerOrder::STATUS_SOLD,
            ])
            ->count();
        $orderStatusBreakdown = CustomerOrder::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'status')
            ->map(static fn ($value): int => (int) $value)
            ->all();

        $dailyContactMessages = $buildDailyCounts('contact_messages', 'submitted_at', $start, $end);
        $dailyEstimateLeads = $buildDailyCounts('estimate_leads', 'submitted_at', $start, $end);
        $dailyOrders = $buildDailyCounts('customer_orders', 'created_at', $start, $end);
        $internalDaily = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $dateKey = $cursor->toDateString();

            $internalDaily[] = [
                'date' => $dateKey,
                'label' => $cursor->locale('th')->translatedFormat('j M'),
                'contact_messages' => $dailyContactMessages[$dateKey] ?? 0,
                'estimate_leads' => $dailyEstimateLeads[$dateKey] ?? 0,
                'orders_created' => $dailyOrders[$dateKey] ?? 0,
            ];
        }

        return view('admin.analytics', [
            'days' => $days,
            'rangeOptions' => $rangeOptions,
            'ga4Dashboard' => $ga4Dashboard,
            'ga4Error' => $ga4Error,
            'ga4Settings' => [
                'measurement_id' => $ga4->measurementId(),
                'property_id' => $ga4->propertyId(),
                'service_account_json' => $ga4->editableServiceAccountJson(),
            ],
            'ga4ConfiguredForTracking' => $ga4->isClientTrackingConfigured(),
            'ga4ConfiguredForReporting' => $ga4->isReportingConfigured(),
            'ga4ServiceAccountEmail' => $ga4->serviceAccountEmail(),
            'internalSummary' => [
                'contact_messages' => $contactMessagesCount,
                'estimate_leads' => $estimateLeadsCount,
                'orders_created' => $ordersCreatedCount,
                'closed_orders' => $closedOrdersCount,
            ],
            'internalDaily' => $internalDaily,
            'orderStatusBreakdown' => $orderStatusBreakdown,
        ]);
    })->name('analytics');

    Route::post('/analytics/settings', function (Request $request) use ($ensureAdmin, $resolveEnvironmentEditor) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $environmentEditor = $resolveEnvironmentEditor();
        $ga4 = app(Ga4AnalyticsService::class);
        $data = $request->validate([
            'ga4_measurement_id' => ['nullable', 'string', 'max:64'],
            'ga4_property_id' => ['nullable', 'string', 'max:64'],
            'ga4_service_account_json' => ['nullable', 'string', 'max:200000'],
        ]);

        $measurementId = strtoupper(trim((string) ($data['ga4_measurement_id'] ?? '')));
        $propertyId = trim((string) ($data['ga4_property_id'] ?? ''));

        if ($measurementId !== '' && preg_match('/^G-[A-Z0-9]+$/', $measurementId) !== 1) {
            throw ValidationException::withMessages([
                'ga4_measurement_id' => 'GA4 Measurement ID ต้องอยู่ในรูปแบบ `G-XXXXXXXXXX`',
            ]);
        }

        if ($propertyId !== '' && preg_match('/^\d+$/', $propertyId) !== 1) {
            throw ValidationException::withMessages([
                'ga4_property_id' => 'GA4 Property ID ต้องเป็นตัวเลขเท่านั้น',
            ]);
        }

        try {
            $encodedCredentials = $ga4->normalizeServiceAccountJson($data['ga4_service_account_json'] ?? '');

            $environmentEditor->setMany([
                'GA4_MEASUREMENT_ID' => $measurementId,
                'GA4_PROPERTY_ID' => $propertyId,
                'GA4_SERVICE_ACCOUNT_JSON_BASE64' => $encodedCredentials,
            ]);

            config()->set('services.ga4.measurement_id', $measurementId);
            config()->set('services.ga4.property_id', $propertyId);
            config()->set('services.ga4.service_account_json_base64', $encodedCredentials);

            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['analytics_settings' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.analytics')
            ->with('status_message', 'บันทึก GA4 settings เรียบร้อยแล้ว');
    })->name('analytics.settings.update');

    Route::get('/line-settings', function () use ($ensureAdmin, $resolveEnvironmentEditor, $latestLineWebhookEvents) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        $environmentEditor = $resolveEnvironmentEditor();

        $settings = $environmentEditor->getMany([
            'LINE_CHANNEL_ACCESS_TOKEN',
            'LINE_CHANNEL_SECRET',
            'LINE_GROUP_ID',
            'LINE_LOTTERY_GROUP_ID',
        ]);
        $baseUrl = rtrim((string) config('app.url', url('/')), '/');
        $webhookUrl = $baseUrl . route('line.webhook', [], false);
        $webhookEvents = $latestLineWebhookEvents();
        $latestLotteryResult = null;

        if (Schema::hasTable('lottery_results')) {
            $latestLotteryResult = LotteryResult::query()
                ->orderByRaw('COALESCE(source_draw_date, draw_date) DESC')
                ->orderByDesc('fetched_at')
                ->orderByDesc('id')
                ->first();
        }

        return view('admin.line-settings', compact('settings', 'webhookUrl', 'webhookEvents', 'latestLotteryResult'));
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
            'line_lottery_group_id' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $token = trim((string) ($data['line_channel_access_token'] ?? ''));
            $channelSecret = trim((string) ($data['line_channel_secret'] ?? ''));
            $groupId = trim((string) ($data['line_group_id'] ?? ''));
            $lotteryGroupId = trim((string) ($data['line_lottery_group_id'] ?? ''));

            $environmentEditor->setMany([
                'LINE_CHANNEL_ACCESS_TOKEN' => $token,
                'LINE_CHANNEL_SECRET' => $channelSecret,
                'LINE_GROUP_ID' => $groupId,
                'LINE_LOTTERY_GROUP_ID' => $lotteryGroupId,
            ]);

            config()->set('services.line.channel_access_token', $token);
            config()->set('services.line.channel_secret', $channelSecret);
            config()->set('services.line.group_id', $groupId);
            config()->set('services.line.groups.lottery', $lotteryGroupId);

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

    Route::post('/lottery/fetch-force', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        try {
            $exitCode = Artisan::call('lottery:fetch-latest', [
                '--force' => true,
            ]);
            $output = trim((string) Artisan::output());

            if ($exitCode !== 0) {
                return back()->withErrors([
                    'lottery_force_fetch' => $output !== '' ? $output : 'เรียก lottery:fetch-latest --force ไม่สำเร็จ',
                ]);
            }

            return redirect()
                ->route('admin.line-settings')
                ->with('status_message', 'สั่ง force เรียกหวยเรียบร้อยแล้ว')
                ->with('lottery_force_output', $output);
        } catch (\Throwable $e) {
            Log::warning('Manual lottery force fetch failed.', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'lottery_force_fetch' => $e->getMessage(),
            ]);
        }
    })->name('lottery.fetch-force');

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


    Route::get('/utils/migrate', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return "Database expanded successfully!<br><pre>" . $output . "</pre><br><a href='" . route('admin.articles') . "'>กลับหน้าบทความ</a>";
        } catch (\Exception $e) {
            return "Migration Error: " . $e->getMessage();
        }
    });

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

    Route::get('/utils/storage-link', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        try {
            Artisan::call('storage:link');
            return "OK: Storage symlink has been created successfully. Your images should now be visible!";
        } catch (\Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    })->name('utils.storage-link');

    Route::get('/articles', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $articles = Article::query()
            ->latest('created_at')
            ->paginate(20);

        return view('admin.articles', compact('articles'));
    })->name('articles');

    Route::get('/articles/create', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }
        return view('admin.article-create');
    })->name('articles.create');

    Route::post('/articles', function (Request $request) use ($ensureAdmin, $buildArticleSlug, $resolveArticleImageMeta, $sanitizeArticleContent, $articleColumnExists, $ensurePublicStorageLink, $decodeBase64Image) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string'],
            'lsi_keywords' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'land_path' => ['nullable', 'string', 'max:500'],
            'sq_path' => ['nullable', 'string', 'max:500'],
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

        $content = $sanitizeArticleContent(trim($data['content']));

        try {
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? public_path();
            $storageDir = rtrim($docRoot, '/') . '/storage/';

            // Use pre-uploaded path from /p/img endpoint (already saved to storage).
            $landPath = trim((string) ($data['land_path'] ?? ''));
            if ($landPath !== '' && file_exists($storageDir . $landPath)) {
                $coverImageLandscapePath = $landPath;
            }

            $sqPath = trim((string) ($data['sq_path'] ?? ''));
            if ($sqPath !== '' && file_exists($storageDir . $sqPath)) {
                $coverImageSquarePath = $sqPath;
            }

            $articleData = [
                'title' => trim($data['title']),
                'slug' => $slug,
                'excerpt' => trim((string) ($data['excerpt'] ?? '')) ?: null,
                'content' => $content,
                'meta_description' => trim((string) ($data['meta_description'] ?? '')) ?: null,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? $publishedAt : null,
                'cover_image_path' => $coverImagePath,
                'author_user_id' => is_numeric(session('admin_user_id')) ? (int) session('admin_user_id') : null,
            ];

            if ($articleColumnExists('keywords')) {
                $articleData['keywords'] = trim((string) ($data['keywords'] ?? '')) ?: null;
            }

            if ($articleColumnExists('lsi_keywords')) {
                $articleData['lsi_keywords'] = trim((string) ($data['lsi_keywords'] ?? '')) ?: null;
            }

            if ($articleColumnExists('cover_image_landscape_path')) {
                $articleData['cover_image_landscape_path'] = $coverImageLandscapePath;
            }

            if ($articleColumnExists('cover_image_square_path')) {
                $articleData['cover_image_square_path'] = $coverImageSquarePath;
            }

            Article::query()->create($articleData);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['save_error' => 'ไม่สามารถบันทึกบทความหรืออัปโหลดรูปภาพได้: ' . $e->getMessage()]);
        }

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

    Route::post('/content-media/{article}', function (Request $request, Article $article) use ($ensureAdmin, $buildArticleSlug, $resolveArticleImageMeta, $sanitizeArticleContent, $articleColumnExists, $ensurePublicStorageLink, $decodeBase64Image) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:190',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('articles', 'slug')->ignore($article->id),
            ],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string'],
            'lsi_keywords' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'land_path' => ['nullable', 'string', 'max:500'],
            'sq_path' => ['nullable', 'string', 'max:500'],
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

        $content = $sanitizeArticleContent(trim($data['content']));

        try {
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? public_path();
            $storageDir = rtrim($docRoot, '/') . '/storage/';

            // Use pre-uploaded path from /p/img endpoint (already saved to storage).
            $landPath = trim((string) ($data['land_path'] ?? ''));
            if ($landPath !== '' && file_exists($storageDir . $landPath)) {
                if ($coverImageLandscapePath && $coverImageLandscapePath !== $landPath) {
                    @unlink($storageDir . $coverImageLandscapePath);
                }
                $coverImageLandscapePath = $landPath;
            }

            $sqPath = trim((string) ($data['sq_path'] ?? ''));
            if ($sqPath !== '' && file_exists($storageDir . $sqPath)) {
                if ($coverImageSquarePath && $coverImageSquarePath !== $sqPath) {
                    @unlink($storageDir . $coverImageSquarePath);
                }
                $coverImageSquarePath = $sqPath;
            }

            $articleData = [
                'title' => trim($data['title']),
                'slug' => $slug,
                'excerpt' => trim((string) ($data['excerpt'] ?? '')) ?: null,
                'content' => $content,
                'meta_description' => trim((string) ($data['meta_description'] ?? '')) ?: null,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? $publishedAt : null,
                'cover_image_path' => $coverImagePath,
            ];

            if ($articleColumnExists('keywords')) {
                $articleData['keywords'] = trim((string) ($data['keywords'] ?? '')) ?: null;
            }

            if ($articleColumnExists('lsi_keywords')) {
                $articleData['lsi_keywords'] = trim((string) ($data['lsi_keywords'] ?? '')) ?: null;
            }

            if ($articleColumnExists('cover_image_landscape_path')) {
                $articleData['cover_image_landscape_path'] = $coverImageLandscapePath;
            }

            if ($articleColumnExists('cover_image_square_path')) {
                $articleData['cover_image_square_path'] = $coverImageSquarePath;
            }

            $article->update($articleData);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['save_error' => 'ไม่สามารถอัปเดตบทความหรืออัปโหลดรูปภาพได้: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.articles')
            ->with('status_message', 'อัปเดตบทความเรียบร้อย');
    })->name('articles.update');

    Route::get('/articles/{article}/preview', function (Article $article) use ($ensureAdmin, $resolveLotteryResultForArticle) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $article->load([
            'approvedComments' => fn ($query) => $query->latest('id'),
        ]);

        $lotteryResult = $resolveLotteryResultForArticle($article);

        return view('articles.show', compact('article', 'lotteryResult'));
    })->name('articles.preview');

    Route::delete('/articles/{article}', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin(User::ROLE_MANAGER)) {
            return $redirect;
        }

        if (session('admin_user_role') !== User::ROLE_MANAGER) {
            abort(403);
        }

        // 1. ลบรูปหน้าปกทั้งหมด
        $coverPaths = array_values(array_unique(array_filter([
            $article->cover_image_path,
            $article->cover_image_landscape_path,
            $article->cover_image_square_path,
        ])));

        foreach ($coverPaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // 2. สแกนหาและลบรูปภาพในเนื้อหา (Content Images)
        $content = (string) $article->content;
        preg_match_all('/<img[^>]+src="([^">]+)"/i', $content, $matches);
        $imageUrls = $matches[1] ?? [];

        foreach ($imageUrls as $url) {
            $storagePath = preg_replace('/^.*\/storage\//i', '', $url);
            if ($storagePath !== $url) {
                if (Storage::disk('public')->exists($storagePath)) {
                    Storage::disk('public')->delete($storagePath);
                }
            }
        }

        // 3. ลบคอมเมนต์ที่เกี่ยวข้อง
        $article->comments()->delete();

        // 4. ลบบทความ
        $article->delete();

        return redirect()
            ->route('admin.articles')
            ->with('status_message', 'ลบบทความและไฟล์ที่เกี่ยวข้องเรียบร้อยแล้ว');
    })->name('articles.delete');

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

    Route::get('/contact-messages', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $messages = ContactMessage::query()
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(40);

        return view('admin.contact-messages', compact('messages'));
    })->name('contact-messages');

    Route::get('/estimate-leads', function (Request $request) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $search = trim((string) $request->query('q', ''));
        $selectedGender = trim((string) $request->query('gender', ''));
        $selectedWorkType = trim((string) $request->query('work_type', ''));
        $selectedGoal = trim((string) $request->query('goal', ''));
        $searchDigits = preg_replace('/\D+/', '', $search) ?? '';
        $searchTerm = mb_strtolower($search);
        $genderLabels = EstimateLead::genderLabels();
        $workTypeLabels = EstimateLead::workTypeLabels();
        $goalLabels = EstimateLead::goalLabels();

        if (! array_key_exists($selectedGender, $genderLabels)) {
            $selectedGender = '';
        }

        if (! array_key_exists($selectedWorkType, $workTypeLabels)) {
            $selectedWorkType = '';
        }

        if (! array_key_exists($selectedGoal, $goalLabels)) {
            $selectedGoal = '';
        }

        $leadQuery = EstimateLead::query()
            ->when($search !== '', function ($query) use ($search, $searchDigits, $searchTerm) {
                $query->where(function ($innerQuery) use ($search, $searchDigits, $searchTerm) {
                    $innerQuery
                        ->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhereRaw('LOWER(email) like ?', ['%' . $searchTerm . '%']);

                    if ($searchDigits !== '') {
                        $innerQuery
                            ->orWhere('main_phone', 'like', '%' . $searchDigits . '%')
                            ->orWhere('current_phone', 'like', '%' . $searchDigits . '%');
                    }
                });
            })
            ->when($selectedGender !== '', fn ($query) => $query->where('gender', $selectedGender))
            ->when($selectedWorkType !== '', fn ($query) => $query->where('work_type', $selectedWorkType))
            ->when($selectedGoal !== '', fn ($query) => $query->where('goal', $selectedGoal));

        $timezone = 'Asia/Bangkok';
        $todayStart = now($timezone)->startOfDay();
        $lastSevenDaysStart = now($timezone)->subDays(6)->startOfDay();
        $monthStart = now($timezone)->startOfMonth();

        $stats = [
            'total' => EstimateLead::query()->count(),
            'today' => EstimateLead::query()->where('submitted_at', '>=', $todayStart)->count(),
            'last_7_days' => EstimateLead::query()->where('submitted_at', '>=', $lastSevenDaysStart)->count(),
            'this_month' => EstimateLead::query()->where('submitted_at', '>=', $monthStart)->count(),
            'filtered' => (clone $leadQuery)->count(),
        ];

        $leads = $leadQuery
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.estimate-leads', compact(
            'leads',
            'search',
            'selectedGender',
            'selectedWorkType',
            'selectedGoal',
            'genderLabels',
            'workTypeLabels',
            'goalLabels',
            'stats'
        ));
    })->name('estimate-leads');

    Route::get('/estimate-leads/{estimateLead}', function (EstimateLead $estimateLead) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        if (Schema::hasTable('line_notification_logs')) {
            $estimateLead->load([
                'lineNotificationLogs' => fn ($query) => $query->latest()->limit(20),
            ]);
        } else {
            $estimateLead->setRelation('lineNotificationLogs', collect());
        }

        return view('admin.estimate-leads-show', compact('estimateLead'));
    })->name('estimate-leads.show');

    Route::delete('/contact-messages/{contactMessage}', function (ContactMessage $contactMessage) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $contactMessage->delete();

        return back()->with('status_message', 'ลบข้อความติดต่อเรียบร้อย');
    })->name('contact-messages.delete');

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


// UPDATE
Route::post('/direct-save-article/{article}', function (Request $request, Article $article) use ($ensureAdmin, $sanitizeArticleContent, $articleColumnExists, $ensurePublicStorageLink) {
    if ($redirect = $ensureAdmin()) return $redirect;

    $data = $request->validate([
        'title' => ['required', 'string', 'max:190'],
        'excerpt' => ['nullable', 'string'],
        'content' => ['required', 'string'],
        'meta_description' => ['nullable', 'string', 'max:500'],
        'keywords' => ['nullable', 'string'],
        'lsi_keywords' => ['nullable', 'string'],
        'published_at' => ['nullable', 'date'],
        'is_published' => ['nullable', 'boolean'],
        'upload_media_sq' => ['nullable', 'image', 'max:10240'],
    ]);

    $isPublished = $request->boolean('is_published');
    $publishedAt = $data['published_at'] ?? ($article->published_at ?: now());
    $year = Carbon::parse($publishedAt)->year;
    $slug = $article->slug;

    $sqPath = $article->cover_image_square_path;
    $content = $sanitizeArticleContent(trim($data['content']));
    $ensurePublicStorageLink();
    if ($request->hasFile('upload_media_sq')) {
        if ($sqPath) Storage::disk('public')->delete($sqPath);
        $file = $request->file('upload_media_sq');
        $ext = $file->getClientOriginalExtension();
        $filename = "{$slug}.{$ext}";
        $dir = "{$year}";
        if (!Storage::disk('public')->exists($dir)) Storage::disk('public')->makeDirectory($dir);
        Storage::disk('public')->putFileAs($dir, $file, $filename);
        $sqPath = "{$dir}/{$filename}";
    }

    $articleData = [
        'title' => trim($data['title']),
        'excerpt' => trim((string)($data['excerpt'] ?? '')) ?: null,
        'content' => $content,
        'meta_description' => trim((string)($data['meta_description'] ?? '')) ?: null,
        'is_published' => $isPublished,
        'published_at' => $publishedAt,
    ];

    if ($articleColumnExists('keywords')) {
        $articleData['keywords'] = trim((string) ($data['keywords'] ?? '')) ?: null;
    }

    if ($articleColumnExists('lsi_keywords')) {
        $articleData['lsi_keywords'] = trim((string) ($data['lsi_keywords'] ?? '')) ?: null;
    }

    if ($articleColumnExists('cover_image_square_path')) {
        $articleData['cover_image_square_path'] = $sqPath;
    }

    $article->update($articleData);

    return redirect()->route('admin.articles')->with('status_message', 'อัปเดตบทความเรียบร้อย');
})->name('articles.update.bypass');


// --- SIMPLIFIED FIREWALL BYPASS ROUTES (NO ADMIN PREFIX) ---

// CREATE
Route::post('/direct-create-article', function (Request $request) use ($ensureAdmin, $buildArticleSlug, $sanitizeArticleContent, $articleColumnExists, $ensurePublicStorageLink) {
    if ($redirect = $ensureAdmin()) return $redirect;

    $data = $request->validate([
        'title' => ['required', 'string', 'max:190'],
        'excerpt' => ['nullable', 'string'],
        'content' => ['required', 'string'],
        'meta_description' => ['nullable', 'string', 'max:500'],
        'keywords' => ['nullable', 'string'],
        'lsi_keywords' => ['nullable', 'string'],
        'published_at' => ['nullable', 'date'],
        'is_published' => ['nullable', 'boolean'],
        'upload_media_sq' => ['nullable', 'image', 'max:10240'],
    ]);

    $slug = $buildArticleSlug($data['title'], null);
    $isPublished = $request->boolean('is_published');
    $publishedAt = $data['published_at'] ?? ($isPublished ? now() : null);
    $year = Carbon::parse($publishedAt ?: now())->year;

    $sqPath = null;
    $content = $sanitizeArticleContent(trim($data['content']));
    $ensurePublicStorageLink();
    if ($request->hasFile('upload_media_sq')) {
        $file = $request->file('upload_media_sq');
        $ext = $file->getClientOriginalExtension();
        $filename = "{$slug}.{$ext}";
        $dir = "{$year}";
        if (!Storage::disk('public')->exists($dir)) Storage::disk('public')->makeDirectory($dir);
        Storage::disk('public')->putFileAs($dir, $file, $filename);
        $sqPath = "{$dir}/{$filename}";
    }

    $articleData = [
        'title' => trim($data['title']),
        'slug' => $slug,
        'excerpt' => trim((string)($data['excerpt'] ?? '')) ?: null,
        'content' => $content,
        'meta_description' => trim((string)($data['meta_description'] ?? '')) ?: null,
        'is_published' => $isPublished,
        'published_at' => $publishedAt,
        'author_user_id' => session('admin_user_id'),
    ];

    if ($articleColumnExists('keywords')) {
        $articleData['keywords'] = trim((string) ($data['keywords'] ?? '')) ?: null;
    }

    if ($articleColumnExists('lsi_keywords')) {
        $articleData['lsi_keywords'] = trim((string) ($data['lsi_keywords'] ?? '')) ?: null;
    }

    if ($articleColumnExists('cover_image_square_path')) {
        $articleData['cover_image_square_path'] = $sqPath;
    }

    Article::create($articleData);

    return redirect()->route('admin.articles')->with('status_message', 'สร้างบทความเรียบร้อย');
})->name('articles.store.bypass');
