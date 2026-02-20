<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('each album card has a remove button', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee('remove-album-button', false)
        ->assertSee('Remove from list');
});

test('clicking remove opens a confirmation modal', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee('remove-album-modal', false)
        ->assertSee('Remove Album')
        ->assertSee('Are you sure you want to remove')
        ->assertSee('Cancel')
        ->assertSee('Confirm');
});

test('confirming removal removes the album from the list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->delete(route('lists.albums.destroy', [$list, $album]))
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh()->albums)->toHaveCount(0);
});

test('album record is not deleted from the database after removal from list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->delete(route('lists.albums.destroy', [$list, $album]));

    expect(Album::find($album->id))->not->toBeNull();
});

test('album is removed from the correct list only', function () {
    $user = User::factory()->create();
    $list1 = AlbumList::factory()->create(['user_id' => $user->id]);
    $list2 = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list1->albums()->attach($album->id, ['position' => 1]);
    $list2->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->delete(route('lists.albums.destroy', [$list1, $album]));

    expect($list1->fresh()->albums)->toHaveCount(0);
    expect($list2->fresh()->albums)->toHaveCount(1);
});

test('list updates immediately after removal', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['title' => 'Album To Remove']);

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->delete(route('lists.albums.destroy', [$list, $album]));

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertDontSee('Album To Remove')
        ->assertSee('0 albums');
});

test('user cannot remove albums from another users list', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $owner->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($otherUser)
        ->delete(route('lists.albums.destroy', [$list, $album]))
        ->assertForbidden();

    expect($list->fresh()->albums)->toHaveCount(1);
});

test('unauthenticated user cannot remove albums', function () {
    $list = AlbumList::factory()->create();
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->delete(route('lists.albums.destroy', [$list, $album]))
        ->assertRedirect(route('login'));
});

test('remove button has album id and title data attributes', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['title' => 'Test Album Title']);

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee('data-album-id="'.$album->id.'"', false)
        ->assertSee('data-album-title="Test Album Title"', false);
});

test('remove modal form contains delete method and csrf token', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $response = $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('name="_method" value="DELETE"');
    expect($content)->toContain('id="remove-album-form"');
});
