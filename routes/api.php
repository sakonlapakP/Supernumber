<?php

use App\Http\Controllers\Api\TarotReadingController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:tarot-ai')->group(function (): void {
    Route::post('/tarot/reading', TarotReadingController::class);
});
