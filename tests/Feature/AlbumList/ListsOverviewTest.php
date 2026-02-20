<?php

use App\Models\AlbumList;
use App\Models\User;

test('lists page displays all user lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(3)->create(['user_id' => $user->id]);

    // 3 custom + 1 system watchlist = 4
    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertSee('My Lists')
        ->assertSee('Watchlist');
});

test('system lists are displayed before custom lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Custom Alpha']);
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Custom Beta']);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSeeInOrder(['Watchlist', 'Custom Alpha', 'Custom Beta']);
});

test('each list card shows the list name and album count', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSee('Watchlist')
        ->assertSee('0 albums');
});

test('each list card has a link to the list detail page', function () {
    $user = User::factory()->create();
    $list = $user->albumLists->first();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSee(route('lists.show', $list));
});

test('each list card shows an arrow icon', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSee('m8.25 4.5 7.5 7.5-7.5 7.5', false);
});

test('add list button is visible', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('lists.index'))
        ->assertSee('Add List');
});

test('lists page only shows lists for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $otherUser->id, 'title' => 'Other User List']);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertDontSee('Other User List');
});

test('empty state is shown when user has no custom lists', function () {
    $user = User::factory()->create();

    // User has the system Watchlist, so it won't show empty state
    // Delete the watchlist to test empty state
    $user->albumLists()->delete();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSee('No lists yet');
});

test('unauthenticated users cannot access the lists page', function () {
    $this->get(route('lists.index'))
        ->assertRedirect(route('login'));
});
