<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('copying an album adds it to the destination list and keeps it in the source list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertRedirect(route('lists.show', $sourceList));

    expect($sourceList->fresh()->albums)->toHaveCount(1);
    expect($destList->fresh()->albums)->toHaveCount(1);
    expect($destList->fresh()->albums->first()->id)->toBe($album->id);
});

test('copied album gets the next position in the destination list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $existingAlbum = Album::factory()->create();
    $copiedAlbum = Album::factory()->create();

    $destList->albums()->attach($existingAlbum->id, ['position' => 1]);
    $sourceList->albums()->attach($copiedAlbum->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$sourceList, $copiedAlbum]), [
            'destination_list_id' => $destList->id,
        ]);

    $pivot = $destList->fresh()->albums()->where('album_id', $copiedAlbum->id)->first()->pivot;
    expect($pivot->position)->toBe(2);
});

test('copying an album with a note preserves the note in the destination list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1, 'note' => 'Great album']);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ]);

    $pivot = $destList->fresh()->albums()->where('album_id', $album->id)->first()->pivot;
    expect($pivot->note)->toBe('Great album');
});

test('cannot copy album to a list that already contains it', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);
    $destList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertRedirect(route('lists.show', $sourceList));

    expect($destList->fresh()->albums)->toHaveCount(1);
});

test('albums cannot be copied into the Reviewed list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->for($user)->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$sourceList, $album]), [
            'destination_list_id' => $reviewedList->id,
        ])
        ->assertForbidden();
});

test('cannot copy album to another users list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $user->id]);
    $destList = AlbumList::factory()->create(['user_id' => $otherUser->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertForbidden();

    expect($destList->fresh()->albums)->toHaveCount(0);
});

test('cannot copy album from another users list', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $sourceList = AlbumList::factory()->create(['user_id' => $owner->id]);
    $destList = AlbumList::factory()->create(['user_id' => $otherUser->id]);
    $album = Album::factory()->create();

    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($otherUser)
        ->post(route('lists.albums.copy', [$sourceList, $album]), [
            'destination_list_id' => $destList->id,
        ])
        ->assertForbidden();
});

test('unauthenticated user cannot copy albums', function () {
    $list = AlbumList::factory()->create();
    $destList = AlbumList::factory()->create(['user_id' => $list->user_id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->post(route('lists.albums.copy', [$list, $album]), [
        'destination_list_id' => $destList->id,
    ])
        ->assertRedirect(route('login'));
});

test('destination list id is required for copy', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.copy', [$list, $album]), [])
        ->assertSessionHasErrors('destination_list_id');
});
