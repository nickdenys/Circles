<?php

use App\Mcp\Servers\HoopifyServer;
use App\Mcp\Tools\DeleteList;
use App\Models\AlbumList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it deletes a custom list with warning', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Old Favorites',
    ]);

    HoopifyServer::actingAs($this->user)
        ->tool(DeleteList::class, ['list' => 'Old Favorites'])
        ->assertOk()
        ->assertSee('Warning')
        ->assertSee('Old Favorites');

    $this->assertDatabaseMissing('album_lists', [
        'id' => $list->id,
    ]);
});

test('it rejects deletion of system list', function () {
    HoopifyServer::actingAs($this->user)
        ->tool(DeleteList::class, ['list' => 'Listen Later'])
        ->assertHasErrors(['System lists cannot be deleted.']);
});
