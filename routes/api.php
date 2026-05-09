<?php

use App\Http\Controllers\Api\TarotReadingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(ApiTokenAuth::class)->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Articles Management ---
    // Read-only access for all authenticated users (Staff, Admin, Manager)
    Route::get('articles', [ArticleController::class, 'index'])->name('api.articles.index');
    Route::get('articles/{article}', [ArticleController::class, 'show'])->name('api.articles.show');
    Route::get('articles/{article}/preview-url', [ArticleController::class, 'previewUrl'])->name('api.articles.preview-url');

    // Write access restricted to Admin and Manager
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('articles', [ArticleController::class, 'store'])->name('api.articles.store');
        Route::match(['put', 'patch'], 'articles/{article}', [ArticleController::class, 'update'])->name('api.articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('api.articles.destroy');
        Route::post('articles/import-json', [ArticleController::class, 'importJson'])->name('api.articles.import-json');
        Route::post('articles/{article}/share', [ArticleController::class, 'share'])->name('api.articles.share');
    });

    // --- User Management (Manager Only) ---
    Route::middleware('role:manager')->group(function () {
        Route::get('users', [\App\Http\Controllers\Api\UserController::class, 'index'])->name('api.users.index');
        Route::post('users', [\App\Http\Controllers\Api\UserController::class, 'store'])->name('api.users.store');
        Route::get('users/{user}', [\App\Http\Controllers\Api\UserController::class, 'show'])->name('api.users.show');
        Route::match(['put', 'patch'], 'users/{user}', [\App\Http\Controllers\Api\UserController::class, 'update'])->name('api.users.update');
        Route::delete('users/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy'])->name('api.users.destroy');
    });
});

Route::middleware('throttle:tarot-ai')->group(function (): void {
    Route::post('/tarot/reading', TarotReadingController::class);
});
