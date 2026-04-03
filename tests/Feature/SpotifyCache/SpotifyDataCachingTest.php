<?php

use App\Models\User;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function fakeSpotifySearch(array $albums = []): void
{
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => [
                'items' => $albums,
            ],
        ]),
    ]);
}

function fakeSpotifyAlbum(array $overrides = []): void
{
    $album = array_merge([
        'id' => '4aawyAB9vmqN3uQ7FjRGTy',
        'name' => 'Global Warming',
        'artists' => [['id' => '0TnOYISbd1XYRBk9myaseg', 'name' => 'Pitbull']],
        'album_type' => 'album',
        'total_tracks' => 12,
        'release_date' => '2012-11-16',
        'images' => [['url' => 'https://i.scdn.co/image/cover', 'height' => 640, 'width' => 640]],
        'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
        'tracks' => [
            'items' => [
                ['duration_ms' => 200000],
                ['duration_ms' => 180000],
            ],
        ],
    ], $overrides);

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response($album),
        'musicbrainz.org/ws/2/release-group?*' => Http::response([
            'release-groups' => [['id' => 'mb-test-id']],
        ]),
        'musicbrainz.org/ws/2/release-group/mb-test-id*' => Http::response([
            'genres' => [['name' => 'latin pop', 'count' => 5]],
        ]),
    ]);
}

function sampleSearchAlbum(array $overrides = []): array
{
    return array_merge([
        'id' => '4aawyAB9vmqN3uQ7FjRGTy',
        'name' => 'Global Warming',
        'artists' => [['name' => 'Pitbull']],
        'images' => [['url' => 'https://i.scdn.co/image/cover', 'height' => 640, 'width' => 640]],
        'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
    ], $overrides);
}

test('search results are cached after first api call', function () {
    $user = User::factory()->create();

    fakeSpotifySearch([sampleSearchAlbum()]);

    $spotify = new SpotifyService($user);

    $first = $spotify->searchAlbums('global');
    $second = $spotify->searchAlbums('global');

    expect($first)->toEqual($second);
    Http::assertSentCount(1);
});

test('cached search results are used for subsequent requests', function () {
    $user = User::factory()->create();

    fakeSpotifySearch([sampleSearchAlbum()]);

    $spotify = new SpotifyService($user);
    $spotify->searchAlbums('global');

    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => ['items' => [sampleSearchAlbum(['name' => 'Different Album'])]],
        ]),
    ]);

    $cached = $spotify->searchAlbums('global');
    expect($cached[0]['name'])->toBe('Global Warming');
});

test('different search queries have separate cache entries', function () {
    $user = User::factory()->create();

    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => ['items' => [sampleSearchAlbum()]],
        ]),
    ]);

    $spotify = new SpotifyService($user);
    $spotify->searchAlbums('global');
    $spotify->searchAlbums('thriller');

    Http::assertSentCount(2);
});

test('different users have separate search cache entries', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => ['items' => [sampleSearchAlbum()]],
        ]),
    ]);

    $spotify1 = new SpotifyService($user1);
    $spotify1->searchAlbums('global');

    $spotify2 = new SpotifyService($user2);
    $spotify2->searchAlbums('global');

    Http::assertSentCount(2);
});

test('album data is cached after first api call', function () {
    $user = User::factory()->create();

    fakeSpotifyAlbum();

    $spotify = new SpotifyService($user);

    $first = $spotify->getAlbum('4aawyAB9vmqN3uQ7FjRGTy');
    $second = $spotify->getAlbum('4aawyAB9vmqN3uQ7FjRGTy');

    expect($first)->toEqual($second);
    // 1 request on first call (Spotify album), 0 on second (cached)
    Http::assertSentCount(1);
});

