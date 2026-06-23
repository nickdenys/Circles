<?php

use App\Exceptions\SpotifyAuthExpired;
use App\Exceptions\SpotifyUnavailable;
use App\Models\User;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\Http;

test('an invalid_grant refresh discards the stored tokens and raises SpotifyAuthExpired', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    expect(fn () => (new SpotifyService($user))->getAlbum('does-not-matter'))
        ->toThrow(SpotifyAuthExpired::class);

    expect($user->fresh())
        ->spotify_token->toBeNull()
        ->spotify_refresh_token->toBeNull()
        ->spotify_token_expires_at->toBeNull();
});

test('a transient refresh failure raises SpotifyUnavailable and keeps the tokens', function () {
    $user = User::factory()->withExpiredToken()->create();
    $refreshToken = $user->spotify_refresh_token;

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'server_error'], 500),
    ]);

    expect(fn () => (new SpotifyService($user))->getAlbum('does-not-matter'))
        ->toThrow(SpotifyUnavailable::class);

    expect($user->fresh())
        ->spotify_refresh_token->toBe($refreshToken);
});

test('a disconnected user fails fast without contacting Spotify', function () {
    $user = User::factory()->disconnectedFromSpotify()->create();

    Http::fake();

    expect(fn () => (new SpotifyService($user))->getAlbum('does-not-matter'))
        ->toThrow(SpotifyAuthExpired::class);

    Http::assertNothingSent();
});

test('successful refresh updates the user tokens', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
        ]),
        'api.spotify.com/v1/albums/*' => Http::response([], 404),
    ]);

    (new SpotifyService($user))->getAlbum('whatever');

    expect($user->fresh())
        ->spotify_token->toBe('new-access-token')
        ->spotify_refresh_token->toBe('new-refresh-token');
});
