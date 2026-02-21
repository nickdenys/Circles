<?php

use App\Models\AlbumList;
use App\Models\User;

test('list detail page renders the Lists/Show inertia component', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'My Favorites']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Lists/Show')
            ->where('list.title', 'My Favorites')
        );
});

test('list detail page passes the list description', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'description' => 'A collection of my favorite albums',
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.description', 'A collection of my favorite albums')
        );
});

test('list detail page passes null description when not set', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'description' => null,
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('list.description', null)
        );
});

test('list detail page passes album count', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.albumsCount', 0)
        );
});

test('list detail page passes list type for custom lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'type' => 'custom']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.type', 'custom')
        );
});

test('edit and delete buttons are rendered for custom lists', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="edit-list-button"')
        ->toContain('id="delete-list-button"')
        ->toContain("list.type === 'custom'");
});

test('edit and delete buttons are hidden for system lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.type', 'system')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain("list.type === 'custom'");
});

test('users cannot view another users list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertForbidden();
});

test('unauthenticated users cannot access the list detail page', function () {
    $list = AlbumList::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->get(route('lists.show', $list))
        ->assertRedirect(route('login'));
});

test('list detail page shows empty state when no albums', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)->toContain('No albums yet. Search for albums above to add them to this list.');
});

test('list detail page uses Head component with list title', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('<Head title={list.title}')
        ->toContain("from '@inertiajs/react'");
});
