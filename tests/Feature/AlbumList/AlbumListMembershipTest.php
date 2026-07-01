<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('membership endpoint returns the ids of the users lists that contain the album', function () {
    $user = User::factory()->create();
    $listWithAlbum = AlbumList::factory()->create(['user_id' => $user->id]);
    $otherListWithAlbum = AlbumList::factory()->create(['user_id' => $user->id]);
    $listWithoutAlbum = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $listWithAlbum->albums()->attach($album->id, ['position' => 1]);
    $otherListWithAlbum->albums()->attach($album->id, ['position' => 1]);

    $response = $this->actingAs($user)
        ->getJson(route('albums.list-memberships', $album))
        ->assertSuccessful();

    expect($response->json('data'))
        ->toContain($listWithAlbum->id)
        ->toContain($otherListWithAlbum->id)
        ->not->toContain($listWithoutAlbum->id);
});

test('membership endpoint returns an empty list when the album is in none of the users lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $this->actingAs($user)
        ->getJson(route('albums.list-memberships', $album))
        ->assertSuccessful()
        ->assertExactJson(['data' => []]);
});

test('membership endpoint does not expose other users lists', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherUsersList = AlbumList::factory()->create(['user_id' => $otherUser->id]);
    $album = Album::factory()->create();

    $otherUsersList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->getJson(route('albums.list-memberships', $album))
        ->assertSuccessful()
        ->assertExactJson(['data' => []]);
});

test('unauthenticated user cannot query album list memberships', function () {
    $album = Album::factory()->create();

    $this->getJson(route('albums.list-memberships', $album))
        ->assertUnauthorized();
});
