<?php

use App\Http\Controllers\Auth\SpotifyAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::get('/auth/spotify/redirect', [SpotifyAuthController::class, 'redirect'])->name('spotify.redirect');
    Route::get('/auth/spotify/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SpotifyAuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => view('home'))->name('home');
    Route::get('/lists', fn () => view('lists.index'))->name('lists.index');
});
