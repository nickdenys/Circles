<?php

use App\Models\AlbumList;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('watchlist is created automatically when a new user registers', function () {
    $user = User::factory()->create();

    expect($user->albumLists)->toHaveCount(1);
    expect($user->albumLists->first())
        ->title->toBe('Watchlist')
        ->type->toBe('system');
});

test('watchlist is created via spotify oauth callback', function () {
    $spotifyUser = (new SocialiteUser)->setRaw([
        'id' => 'spotify_new_user',
        'display_name' => 'New User',
        'email' => 'new@example.com',
        'images' => [],
    ])->map([
        'id' => 'spotify_new_user',
        'name' => 'New User',
        'email' => 'new@example.com',
        'avatar' => null,
    ]);

    $spotifyUser->token = 'fake-token';
    $spotifyUser->refreshToken = 'fake-refresh';
    $spotifyUser->expiresIn = 3600;

    Socialite::fake('spotify', $spotifyUser);

    $this->get(route('spotify.callback'));

    $user = User::where('spotify_id', 'spotify_new_user')->first();

    expect($user->albumLists)->toHaveCount(1);
    expect($user->albumLists->first())
        ->title->toBe('Watchlist')
        ->type->toBe('system');
});

test('watchlist is not duplicated on subsequent logins', function () {
    $user = User::factory()->create(['spotify_id' => 'spotify_existing']);

    expect($user->albumLists)->toHaveCount(1);

    $spotifyUser = (new SocialiteUser)->setRaw([
        'id' => 'spotify_existing',
        'display_name' => 'Existing User',
        'email' => 'existing@example.com',
        'images' => [],
    ])->map([
        'id' => 'spotify_existing',
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'avatar' => null,
    ]);

    $spotifyUser->token = 'fake-token';
    $spotifyUser->refreshToken = 'fake-refresh';
    $spotifyUser->expiresIn = 3600;

    Socialite::fake('spotify', $spotifyUser);

    $this->get(route('spotify.callback'));

    expect($user->fresh()->albumLists)->toHaveCount(1);
});

test('system lists are flagged with type system', function () {
    $list = AlbumList::factory()->system()->create();

    expect($list->type)->toBe('system');
    expect($list->isSystem())->toBeTrue();
});

test('custom lists are flagged with type custom', function () {
    $list = AlbumList::factory()->create();

    expect($list->type)->toBe('custom');
    expect($list->isSystem())->toBeFalse();
});

test('album list belongs to a user', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    expect($list->user->id)->toBe($user->id);
});

test('user has many album lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(3)->create(['user_id' => $user->id]);

    // 3 custom + 1 system watchlist = 4
    expect($user->fresh()->albumLists)->toHaveCount(4);
});
