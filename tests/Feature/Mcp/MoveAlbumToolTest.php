<?php

use App\Mcp\Servers\HoopifyServer;
use App\Mcp\Tools\MoveAlbum;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it moves album between lists', function () {
    $fromList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Source List',
    ]);

    $toList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Destination List',
    ]);

    $album = Album::factory()->create([
        'spotify_id' => 'moveMe',
        'title' => 'Move Me',
    ]);

    $fromList->albums()->attach($album->id, ['position' => 1]);

    HoopifyServer::actingAs($this->user)
        ->tool(MoveAlbum::class, [
            'spotify_id' => 'moveMe',
            'from_list' => 'Source List',
            'to_list' => 'Destination List',
        ])
        ->assertOk()
        ->assertSee('Move Me')
        ->assertSee('Source List')
        ->assertSee('Destination List');

    expect($fromList->albums()->where('album_id', $album->id)->exists())->toBeFalse();
    expect($toList->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('it preserves note when moving album between lists', function () {
    $fromList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Source List',
    ]);

    $toList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Destination List',
    ]);

    $album = Album::factory()->create([
        'spotify_id' => 'noteAlbum',
        'title' => 'Note Album',
    ]);

    $fromList->albums()->attach($album->id, ['position' => 1, 'note' => 'Must listen']);

    HoopifyServer::actingAs($this->user)
        ->tool(MoveAlbum::class, [
            'spotify_id' => 'noteAlbum',
            'from_list' => 'Source List',
            'to_list' => 'Destination List',
        ])
        ->assertOk();

    $pivot = $toList->fresh()->albums()->where('album_id', $album->id)->first()->pivot;
    expect($pivot->note)->toBe('Must listen');
});

test('it prevents duplicate in destination list', function () {
    $fromList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Source List',
    ]);

    $toList = AlbumList::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Destination List',
    ]);

    $album = Album::factory()->create([
        'spotify_id' => 'dupeMove',
        'title' => 'Dupe Move',
    ]);

    $fromList->albums()->attach($album->id, ['position' => 1]);
    $toList->albums()->attach($album->id, ['position' => 1]);

    HoopifyServer::actingAs($this->user)
        ->tool(MoveAlbum::class, [
            'spotify_id' => 'dupeMove',
            'from_list' => 'Source List',
            'to_list' => 'Destination List',
        ])
        ->assertHasErrors(['already exists']);
});
