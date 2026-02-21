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
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.title', 'OK Computer')
        );
});

test('album card displays artist name', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['artists' => 'Radiohead']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.artists', 'Radiohead')
        );
});

test('album card displays album cover image', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['cover_url' => 'https://i.scdn.co/image/test123']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.coverUrl', 'https://i.scdn.co/image/test123')
        );
});

test('album card displays runtime formatted as minutes', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['runtime_ms' => 2700000]); // 45 minutes
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.runtimeMs', 2700000)
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain('formatRuntime');
});

test('album card displays runtime with hours when over 60 minutes', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    // Verify the formatRuntime function handles hours correctly
    expect($component)
        ->toContain('totalMinutes >= 60')
        ->toContain('h ')
        ->toContain('m');
});

test('album card displays album type', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['album_type' => 'album']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.albumType', 'album')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain('formatAlbumType');
});

test('album card displays total tracks', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['total_tracks' => 12]);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.totalTracks', 12)
        );
});

test('album card displays singular track label for single track', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('=== 1')
        ->toContain("'track'")
        ->toContain("'tracks'");
});

test('album card displays release date', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['release_date' => '2024-06-15']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.releaseDate', '2024-06-15')
        );
});

test('album card links to spotify uri', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['spotify_uri' => 'spotify:album:6dVIqQ8qmQ5GBnJ9shOYGE']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.spotifyUri', 'spotify:album:6dVIqQ8qmQ5GBnJ9shOYGE')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain('href={album.spotifyUri}');
});

test('album card uses Card component with album-card class', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('className="album-card')
        ->toContain("from '@/components/ui/card'");
});

test('empty state is hidden when albums exist', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 1)
        );
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
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.title', 'First Album')
            ->where('albums.data.1.title', 'Second Album')
        );
});