test('cached album data is used for subsequent requests', function () {
    $user = User::factory()->create();

    fakeSpotifyAlbum();

    $spotify = new SpotifyService($user);
    $spotify->getAlbum('4aawyAB9vmqN3uQ7FjRGTy');

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response([
            'id' => '4aawyAB9vmqN3uQ7FjRGTy',
            'name' => 'Updated Name',
            'artists' => [['id' => '0TnOYISbd1XYRBk9myaseg', 'name' => 'Pitbull']],
            'album_type' => 'album',
            'total_tracks' => 12,
            'release_date' => '2012-11-16',
            'images' => [['url' => 'https://i.scdn.co/image/cover']],
            'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
            'tracks' => ['items' => []],
        ]),
        'musicbrainz.org/*' => Http::response(['release-groups' => []]),
    ]);

    $cached = $spotify->getAlbum('4aawyAB9vmqN3uQ7FjRGTy');
    expect($cached['title'])->toBe('Global Warming');
});

test('failed search responses are not cached', function () {
    $user = User::factory()->create();

    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([], 500),
    ]);

    $spotify = new SpotifyService($user);
    $spotify->searchAlbums('global');

    $cacheKey = 'spotify_search:'.$user->id.':'.md5('global').':5';
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('failed album responses are not cached', function () {
    $user = User::factory()->create();

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response([], 404),
    ]);

    $spotify = new SpotifyService($user);
    $spotify->getAlbum('nonexistent');

    expect(Cache::has('spotify_album:nonexistent'))->toBeFalse();
});

test('bypass cache forces fresh search api call', function () {
    $user = User::factory()->create();
    $callCount = 0;

    Http::fake([
        'api.spotify.com/v1/search*' => function () use (&$callCount) {
            $callCount++;

            return Http::response([
                'albums' => ['items' => [sampleSearchAlbum(['name' => 'Result '.$callCount])]],
            ]);
        },
    ]);

    $spotify = new SpotifyService($user);
    $spotify->searchAlbums('global');
    expect($callCount)->toBe(1);

    $fresh = $spotify->bypassCache()->searchAlbums('global');
    expect($callCount)->toBe(2);
    expect($fresh[0]['name'])->toBe('Result 2');
});

test('bypass cache forces fresh album api call', function () {
    $user = User::factory()->create();
    $callCount = 0;

    Http::fake([
        'api.spotify.com/v1/albums/*' => function () use (&$callCount) {
            $callCount++;

            return Http::response([
                'id' => '4aawyAB9vmqN3uQ7FjRGTy',
                'name' => 'Album v'.$callCount,
                'artists' => [['id' => '0TnOYISbd1XYRBk9myaseg', 'name' => 'Pitbull']],
                'album_type' => 'album',
                'total_tracks' => $callCount * 10,
                'release_date' => '2012-11-16',
                'images' => [['url' => 'https://i.scdn.co/image/cover']],
                'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
                'tracks' => ['items' => [['duration_ms' => 200000]]],
            ]);
        },
        'musicbrainz.org/ws/2/release-group?*' => Http::response([
            'release-groups' => [['id' => 'mb-test-id']],
        ]),
        'musicbrainz.org/ws/2/release-group/mb-test-id*' => Http::response([
            'genres' => [['name' => 'latin pop', 'count' => 5]],
        ]),
    ]);

    $spotify = new SpotifyService($user);
    $spotify->getAlbum('4aawyAB9vmqN3uQ7FjRGTy');
    expect($callCount)->toBe(1);

    $fresh = $spotify->bypassCache()->getAlbum('4aawyAB9vmqN3uQ7FjRGTy');
    expect($callCount)->toBe(2);
    expect($fresh['title'])->toBe('Album v2');
    expect($fresh['total_tracks'])->toBe(20);
});

test('search results are served from cache via search endpoint', function () {
    $user = User::factory()->create();

    fakeSpotifySearch([sampleSearchAlbum()]);

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'global']))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');

    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => ['items' => [sampleSearchAlbum(), sampleSearchAlbum(['id' => 'second'])]],
        ]),
    ]);

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'global']))
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});
