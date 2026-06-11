<?php

use App\Models\AlbumList;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('system lists are created automatically when a new user registers', function () {
    $user = User::factory()->create();

    expect($user->albumLists)->toHaveCount(2);
    expect($user->albumLists->firstWhere('type', 'system'))
        ->title->toBe('Listen Later');
    expect($user->albumLists->firstWhere('type', 'reviewed'))
        ->title->toBe('Reviewed')
        ->sort->toBe('added')
        ->direction->toBe('desc');
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

    expect($user->albumLists)->toHaveCount(2);
    expect($user->albumLists->pluck('type')->sort()->values()->all())
        ->toEqual(['reviewed', 'system']);
});

test('watchlist is not duplicated on subsequent logins', function () {
    $user = User::factory()->create(['spotify_id' => 'spotify_existing']);

    expect($user->albumLists)->toHaveCount(2);

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

    expect($user->fresh()->albumLists)->toHaveCount(2);
});

test('ensureSystemLists is idempotent', function () {
    $user = User::factory()->create();

    expect($user->albumLists()->count())->toBe(2);

    $user->ensureSystemLists();
    $user->ensureSystemLists();

    expect($user->albumLists()->count())->toBe(2);
    expect($user->albumLists()->where('type', 'system')->count())->toBe(1);
    expect($user->albumLists()->where('type', 'reviewed')->count())->toBe(1);
});

test('system lists are flagged with type system', function () {
    $list = AlbumList::factory()->system()->create();

    expect($list->type)->toBe('system');
    expect($list->isSystem())->toBeTrue();
});

test('the Reviewed list is flagged with type reviewed', function () {
    $list = User::factory()->create()->reviewedList;

    expect($list->type)->toBe('reviewed');
    expect($list->isReviewed())->toBeTrue();
    expect($list->isSystem())->toBeFalse();
    expect($list->isLocked())->toBeTrue();
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

    // 3 custom + Listen Later (system) + Reviewed = 5
    expect($user->fresh()->albumLists)->toHaveCount(5);
});

test('new users get a default description on Listen Later and Reviewed', function () {
    $user = User::factory()->create();

    $listenLater = $user->listenLaterList;
    $reviewed = $user->reviewedList;

    expect($listenLater->description)->toBe(AlbumList::LISTEN_LATER_DESCRIPTION);
    expect($reviewed->description)->toBe(AlbumList::REVIEWED_DESCRIPTION);
});

test('ensureSystemLists does not overwrite descriptions on lists that already exist', function () {
    $user = User::factory()->create();

    $user->listenLaterList->update(['description' => 'My own note']);
    $user->reviewedList->update(['description' => 'Custom']);

    $user->ensureSystemLists();

    expect($user->listenLaterList->fresh()->description)->toBe('My own note');
    expect($user->reviewedList->fresh()->description)->toBe('Custom');
});
