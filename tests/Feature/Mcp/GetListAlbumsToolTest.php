<?php

use App\Mcp\Servers\CirclesServer;
use App\Mcp\Tools\GetListAlbums;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it returns albums by list id', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Rock Classics',
    ]);

    $album = Album::factory()->create(['title' => 'Abbey Road']);
    $list->albums()->attach($album->id, ['position' => 1]);

    CirclesServer::actingAs($this->user)
        ->tool(GetListAlbums::class, ['list' => (string) $list->id])
        ->assertOk()
        ->assertSee('Abbey Road');
});

test('it returns albums by list name', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Jazz Collection',
    ]);

    $album = Album::factory()->create(['title' => 'Kind of Blue']);
    $list->albums()->attach($album->id, ['position' => 1]);

    CirclesServer::actingAs($this->user)
        ->tool(GetListAlbums::class, ['list' => 'Jazz Collection'])
        ->assertOk()
        ->assertSee('Kind of Blue');
});

test('it returns error when list not found', function () {
    CirclesServer::actingAs($this->user)
        ->tool(GetListAlbums::class, ['list' => 'Nonexistent List'])
        ->assertHasErrors(['List not found.']);
});

test('it includes the note from the pivot', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Noted List',
    ]);

    $album = Album::factory()->create(['title' => 'Noted Album']);
    $list->albums()->attach($album->id, [
        'position' => 1,
        'note' => 'Essential listening for Sundays.',
    ]);

    CirclesServer::actingAs($this->user)
        ->tool(GetListAlbums::class, ['list' => 'Noted List'])
        ->assertOk()
        ->assertSee('Essential listening for Sundays.');
});

test('it handles empty lists gracefully', function () {
    $list = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Empty List',
    ]);

    CirclesServer::actingAs($this->user)
        ->tool(GetListAlbums::class, ['list' => 'Empty List'])
        ->assertOk();
});
