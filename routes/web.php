<?php

use App\Http\Controllers\AlbumListAlbumController;
use App\Http\Controllers\AlbumListController;
use App\Http\Controllers\AlbumReviewController;
use App\Http\Controllers\Auth\SpotifyAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpotifySearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::get('/auth/spotify/redirect', [SpotifyAuthController::class, 'redirect'])->name('spotify.redirect');
});

Route::get('/auth/spotify/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SpotifyAuthController::class, 'logout'])->name('logout');
    Route::get('/auth/spotify/reconnect', [SpotifyAuthController::class, 'reconnect'])->name('spotify.reconnect');

    Route::get('/', HomeController::class)->name('home');
    Route::get('/lists', [AlbumListController::class, 'index'])->name('lists.index');
    Route::get('/lists/search', [AlbumListController::class, 'search'])->name('lists.search');
    Route::post('/lists', [AlbumListController::class, 'store'])->name('lists.store');
    Route::get('/lists/{listSlug}', [AlbumListController::class, 'show'])
        ->where('listSlug', '[a-z0-9-]+')
        ->name('lists.show');
    Route::post('/lists/{albumList:id}/refresh', [AlbumListController::class, 'refresh'])->name('lists.refresh');
    Route::patch('/lists/{albumList:id}/sort', [AlbumListController::class, 'updateSort'])->name('lists.sort.update');
    Route::put('/lists/{albumList:id}', [AlbumListController::class, 'update'])->name('lists.update');
    Route::delete('/lists/{albumList:id}', [AlbumListController::class, 'destroy'])->name('lists.destroy');

    Route::post('/lists/{albumList:id}/albums', [AlbumListAlbumController::class, 'store'])->name('lists.albums.store');
    Route::put('/lists/{albumList:id}/albums/reorder', [AlbumListAlbumController::class, 'reorder'])->name('lists.albums.reorder');
    Route::post('/lists/{albumList:id}/albums/{album}/move', [AlbumListAlbumController::class, 'move'])->name('lists.albums.move');
    Route::patch('/lists/{albumList:id}/albums/{album}', [AlbumListAlbumController::class, 'update'])->name('lists.albums.update');
    Route::delete('/lists/{albumList:id}/albums/{album}', [AlbumListAlbumController::class, 'destroy'])->name('lists.albums.destroy');
    Route::post('/lists/{albumList:id}/albums/{album}/review', [AlbumReviewController::class, 'store'])->name('lists.albums.review.store');
    Route::delete('/lists/{albumList:id}/albums/{album}/review', [AlbumReviewController::class, 'destroy'])->name('lists.albums.review.destroy');

    Route::get('/spotify/search/albums', [SpotifySearchController::class, 'albums'])->name('spotify.search.albums');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/tokens', [SettingsController::class, 'createToken'])->name('settings.tokens.store');
    Route::delete('/settings/tokens/{token}', [SettingsController::class, 'destroyToken'])->name('settings.tokens.destroy');
});
