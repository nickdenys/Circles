<?php

use App\Jobs\FetchAlbumGenres;
use App\Models\Album;
use Illuminate\Support\Facades\Http;

test('job fetches genres from musicbrainz and updates the album', function () {
    $album = Album::factory()->create([
        'title' => 'Global Warming',
        'artists' => 'Pitbull',
        'genres' => [],
    ]);

    Http::fake([
        'musicbrainz.org/ws/2/release-group?*' => Http::response([
            'release-groups' => [['id' => 'mb-test-id']],
        ]),
        'musicbrainz.org/ws/2/release-group/mb-test-id*' => Http::response([
            'genres' => [
                ['name' => 'latin pop', 'count' => 5],
                ['name' => 'miami hip hop', 'count' => 3],
            ],
        ]),
    ]);

    (new FetchAlbumGenres($album))->handle();

    $album->refresh();
    expect($album->genres)->toBe(['latin pop', 'miami hip hop']);
});

test('job uses first artist from comma-separated list', function () {
    $album = Album::factory()->create([
        'title' => 'Test Album',
        'artists' => 'Main Artist, Featured Artist',
        'genres' => [],
    ]);

    Http::fake([
        'musicbrainz.org/*' => Http::response(['release-groups' => []]),
    ]);

    (new FetchAlbumGenres($album))->handle();

    Http::assertSent(function ($request) {
        $query = urldecode($request->url());

        return str_contains($query, 'musicbrainz.org')
            && str_contains($query, 'Main Artist')
            && ! str_contains($query, 'Featured');
    });
});

test('job sets empty genres when musicbrainz returns no results', function () {
    $album = Album::factory()->create([
        'title' => 'Unknown Album',
        'artists' => 'Unknown Artist',
        'genres' => [],
    ]);

    Http::fake([
        'musicbrainz.org/*' => Http::response(['release-groups' => []]),
    ]);

    (new FetchAlbumGenres($album))->handle();

    $album->refresh();
    expect($album->genres)->toBe([]);
});

test('job bypasses cache when flag is set', function () {
    $album = Album::factory()->create([
        'title' => 'Test Album',
        'artists' => 'Test Artist',
        'genres' => ['old genre'],
    ]);

    Http::fake([
        'musicbrainz.org/ws/2/release-group?*' => Http::response([
            'release-groups' => [['id' => 'mb-id']],
        ]),
        'musicbrainz.org/ws/2/release-group/mb-id*' => Http::response([
            'genres' => [['name' => 'new genre', 'count' => 1]],
        ]),
    ]);

    (new FetchAlbumGenres($album, bypassCache: true))->handle();

    $album->refresh();
    expect($album->genres)->toBe(['new genre']);
});
