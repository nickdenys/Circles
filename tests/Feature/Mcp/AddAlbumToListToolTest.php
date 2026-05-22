<?php

use App\Mcp\Servers\HoopifyServer;
use App\Mcp\Tools\AddAlbumToList;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    Queue::fake();
});

test('it adds a new album by fetching from spotify', function () {
    Http::fake([
        'api.spotify.com/v1/albums/4aawyAB9vmqN3uQ7FjRGTy' => Http::response([
            'id' => '4aawyAB9vmqN3uQ7FjRGTy',
            'name' => 'Global Warming',
            'artists' => [['name' => 'Pitbull']],
            'images' => [['url' => 'https://i.scdn.co/image/test', 'height' => 640, 'width' => 640]],
            'uri' => 'spotify:album:4aawyAB9vmqN3uQ7FjRGTy',
            'album_type' => 'album',
            'total_tracks' => 18,
            'release_date' => '2012-11-16',
            'tracks' => [
                'items' => [
                    ['duration_ms' => 200000],
                    ['duration_ms' => 250000],
                ],
            ],
            'genres' => [],
        ]),
    ]);

    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
            'list' => 'My List',
        ])
        ->assertOk()
        ->assertSee('Global Warming');

    $this->assertDatabaseHas('albums', [
        'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
    ]);
});

test('it reuses an existing album record', function () {
    $album = Album::factory()->create([
        'spotify_id' => 'existingAlbumId',
        'title' => 'Existing Album',
    ]);

    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => 'existingAlbumId',
            'list' => 'My List',
        ])
        ->assertOk()
        ->assertSee('Existing Album');

    Http::assertNothingSent();
});

test('it defaults to listen later when no list specified', function () {
    $album = Album::factory()->create([
        'spotify_id' => 'defaultListAlbum',
        'title' => 'Default List Album',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => 'defaultListAlbum',
        ])
        ->assertOk()
        ->assertSee('Listen Later');
});

test('it parses spotify urls', function () {
    $album = Album::factory()->create([
        'spotify_id' => '4aawyAB9vmqN3uQ7FjRGTy',
        'title' => 'URL Album',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => 'https://open.spotify.com/album/4aawyAB9vmqN3uQ7FjRGTy',
        ])
        ->assertOk()
        ->assertSee('URL Album');
});

test('it stores a note when provided', function () {
    $album = Album::factory()->create([
        'spotify_id' => 'notedAlbumId',
        'title' => 'Noted Album',
    ]);

    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => 'notedAlbumId',
            'list' => 'My List',
            'note' => 'Great late-night record.',
        ])
        ->assertOk()
        ->assertSee('Great late-night record.');

    $this->assertDatabaseHas('album_album_list', [
        'album_list_id' => $list->id,
        'album_id' => $album->id,
        'note' => 'Great late-night record.',
    ]);
});

test('it stores a null note when the note is blank', function () {
    $album = Album::factory()->create([
        'spotify_id' => 'blankNoteAlbumId',
        'title' => 'Blank Note Album',
    ]);

    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => 'blankNoteAlbumId',
            'list' => 'My List',
            'note' => '   ',
        ])
        ->assertOk();

    $this->assertDatabaseHas('album_album_list', [
        'album_list_id' => $list->id,
        'album_id' => $album->id,
        'note' => null,
    ]);
});

test('it prevents duplicate albums in a list', function () {
    $album = Album::factory()->create([
        'spotify_id' => 'dupeAlbumId',
        'title' => 'Dupe Album',
    ]);

    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    $list->albums()->attach($album->id, ['position' => 1]);

    HoopifyServer::actingAs($this->user)
        ->tool(AddAlbumToList::class, [
            'spotify_id' => 'dupeAlbumId',
            'list' => 'My List',
        ])
        ->assertOk()
        ->assertSee('already in');
});
