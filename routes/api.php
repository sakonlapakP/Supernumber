<?php

use App\Http\Controllers\Api\TarotReadingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('articles', ArticleController::class);
});

Route::middleware('throttle:tarot-ai')->group(function (): void {
    Route::post('/tarot/reading', TarotReadingController::class);
});
