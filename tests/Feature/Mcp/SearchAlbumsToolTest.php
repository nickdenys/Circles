<?php

use App\Mcp\Servers\HoopifyServer;
use App\Mcp\Tools\SearchAlbums;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it returns search results from spotify', function () {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => [
                'items' => [
                    [
                        'id' => '4aawyAB9vmqN3uQ7FjRGTy',
                        'name' => 'Global Warming',
                        'artists' => [['name' => 'Pitbull']],
                        'images' => [['url' => 'https://i.scdn.co/image/test', 'height' => 640, 'width' => 640]],
                        'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
                    ],
                    [
                        'id' => '5ht7ItJgpBH7W6vJ5BqpPr',
                        'name' => 'Thriller',
                        'artists' => [['name' => 'Michael Jackson']],
                        'images' => [['url' => 'https://i.scdn.co/image/thriller', 'height' => 640, 'width' => 640]],
                        'uri' => 'spotify:album:5ht7ItJgpBH7W6vJ5BqpPr',
                    ],
                ],
            ],
        ]),
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(SearchAlbums::class, ['query' => 'global warming'])
        ->assertOk()
        ->assertSee('Global Warming')
        ->assertSee('Thriller');
});

test('it returns empty array when no match found', function () {
    Http::fake([
        'api.spotify.com/v1/search*' => Http::response([
            'albums' => ['items' => []],
        ]),
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(SearchAlbums::class, ['query' => 'nonexistentalbumasdf'])
        ->assertOk()
        ->assertDontSee('Global Warming');
});

test('it validates query is required', function () {
    HoopifyServer::actingAs($this->user)
        ->tool(SearchAlbums::class, [])
        ->assertHasErrors(['query']);
});

test('it surfaces a reconnect message when the spotify refresh token is invalid', function () {
    $user = User::factory()->withExpiredToken()->create();

    Http::fake([
        'accounts.spotify.com/api/token' => Http::response(['error' => 'invalid_grant'], 400),
    ]);

    HoopifyServer::actingAs($user)
        ->tool(SearchAlbums::class, ['query' => 'doesnt matter'])
        ->assertSee('Your Spotify connection has expired')
        ->assertSee('/auth/spotify/reconnect');
});
