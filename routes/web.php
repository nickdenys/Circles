<?php

use App\Http\Controllers\AlbumListController;
use App\Http\Controllers\Auth\SpotifyAuthController;
use App\Http\Controllers\SpotifySearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::get('/auth/spotify/redirect', [SpotifyAuthController::class, 'redirect'])->name('spotify.redirect');
    Route::get('/auth/spotify/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SpotifyAuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => view('home'))->name('home');
    Route::get('/lists', [AlbumListController::class, 'index'])->name('lists.index');
    Route::post('/lists', [AlbumListController::class, 'store'])->name('lists.store');
    Route::get('/lists/{albumList}', [AlbumListController::class, 'show'])->name('lists.show');
    Route::put('/lists/{albumList}', [AlbumListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{albumList}', [AlbumListController::class, 'destroy'])->name('lists.destroy');

    Route::get('/spotify/search/albums', [SpotifySearchController::class, 'albums'])->name('spotify.search.albums');
});
