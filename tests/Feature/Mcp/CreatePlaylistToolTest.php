<?php

use App\Mcp\Servers\HoopifyServer;
use App\Mcp\Tools\CreatePlaylist;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it creates a playlist and adds tracks', function () {
    Http::fake([
        'api.spotify.com/v1/me/playlists' => Http::response([
            'id' => 'playlist123',
            'name' => 'Road Trip',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/playlist123'],
            'uri' => 'spotify:playlist:playlist123',
        ]),
        'api.spotify.com/v1/playlists/playlist123/items' => Http::response([
            'snapshot_id' => 'snapshot1',
        ]),
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(CreatePlaylist::class, [
            'name' => 'Road Trip',
            'track_uris' => [
                'spotify:track:4iV5W9uYEdYUVa79Axb7Rh',
                'spotify:track:5CQ30WqJwcep0pYcV4AMNc',
            ],
        ])
        ->assertOk()
        ->assertSee('Road Trip')
        ->assertSee('2 track(s)');
});

test('it accepts an optional description', function () {
    Http::fake([
        'api.spotify.com/v1/me/playlists' => Http::response([
            'id' => 'playlist456',
            'name' => 'Workout Mix',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/playlist456'],
            'uri' => 'spotify:playlist:playlist456',
        ]),
        'api.spotify.com/v1/playlists/playlist456/items' => Http::response([
            'snapshot_id' => 'snapshot2',
        ]),
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(CreatePlaylist::class, [
            'name' => 'Workout Mix',
            'description' => 'Songs to get pumped',
            'track_uris' => ['spotify:track:4iV5W9uYEdYUVa79Axb7Rh'],
        ])
        ->assertOk()
        ->assertSee('Workout Mix');
});

test('it returns error when playlist creation fails', function () {
    Http::fake([
        'api.spotify.com/v1/me/playlists' => Http::response([], 403),
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(CreatePlaylist::class, [
            'name' => 'Fail Playlist',
            'track_uris' => ['spotify:track:4iV5W9uYEdYUVa79Axb7Rh'],
        ])
        ->assertHasErrors();
});

test('it validates name is required', function () {
    HoopifyServer::actingAs($this->user)
        ->tool(CreatePlaylist::class, [
            'track_uris' => ['spotify:track:4iV5W9uYEdYUVa79Axb7Rh'],
        ])
        ->assertHasErrors(['name']);
});

test('it validates track_uris is required', function () {
    HoopifyServer::actingAs($this->user)
        ->tool(CreatePlaylist::class, [
            'name' => 'Test Playlist',
        ])
        ->assertHasErrors(['track uris']);
});

test('it validates track uris match spotify format', function () {
    HoopifyServer::actingAs($this->user)
        ->tool(CreatePlaylist::class, [
            'name' => 'Test Playlist',
            'track_uris' => ['not-a-valid-uri'],
        ])
        ->assertHasErrors();
});
