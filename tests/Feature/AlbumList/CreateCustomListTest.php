<?php

use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('lists index page renders with Lists/Index component', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
        );
});

test('user can create a custom list with title and description', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'My Favorites',
            'description' => 'A list of my favorite albums',
        ])
        ->assertRedirect(route('lists.index'));

    expect($user->albumLists()->where('type', 'custom')->first())
        ->title->toBe('My Favorites')
        ->description->toBe('A list of my favorite albums')
        ->type->toBe('custom');
});

test('user can create a custom list with only a title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'No Description List',
        ])
        ->assertRedirect(route('lists.index'));

    expect($user->albumLists()->where('title', 'No Description List')->first())
        ->description->toBeNull()
        ->type->toBe('custom');
});

test('created list appears on the lists overview page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'New Custom List',
        ]);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists.data', fn ($lists) => $lists
                ->each(fn ($list) => $list
                    ->hasAll(['id', 'title', 'albumsCount', 'url'])
                )
            )
            ->where('lists.data.1.title', 'New Custom List')
        );
});

test('validation requires a title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => '',
            'description' => 'Some description',
        ])
        ->assertSessionHasErrors('title');

    expect($user->albumLists()->where('type', 'custom')->count())->toBe(0);
});

test('validation rejects a title longer than 255 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('title');
});

test('validation rejects a description longer than 1000 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'Valid Title',
            'description' => str_repeat('a', 1001),
        ])
        ->assertSessionHasErrors('description');
});

test('created list is always type custom', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'Sneaky List',
            'type' => 'system',
        ]);

    $list = $user->albumLists()->where('title', 'Sneaky List')->first();

    expect($list->type)->toBe('custom');
});

test('unauthenticated users cannot create lists', function () {
    $this->post(route('lists.store'), [
        'title' => 'Unauthorized List',
    ])->assertRedirect(route('login'));

    expect(AlbumList::where('title', 'Unauthorized List')->exists())->toBeFalse();
});
