<?php

use App\Jobs\FetchAlbumGenres;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function fakeSpotifyAlbumResponse(array $overrides = []): void
{
    $album = array_merge([
        'id' => '4aawyAB9vmqN3uQ7FjRGTy',
        'name' => 'Global Warming',
        'artists' => [
            ['id' => '0TnOYISbd1XYRBk9myaseg', 'name' => 'Pitbull'],
        ],
        'album_type' => 'album',
        'total_tracks' => 12,
        'release_date' => '2012-11-16',
        'images' => [
            ['url' => 'https://i.scdn.co/image/ab67616d0000b273e', 'height' => 640, 'width' => 640],
        ],
        'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
        'tracks' => [
            'items' => [
                ['duration_ms' => 200000],
                ['duration_ms' => 180000],
                ['duration_ms' => 220000],
            ],
        ],
    ], $overrides);

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response($album),
    ]);
}

test('clicking a search result adds the album to the list', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    fakeSpotifyAlbumResponse();

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Global Warming')
        ->assertJsonPath('data.artists', 'Pitbull');

    expect($list->albums)->toHaveCount(1);
});

test('album spotify data is stored in the database', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    fakeSpotifyAlbumResponse();

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertCreated();

    $album = Album::where('spotify_id', '4aawyAB9vmqN3uQ7FjRGTy')->first();
    expect($album)
        ->title->toBe('Global Warming')
        ->artists->toBe('Pitbull')
        ->cover_url->toBe('https://i.scdn.co/image/ab67616d0000b273e')
        ->runtime_ms->toBe(600000)
        ->album_type->toBe('album')
        ->total_tracks->toBe(12)
        ->release_date->toBe('2012-11-16')
        ->spotify_uri->toBe('spotify:album:4aawyAB9vmqN3uQ7FjRGTy')
        ->genres->toBe([]);

    Queue::assertPushed(FetchAlbumGenres::class, function (FetchAlbumGenres $job) use ($album) {
        return $job->album->id === $album->id && $job->bypassCache === false;
    });
});

test('duplicate albums in the same list are prevented', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['spotify_id' => 'existing-album']);

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => 'existing-album',
        ])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Album already in this list.');

    expect($list->albums)->toHaveCount(1);
});

test('album appears in the list immediately after being added', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    fakeSpotifyAlbumResponse();

    $response = $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertCreated();

    $data = $response->json('data');
    expect($data)
        ->toHaveKeys(['id', 'spotify_id', 'title', 'artists', 'cover_url', 'runtime_ms', 'album_type', 'total_tracks', 'release_date', 'spotify_uri', 'genres']);
});

test('existing album record is reused when adding to a different list', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list1 = AlbumList::factory()->create(['user_id' => $user->id]);
    $list2 = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy']);

    $list1->albums()->attach($album->id, ['position' => 1]);

    fakeSpotifyAlbumResponse();

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list2), [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertCreated();

    expect(Album::where('spotify_id', '4aawyAB9vmqN3uQ7FjRGTy')->count())->toBe(1);
    expect($list2->albums)->toHaveCount(1);

    Queue::assertNotPushed(FetchAlbumGenres::class);
});

test('album is assigned the next position in the list', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $existingAlbum = Album::factory()->create();

    $list->albums()->attach($existingAlbum->id, ['position' => 1]);

    fakeSpotifyAlbumResponse();

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertCreated();

    $newAlbum = Album::where('spotify_id', '4aawyAB9vmqN3uQ7FjRGTy')->first();
    $pivot = $list->albums()->where('album_id', $newAlbum->id)->first()->pivot;
    expect($pivot->position)->toBe(2);
});

test('spotify id is required to add an album', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('spotify_id');
});

test('user cannot add albums to another users list', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $owner->id]);

    fakeSpotifyAlbumResponse();

    $this->actingAs($otherUser)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertForbidden();
});

test('unauthenticated user cannot add albums', function () {
    $list = AlbumList::factory()->create();

    $this->postJson(route('lists.albums.store', $list), [
        'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
    ])
        ->assertUnauthorized();
});

test('returns 404 when spotify album is not found', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response([], 404),
    ]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.store', $list), [
            'spotify_id' => 'nonexistent-album',
        ])
        ->assertNotFound()
        ->assertJsonPath('message', 'Album not found on Spotify.');
});

test('album count is displayed on the list detail page', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(3)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('list.albumsCount', 3)
        );
});

test('empty state is shown when list has no albums', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 0)
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain('No albums yet. Click "Add an Album" to get started.');
});
