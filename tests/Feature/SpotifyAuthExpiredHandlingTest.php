<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

test('a JSON request gets a 401 with a reconnect url when the Spotify token is invalid', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $this->actingAs($user)
        ->getJson('/spotify/search/albums?q=radiohead')
        ->assertUnauthorized()
        ->assertJsonPath('reconnect_url', route('spotify.reconnect'));
});

test('an Inertia request is redirected to reconnect when the Spotify token is invalid', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    $version = app(HandleInertiaRequests::class)->version(Request::create('/'));

    $this->actingAs($user)
        ->get('/spotify/search/albums?q=radiohead', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
        ])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('spotify.reconnect'));
});

test('a transient Spotify failure returns a 503 instead of forcing a reconnect', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'server_error'], 500),
    ]);

    $this->actingAs($user)
        ->getJson('/spotify/search/albums?q=radiohead')
        ->assertStatus(503)
        ->assertJsonMissingPath('reconnect_url');
});
