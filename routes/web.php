<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('home');

Route::get('/evaluate', function () {
    return view('evaluate');
})->name('evaluate');

Route::get('/evaluateBadNumber', function () {
    return view('evaluate-bad-number');
})->name('evaluate.bad');

Route::get('/tiers', function () {
    return view('tiers');
})->name('tiers');
