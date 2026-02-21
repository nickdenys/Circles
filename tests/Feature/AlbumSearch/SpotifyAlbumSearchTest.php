<?php

use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function fakeSpotifySearchResponse(array $albums = []): void
{
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => [
                'items' => $albums,
            ],
        ]),
    ]);
}

function sampleSpotifyAlbum(array $overrides = []): array
{
    return array_merge([
        'id' => '4aawyAB9vmqN3uQ7FjRGTy',
        'name' => 'Global Warming',
        'artists' => [
            ['name' => 'Pitbull'],
        ],
        'images' => [
            ['url' => 'https://i.scdn.co/image/ab67616d0000b273e', 'height' => 640, 'width' => 640],
        ],
        'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
    ], $overrides);
}

test('search bar is displayed on the list detail page', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="album-search-input"')
        ->toContain('Search for albums on Spotify...');
});

test('search dropdown container exists on the page', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="album-search-dropdown"')
        ->toContain('id="album-search-results"');
});

test('search endpoint returns album results from spotify', function () {
    $user = User::factory()->create();

    fakeSpotifySearchResponse([
        sampleSpotifyAlbum(),
        sampleSpotifyAlbum([
            'id' => '5ht7ItJgpBH7W6vJ5BqpPr',
            'name' => 'Thriller',
            'artists' => [['name' => 'Michael Jackson']],
            'images' => [['url' => 'https://i.scdn.co/image/thriller', 'height' => 640, 'width' => 640]],
            'uri' => 'spotify:album:5ht7ItJgpBH7W6vJ5BqpPr',
        ]),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'global']))
        ->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect($data[0])->toMatchArray([
        'id' => '4aawyAB9vmqN3uQ7FjRGTy',
        'name' => 'Global Warming',
        'artists' => 'Pitbull',
        'image' => 'https://i.scdn.co/image/ab67616d0000b273e',
        'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
    ]);
});

test('search endpoint returns up to 5 results', function () {
    $user = User::factory()->create();

    Http::fake([
        'api.spotify.com/v1/search*' => function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);
            expect((int) $params['limit'])->toBe(5);

            return Http::response(['albums' => ['items' => []]]);
        },
    ]);

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'test']))
        ->assertSuccessful();
});

test('search endpoint requires minimum 2 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'a']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

test('search endpoint requires a query parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

test('search endpoint returns each result with name artist and image', function () {
    $user = User::factory()->create();

    fakeSpotifySearchResponse([
        sampleSpotifyAlbum([
            'name' => 'Abbey Road',
            'artists' => [['name' => 'The Beatles']],
            'images' => [['url' => 'https://i.scdn.co/image/abbey', 'height' => 640, 'width' => 640]],
        ]),
    ]);

    $data = $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'abbey']))
        ->json('data');

    expect($data[0])
        ->toHaveKeys(['id', 'name', 'artists', 'image', 'uri'])
        ->name->toBe('Abbey Road')
        ->artists->toBe('The Beatles');
});

test('search endpoint handles albums with multiple artists', function () {
    $user = User::factory()->create();

    fakeSpotifySearchResponse([
        sampleSpotifyAlbum([
            'artists' => [
                ['name' => 'Artist One'],
                ['name' => 'Artist Two'],
            ],
        ]),
    ]);

    $data = $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'test']))
        ->json('data');

    expect($data[0]['artists'])->toBe('Artist One, Artist Two');
});

test('search endpoint handles albums with no images', function () {
    $user = User::factory()->create();

    fakeSpotifySearchResponse([
        sampleSpotifyAlbum(['images' => []]),
    ]);

    $data = $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'test']))
        ->json('data');

    expect($data[0]['image'])->toBeNull();
});

test('search endpoint requires authentication', function () {
    $this->getJson(route('spotify.search.albums', ['q' => 'test']))
        ->assertUnauthorized();
});

test('search endpoint refreshes expired spotify token', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response([
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 3600,
        ]),
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => ['items' => []],
        ]),
    ]);

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'test']))
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'accounts.spotify.com/api/token');
    });
});

test('search endpoint handles spotify api failure gracefully', function () {
    $user = User::factory()->create();

    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([], 500),
    ]);

    $this->actingAs($user)
        ->getJson(route('spotify.search.albums', ['q' => 'test']))
        ->assertSuccessful()
        ->assertJson(['data' => []]);
});

test('search bar is displayed on system list detail page', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    // Search input is not conditionally hidden based on list type
    expect($component)->toContain('id="album-search-input"');

    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Lists/Show'));
});
