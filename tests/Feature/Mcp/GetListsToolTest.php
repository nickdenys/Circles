<?php

use App\Mcp\Servers\CirclesServer;
use App\Mcp\Tools\GetLists;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it returns user lists with album counts', function () {
    $customList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'My Favorites',
    ]);

    $albums = Album::factory()->count(3)->create();
    $customList->albums()->attach($albums->pluck('id'), ['position' => 0]);

    CirclesServer::actingAs($this->user)
        ->tool(GetLists::class)
        ->assertOk()
        ->assertSee('Listen Later')
        ->assertSee('My Favorites');
});

test('it shows system list for new user with no custom lists', function () {
    CirclesServer::actingAs($this->user)
        ->tool(GetLists::class)
        ->assertOk()
        ->assertSee('Listen Later');
});
