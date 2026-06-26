<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the Reviewed list cannot be updated through the lists.update endpoint', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;

    $this->actingAs($user)
        ->put(route('lists.update', $reviewedList), ['title' => 'Hacked'])
        ->assertForbidden();
});

test('the Reviewed list cannot be deleted', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;

    $this->actingAs($user)
        ->delete(route('lists.destroy', $reviewedList))
        ->assertForbidden();
});

test('albums cannot be added to the Reviewed list manually', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $reviewedList), ['spotify_id' => 'whatever'])
        ->assertForbidden();
});

test('albums in the Reviewed list cannot have their pivot note edited', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$reviewedList, $album]), ['note' => 'nope'])
        ->assertForbidden();
});

test('albums cannot be removed from the Reviewed list manually', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->delete(route('lists.albums.destroy', [$reviewedList, $album]))
        ->assertForbidden();
});

test('albums cannot be moved into the Reviewed list', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->for($user)->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $sourceList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$sourceList, $album]), [
            'destination_list_id' => $reviewedList->id,
        ])
        ->assertForbidden();
});

test('albums cannot be moved out of the Reviewed list', function () {
    $user = User::factory()->create();
    $destinationList = AlbumList::factory()->for($user)->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->post(route('lists.albums.move', [$reviewedList, $album]), [
            'destination_list_id' => $destinationList->id,
        ])
        ->assertForbidden();
});

test('the list search endpoint can opt out of the Reviewed list', function () {
    $user = User::factory()->create();
    AlbumList::factory()->for($user)->create(['title' => 'Reviewed something']);

    $this->actingAs($user)
        ->getJson(route('lists.search', ['q' => 'review', 'exclude_reviewed' => 1]))
        ->assertSuccessful()
        ->assertJsonMissing(['type' => 'reviewed'])
        ->assertJsonFragment(['title' => 'Reviewed something']);
});

test('the show payload exposes rating and review for Reviewed list albums', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);

    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 4.5,
        'review' => 'a real review',
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $reviewedList))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.rating', 4.5)
            ->where('albums.data.0.review', 'a real review')
        );
});

test('the show payload omits rating and review on non-Reviewed lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('albums.data.0.rating')
            ->missing('albums.data.0.review')
        );
});
