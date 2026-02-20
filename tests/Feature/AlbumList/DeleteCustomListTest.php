<?php

use App\Models\AlbumList;
use App\Models\User;

test('delete button is shown on custom list detail page', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('delete-list-button', escape: false)
        ->assertSee('Delete');
});

test('delete button opens a confirmation modal with are you sure text', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('delete-list-modal', escape: false)
        ->assertSee('Are you sure?')
        ->assertSee('Cancel')
        ->assertSee('Confirm');
});

test('delete modal form submits to the destroy route', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee(route('lists.destroy', $list));
});

test('user can delete a custom list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('lists.destroy', $list))
        ->assertRedirect(route('lists.index'));

    $this->assertDatabaseMissing('album_lists', ['id' => $list->id]);
});

test('system lists cannot be deleted', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->for($user)->create([
        'title' => 'Watchlist',
    ]);

    $this->actingAs($user)
        ->delete(route('lists.destroy', $list))
        ->assertForbidden();

    $this->assertDatabaseHas('album_lists', ['id' => $list->id]);
});

test('delete modal is not shown for system lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertDontSee('delete-list-modal')
        ->assertDontSee('Delete List');
});

test('users cannot delete lists belonging to other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = AlbumList::factory()->for($owner)->create();

    $this->actingAs($other)
        ->delete(route('lists.destroy', $list))
        ->assertForbidden();

    $this->assertDatabaseHas('album_lists', ['id' => $list->id]);
});

test('unauthenticated users cannot delete lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->delete(route('lists.destroy', $list))
        ->assertRedirect(route('login'));

    $this->assertDatabaseHas('album_lists', ['id' => $list->id]);
});
