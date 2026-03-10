<?php

use App\Models\PhoneNumberStatusLog;
use App\Models\PhoneNumber;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Services\LineOrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    if ($requiredRole !== null && $user->role !== $requiredRole) {
        abort(403);
    }

    return null;
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

Route::get('/', function () {
    $numbers = PhoneNumber::query()
        ->available()
        ->where('network_code', 'true_dtac')
        ->inRandomOrder()
        ->limit(100)
        ->get();

    return view('index', compact('numbers'));
})->name('home');

Route::get('/numbers', function () {
    $search = trim((string) request('q', ''));
    $searchDigits = preg_replace('/[^0-9]/', '', $search);
    $selectedPlan = trim((string) request('plan', ''));
    $positionPattern = PhoneNumber::buildSearchPattern(request()->query());

    $baseQuery = PhoneNumber::query()
        ->available()
        ->where('network_code', 'true_dtac');

    $plans = collect(PhoneNumber::packageLabels());

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

    return view('numbers', compact('numbers', 'plans', 'search', 'selectedPlan', 'positionPattern'));
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

    return view('articles.show', compact('article'));
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

Route::get('/evaluate', function () {
    return view('evaluate');
})->name('evaluate');

Route::get('/evaluateBadNumber', function () {
    return view('evaluate-bad-number');
})->name('evaluate.bad');

Route::get('/tiers', function () {
    return view('tiers');
})->name('tiers');

Route::get('/good-number', function () {
    return view('good-number');
})->name('good-number');

Route::get('/book', function () {
    return view('book');
})->name('book');

Route::post('/book/save-step2', function (Request $request) {
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

    $slipPath = $request->file('payment_slip')->store('payment-slips', 'public');

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
        'payment_slip_path' => $slipPath,
        'status' => 'submitted',
    ]);
    $order->save();

    if ($isNewOrder) {
        app(LineOrderNotifier::class)->sendOrderSubmitted($order);
    }

    return response()->json([
        'ok' => true,
        'order_id' => $order->id,
        'message' => 'บันทึกคำสั่งซื้อเรียบร้อยแล้ว',
    ]);
})->name('book.save-step2');

Route::post('/book', function (Request $request) {
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

    $slipPath = $order->payment_slip_path;
    if ($request->hasFile('payment_slip')) {
        $slipPath = $request->file('payment_slip')->store('payment-slips', 'public');
    }
    if (! $slipPath) {
        return back()
            ->withErrors(['payment_slip' => 'โปรดแนบหลักฐานการโอนเงินเพื่อสั่งซื้อเบอร์'])
            ->withInput();
    }

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
        'payment_slip_path' => $slipPath,
        'status' => 'submitted',
    ]);
    $order->save();

    if ($isNewOrder) {
        app(LineOrderNotifier::class)->sendOrderSubmitted($order);
    }

    return redirect()
        ->route('book', [
            'number' => $data['ordered_number'],
            'package' => (int) $data['selected_package'],
        ])
        ->with('status_message', 'บันทึกคำสั่งซื้อเรียบร้อยแล้ว เจ้าหน้าที่จะติดต่อกลับโดยเร็วที่สุด');
})->name('book.submit');

Route::prefix('admin')->name('admin.')->group(function () use ($currentAdmin, $ensureAdmin, $buildArticleSlug) {
    Route::get('/login', function () use ($currentAdmin) {
        if ($currentAdmin()) {
            return redirect()->route('admin.numbers');
        }

        return view('admin.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', trim($credentials['username']))
            ->first();

        if ($user && $user->canAccessAdminPanel() && Hash::check($credentials['password'], $user->password)) {
            $request->session()->regenerate();
            $request->session()->put([
                'admin_authenticated' => true,
                'admin_user_id' => $user->id,
                'admin_user_name' => $user->name,
                'admin_user_role' => $user->role,
            ]);

            return redirect()->route('admin.numbers');
        }

        return back()
            ->withInput($request->except('password'))
            ->withErrors(['username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
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

    Route::get('/numbers', function () use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        $numbers = PhoneNumber::query()
            ->orderByDesc('id')
            ->paginate(50);

        return view('admin.numbers', compact('numbers'));
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

    Route::get('/orders/{order}', function (CustomerOrder $order) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        return view('admin.orders-show', compact('order'));
    })->name('orders.show');

    Route::get('/orders/{order}/edit', function (CustomerOrder $order) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        return view('admin.orders-edit', compact('order'));
    })->name('orders.edit');

    Route::put('/orders/{order}', function (Request $request, CustomerOrder $order) use ($ensureAdmin) {
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

        if ($request->hasFile('payment_slip')) {
            $order->payment_slip_path = $request->file('payment_slip')->store('payment-slips', 'public');
        }

        $orderedNumber = $digitsOnly($data['ordered_number']);
        $currentPhone = $digitsOnly($data['current_phone'] ?? null);
        $zipcode = $digitsOnly($data['zipcode'] ?? null);

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

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status_message', 'อัปเดตคำสั่งซื้อเรียบร้อยแล้ว');
    })->name('orders.update');

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

    Route::post('/articles', function (Request $request) use ($ensureAdmin, $buildArticleSlug) {
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

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('article-covers', 'public');
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

    Route::put('/articles/{article}', function (Request $request, Article $article) use ($ensureAdmin, $buildArticleSlug) {
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

        $coverImagePath = $article->cover_image_path;

        if ($request->hasFile('cover_image')) {
            if ($coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
            }

            $coverImagePath = $request->file('cover_image')->store('article-covers', 'public');
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
        ]);

        return redirect()
            ->route('admin.articles')
            ->with('status_message', 'อัปเดตบทความเรียบร้อย');
    })->name('articles.update');

    Route::post('/articles/{article}/cover/remove', function (Article $article) use ($ensureAdmin) {
        if ($redirect = $ensureAdmin()) {
            return $redirect;
        }

        if ($article->cover_image_path) {
            Storage::disk('public')->delete($article->cover_image_path);
        }

        $article->update([
            'cover_image_path' => null,
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

        if ($article->cover_image_path) {
            Storage::disk('public')->delete($article->cover_image_path);
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

        if ($phoneNumber->normalized_package_price === null) {
            return back()
                ->withInput()
                ->with('error_message', 'ไม่สามารถ hold ได้: ต้องเป็นเบอร์รายเดือนเท่านั้น');
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
