<?php

use App\Mcp\Servers\CirclesServer;
use App\Mcp\Tools\SearchTracks;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it returns search results for multiple queries', function () {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'items' => [
                    [
                        'id' => '4iV5W9uYEdYUVa79Axb7Rh',
                        'name' => 'Bohemian Rhapsody',
                        'artists' => [['name' => 'Queen']],
                        'album' => ['name' => 'A Night at the Opera'],
                        'uri' => 'spotify:track:4iV5W9uYEdYUVa79Axb7Rh',
                        'duration_ms' => 354947,
                    ],
                ],
            ],
        ]),
    ]);

    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, ['queries' => ['Bohemian Rhapsody', 'Stairway to Heaven']])
        ->assertOk()
        ->assertSee('Bohemian Rhapsody')
        ->assertSee('Queen');
});

test('it passes through spotify track uris without searching', function () {
    Http::fake();

    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, ['queries' => ['spotify:track:4iV5W9uYEdYUVa79Axb7Rh']])
        ->assertOk()
        ->assertSee('4iV5W9uYEdYUVa79Axb7Rh');

    Http::assertNothingSent();
});

test('it passes through spotify track urls without searching', function () {
    Http::fake();

    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, ['queries' => ['https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh']])
        ->assertOk()
        ->assertSee('4iV5W9uYEdYUVa79Axb7Rh');

    Http::assertNothingSent();
});

test('it returns empty results when no match found', function () {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => ['items' => []],
        ]),
    ]);

    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, ['queries' => ['nonexistenttrackasdf']])
        ->assertOk()
        ->assertDontSee('Bohemian Rhapsody');
});

test('it validates queries is required', function () {
    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, [])
        ->assertHasErrors(['queries']);
});

test('it validates queries must be an array', function () {
    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, ['queries' => 'not an array'])
        ->assertHasErrors(['queries']);
});

test('it respects custom limit', function () {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'tracks' => [
                'items' => [
                    [
                        'id' => 'track1',
                        'name' => 'Track 1',
                        'artists' => [['name' => 'Artist']],
                        'album' => ['name' => 'Album'],
                        'uri' => 'spotify:track:track1',
                        'duration_ms' => 200000,
                    ],
                ],
            ],
        ]),
    ]);

    CirclesServer::actingAs($this->user)
        ->tool(SearchTracks::class, ['queries' => ['test'], 'limit' => 5])
        ->assertOk()
        ->assertSee('Track 1');
});
