<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('can update the note on a pivot row', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => 'A great album',
        ])
        ->assertOk()
        ->assertExactJson(['note' => 'A great album']);

    expect($list->albums()->first()->pivot->note)->toBe('A great album');
});

test('setting note to null clears it', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1, 'note' => 'Old note']);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => null,
        ])
        ->assertOk()
        ->assertExactJson(['note' => null]);

    expect($list->albums()->first()->pivot->note)->toBeNull();
});

test('empty string note is converted to null', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1, 'note' => 'Old note']);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => '',
        ])
        ->assertOk()
        ->assertExactJson(['note' => null]);

    expect($list->albums()->first()->pivot->note)->toBeNull();
});

test('whitespace-only note is trimmed and converted to null', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => '   ',
        ])
        ->assertOk()
        ->assertExactJson(['note' => null]);

    expect($list->albums()->first()->pivot->note)->toBeNull();
});

test('note with surrounding whitespace is trimmed', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => '  Great album  ',
        ])
        ->assertOk()
        ->assertExactJson(['note' => 'Great album']);

    expect($list->albums()->first()->pivot->note)->toBe('Great album');
});

test('note cannot exceed 10000 characters', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => str_repeat('a', 10001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('note');
});

test('updating note on a system list is allowed', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'type' => 'system']);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => 'Note on system list',
        ])
        ->assertOk()
        ->assertExactJson(['note' => 'Note on system list']);
});

test('cannot update note on another users list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->patchJson(route('lists.albums.update', [$list, $album]), [
            'note' => 'Hijacked',
        ])
        ->assertForbidden();

    expect($list->albums()->first()->pivot->note)->toBeNull();
});

test('unauthenticated user cannot update note', function () {
    $list = AlbumList::factory()->create();
    $album = Album::factory()->create();

    $list->albums()->attach($album->id, ['position' => 1]);

    $this->patchJson(route('lists.albums.update', [$list, $album]), [
        'note' => 'Nope',
    ])
        ->assertUnauthorized();
});
