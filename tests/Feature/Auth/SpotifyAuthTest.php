<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->spotifyUser = (new SocialiteUser)->setRaw([
        'id' => 'spotify_123',
        'display_name' => 'Test User',
        'email' => 'test@example.com',
        'images' => [['url' => 'https://example.com/avatar.jpg']],
    ])->map([
        'id' => 'spotify_123',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $this->spotifyUser->token = 'fake-access-token';
    $this->spotifyUser->refreshToken = 'fake-refresh-token';
    $this->spotifyUser->expiresIn = 3600;
});

test('unauthenticated users are redirected to login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('login page is accessible to guests', function () {
    $this->get('/login')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));
});

test('authenticated users are redirected away from login page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/login')
        ->assertRedirect('/');
});

test('spotify redirect sends user to spotify authorization', function () {
    Socialite::fake('spotify');

    $this->get(route('spotify.redirect'))->assertRedirect();
});

test('spotify callback creates a new user on first login', function () {
    Socialite::fake('spotify', $this->spotifyUser);

    $this->get(route('spotify.callback'))
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'spotify_id' => 'spotify_123',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);
});

test('spotify callback updates existing user on subsequent login', function () {
    $existingUser = User::factory()->create([
        'spotify_id' => 'spotify_123',
        'name' => 'Old Name',
    ]);

    Socialite::fake('spotify', $this->spotifyUser);

    $this->get(route('spotify.callback'))
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();
    expect($existingUser->fresh())
        ->name->toBe('Test User')
        ->email->toBe('test@example.com');
    expect(User::count())->toBe(1);
});

test('allowlisted spotify id is allowed to sign up', function () {
    Config::set('app.signup_allowlist', ['spotify_123']);

    Socialite::fake('spotify', $this->spotifyUser);

    $this->get(route('spotify.callback'))
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['spotify_id' => 'spotify_123']);
});

test('non-allowlisted spotify id is rejected with a flash error', function () {
    Config::set('app.signup_allowlist', ['someone_else']);

    Socialite::fake('spotify', $this->spotifyUser);

    $this->get(route('spotify.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Your Spotify account isn\'t authorized to use Circles.');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['spotify_id' => 'spotify_123']);
});

test('spotify tokens are stored securely', function () {
    Socialite::fake('spotify', $this->spotifyUser);

    $this->get(route('spotify.callback'));

    $user = User::where('spotify_id', 'spotify_123')->first();

    expect($user->spotify_token)->toBe('fake-access-token');
    expect($user->spotify_refresh_token)->toBe('fake-refresh-token');
    expect($user->spotify_token_expires_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('user is remembered after login', function () {
    Socialite::fake('spotify', $this->spotifyUser);

    $this->get(route('spotify.callback'));

    $user = User::where('spotify_id', 'spotify_123')->first();
    expect($user->remember_token)->not->toBeNull();
});

test('user model correctly detects expired spotify token', function () {
    $user = User::factory()->create();
    expect($user->isSpotifyTokenExpired())->toBeFalse();

    $expiredUser = User::factory()->withExpiredToken()->create();
    expect($expiredUser->isSpotifyTokenExpired())->toBeTrue();
});
