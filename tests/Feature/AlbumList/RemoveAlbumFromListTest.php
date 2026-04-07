<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumListAlbum;
use App\Models\User;

test('each album card has a remove button', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('remove-album-button')
        ->toContain('Remove from list');
});

test('remove button is present in the album card component', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('remove-album-button')
        ->toContain('Trash2');
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
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 0)
            ->where('list.albumsCount', 0)
        );
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
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('data-album-id={album.id}')
        ->toContain('data-album-title={album.title}');
});

test('removing an album hard-deletes the pivot row and its note', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1, 'note' => 'Will be deleted']);

    $pivotId = AlbumListAlbum::query()
        ->where('album_list_id', $list->id)
        ->where('album_id', $album->id)
        ->value('id');

    $this->actingAs($user)
        ->delete(route('lists.albums.destroy', [$list, $album]));

    expect(AlbumListAlbum::find($pivotId))->toBeNull();
    expect(AlbumListAlbum::query()
        ->where('album_list_id', $list->id)
        ->where('album_id', $album->id)
        ->exists()
    )->toBeFalse();
});

test('album card passes album data for removal', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['title' => 'Test Album Title']);

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.id', $album->id)
            ->where('albums.data.0.title', 'Test Album Title')
        );
});
