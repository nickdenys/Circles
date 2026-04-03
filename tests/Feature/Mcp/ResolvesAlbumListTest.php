<?php

use App\Mcp\Concerns\ResolvesAlbumList;
use App\Models\AlbumList;
use App\Models\User;

// Anonymous class to test the trait
beforeEach(function () {
    $this->resolver = new class
    {
        use ResolvesAlbumList;

        public function resolve(User $user, ?string $identifier): ?AlbumList
        {
            return $this->resolveList($user, $identifier);
        }
    };
});

test('returns system listen later list when identifier is null', function () {
    $user = User::factory()->create();

    $list = $this->resolver->resolve($user, null);

    expect($list)
        ->not->toBeNull()
        ->title->toBe('Listen Later')
        ->type->toBe('system');
});

test('finds list by numeric id with ownership check', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $result = $this->resolver->resolve($user, (string) $list->id);

    expect($result)
        ->not->toBeNull()
        ->id->toBe($list->id);
});

test('returns null for numeric id owned by another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);

    $result = $this->resolver->resolve($user, (string) $list->id);

    expect($result)->toBeNull();
});

test('finds list by case-insensitive title match', function () {
    $user = User::factory()->create();
    AlbumList::factory()->create([
        'user_id' => $user->id,
        'title' => 'My Favorites',
    ]);

    $result = $this->resolver->resolve($user, 'my favorites');

    expect($result)
        ->not->toBeNull()
        ->title->toBe('My Favorites');
});

test('returns null when no list matches the identifier', function () {
    $user = User::factory()->create();

    $result = $this->resolver->resolve($user, 'nonexistent list');

    expect($result)->toBeNull();
});
