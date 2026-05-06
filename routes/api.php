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
    
    Route::post('articles/import-json', [ArticleController::class, 'importJson']);
    Route::apiResource('articles', ArticleController::class);
});

Route::middleware('throttle:tarot-ai')->group(function (): void {
    Route::post('/tarot/reading', TarotReadingController::class);
});
