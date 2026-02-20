<?php

use App\Models\AlbumList;
use App\Models\User;

test('list detail page displays the list title', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'My Favorites']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee('My Favorites');
});

test('list detail page displays the list description', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'description' => 'A collection of my favorite albums',
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('A collection of my favorite albums');
});

test('list detail page hides description when not set', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'description' => null,
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful();
});

test('list detail page shows album count metadata', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('0 albums');
});

test('edit and delete buttons are shown for custom lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'type' => 'custom']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('Edit')
        ->assertSee('Delete');
});

test('edit and delete buttons are hidden for system lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertDontSee('id="edit-list-button"', false)
        ->assertDontSee('id="delete-list-button"', false);
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
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('No albums yet');
});

test('list detail page uses the list title as the page title', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Rock Classics']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('<title>Rock Classics - ', false);
});
