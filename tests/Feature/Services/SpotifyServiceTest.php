<?php

use App\Exceptions\SpotifyAuthExpired;
use App\Models\User;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\Http;

test('refresh failure raises SpotifyAuthExpired', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    expect(fn () => (new SpotifyService($user))->getAlbum('does-not-matter'))
        ->toThrow(SpotifyAuthExpired::class);
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
