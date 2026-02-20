<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('album card displays album title', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['title' => 'OK Computer']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertSee('OK Computer');
});

test('album card displays artist name', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['artists' => 'Radiohead']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('Radiohead');
});

test('album card displays album cover image', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['cover_url' => 'https://i.scdn.co/image/test123']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('https://i.scdn.co/image/test123', false);
});

test('album card displays runtime formatted as minutes', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['runtime_ms' => 2700000]); // 45 minutes
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('45 min');
});

test('album card displays runtime with hours when over 60 minutes', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['runtime_ms' => 4500000]); // 1h 15m
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('1h 15m');
});

test('album card displays album type', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['album_type' => 'album']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('Album');
});

test('album card displays total tracks', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['total_tracks' => 12]);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('12 tracks');
});

test('album card displays singular track label for single track', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['total_tracks' => 1]);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('1 track');
});

test('album card displays release date', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['release_date' => '2024-06-15']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('2024-06-15');
});

test('album card links to spotify uri', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['spotify_uri' => 'spotify:album:6dVIqQ8qmQ5GBnJ9shOYGE']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('href="spotify:album:6dVIqQ8qmQ5GBnJ9shOYGE"', false);
});

test('album card uses expanded card component with album-card class', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSee('class="album-card', false);
});

test('empty state is hidden when albums exist', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertDontSee('No albums yet');
});

test('multiple album cards are displayed in order', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album1 = Album::factory()->create(['title' => 'First Album']);
    $album2 = Album::factory()->create(['title' => 'Second Album']);
    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSeeInOrder(['First Album', 'Second Album']);
});
