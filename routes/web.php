<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

// Google OAuth routes
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Minimal login route placeholder to satisfy middleware redirects
Route::get('/login', function () {
    return response()->json(['message' => 'login route (web placeholder)'], 401);
})->name('login');

Route::fallback(function () {
    return file_get_contents(public_path('index.html'));
});
