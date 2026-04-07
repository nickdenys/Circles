<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('each album card has a move to list button', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('move-album-button')
        ->toContain('Move to list');
});

test('move button is present in the album card component', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('move-album-button')
        ->toContain('ArrowRightLeft');
});

test('moving an album removes it from the source list and adds it to the destination list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertRedirect(route('lists.show', $sourceList));

    expect($sourceList->fresh()->albums)->toHaveCount(0);
    expect($destList->fresh()->albums)->toHaveCount(1);
    expect($destList->fresh()->albums->first()->id)->toBe($album->id);
});

test('moved album gets the next position in the destination list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $existingAlbum = Album::factory()->create();
    $movedAlbum = Album::factory()->create();

    $destList->albums()->attach($existingAlbum->id, ['position' => 1]);
    $sourceList->albums()->attach($movedAlbum->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $movedAlbum]), [
            'destination_list_id' => $destList->id,
        ]);

    $pivot = $destList->fresh()->albums()->where('album_id', $movedAlbum->id)->first()->pivot;
    expect($pivot->position)->toBe(2);
});

test('cannot move album to a list that already contains it', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);
    $destList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertRedirect(route('lists.show', $sourceList));

    // Album should still be in the source list (not moved)
    expect($sourceList->fresh()->albums)->toHaveCount(1);
    expect($destList->fresh()->albums)->toHaveCount(1);
});

test('cannot move album to another users list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $otherUser->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertForbidden();

    expect($sourceList->fresh()->albums)->toHaveCount(1);
});

test('cannot move album from another users list', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $owner->id]);
    $destList = AlbumList::factory()->create(['user_id' => $otherUser->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($otherUser)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertForbidden();

    expect($sourceList->fresh()->albums)->toHaveCount(1);
});

test('unauthenticated user cannot move albums', function () {
    $list = AlbumList::factory()->create();
    $destList = AlbumList::factory()->create(['user_id' => $list->user_id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->post(route('lists.albums.move', [$list, $album]), [
        'destination_list_id' => $destList->id,
    ])
        ->assertRedirect(route('login'));
});

test('destination list id is required for move', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$list, $album]), [])
        ->assertSessionHasErrors('destination_list_id');
});

test('list search endpoint returns matching lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Rock Albums']);
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Jazz Albums']);

    $this->actingAs($user)
        ->getJson(route('lists.search', ['q' => 'Rock']))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Rock Albums');
});

test('list search endpoint excludes the specified list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Rock Albums']);
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Rock Classics']);

    $this->actingAs($user)
        ->getJson(route('lists.search', ['q' => 'Rock', 'exclude' => $list->id]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Rock Classics');
});

test('list search endpoint does not return other users lists', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $otherUser->id, 'title' => 'Rock Albums']);

    $this->actingAs($user)
        ->getJson(route('lists.search', ['q' => 'Rock']))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('list search requires a query parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('lists.search'))
        ->assertUnprocessable();
});

test('moving an album with a note preserves the note', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1, 'note' => 'Great album']);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertRedirect(route('lists.show', $sourceList));

    $pivot = $destList->fresh()->albums()->where('album_id', $album->id)->first()->pivot;
    expect($pivot->note)->toBe('Great album');
});

test('current list updates immediately after move', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['title' => 'Album To Move']);

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ]);

    $this->actingAs($user)
        ->get(route('lists.show', $sourceList))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 0)
            ->where('list.albumsCount', 0)
        );
});
