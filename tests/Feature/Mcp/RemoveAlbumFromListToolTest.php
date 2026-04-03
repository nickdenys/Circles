<?php

use App\Mcp\Servers\HoopifyServer;
use App\Mcp\Tools\RemoveAlbumFromList;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it removes album from list with warning', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    $album = Album::factory()->create([
        'spotify_id' => 'removeMe',
        'title' => 'Remove Me',
    ]);

    $list->albums()->attach($album->id, ['position' => 1]);

    HoopifyServer::actingAs($this->user)
        ->tool(RemoveAlbumFromList::class, [
            'spotify_id' => 'removeMe',
            'list' => 'My List',
        ])
        ->assertOk()
        ->assertSee('Warning')
        ->assertSee('Remove Me');

    expect($list->albums()->where('album_id', $album->id)->exists())->toBeFalse();
});

test('it returns error when album not in list', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My List',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(RemoveAlbumFromList::class, [
            'spotify_id' => 'nonexistent',
            'list' => 'My List',
        ])
        ->assertHasErrors(['Album not found in this list.']);
});
