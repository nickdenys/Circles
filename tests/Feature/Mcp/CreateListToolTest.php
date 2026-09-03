<?php

use App\Mcp\Servers\AlbumListServer;
use App\Mcp\Tools\CreateList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it creates a custom list', function () {
    AlbumListServer::actingAs($this->user)
        ->tool(CreateList::class, [
            'title' => 'My Playlist',
            'description' => 'A great playlist',
        ])
        ->assertOk()
        ->assertSee('My Playlist');

    $this->assertDatabaseHas('album_lists', [
        'user_id' => $this->user->id,
        'title' => 'My Playlist',
        'description' => 'A great playlist',
        'type' => 'custom',
    ]);
});

test('it validates title is required', function () {
    AlbumListServer::actingAs($this->user)
        ->tool(CreateList::class, [])
        ->assertHasErrors(['title']);
});
