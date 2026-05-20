<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Serve the main SPA (index.html will be served from public/)
Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

// Google OAuth routes
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Fallback to SPA for all other routes
Route::fallback(function () {
    return file_get_contents(public_path('index.html'));
});
