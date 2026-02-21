<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('albums can be reordered via the reorder endpoint', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create();
    $album2 = Album::factory()->create();
    $album3 = Album::factory()->create();

    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);
    $list->albums()->attach($album3->id, ['position' => 3]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [$album3->id, $album1->id, $album2->id],
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Order updated.');

    expect($list->albums()->orderBy('position')->pluck('albums.id')->toArray())
        ->toBe([$album3->id, $album1->id, $album2->id]);
});

test('reorder updates position values correctly', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create();
    $album2 = Album::factory()->create();

    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [$album2->id, $album1->id],
        ])
        ->assertOk();

    expect($list->albums()->where('album_id', $album2->id)->first()->pivot->position)->toBe(1);
    expect($list->albums()->where('album_id', $album1->id)->first()->pivot->position)->toBe(2);
});

test('reorder requires album_ids array', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('album_ids');
});

test('reorder requires album_ids to contain integers', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => ['abc', 'def'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('album_ids.0');
});

test('reorder is forbidden for other users lists', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [1, 2],
        ])
        ->assertForbidden();
});

test('reorder requires authentication', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->putJson(route('lists.albums.reorder', $list), [
        'album_ids' => [1, 2],
    ])->assertUnauthorized();
});

test('list detail page displays drag handles on album cards', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('drag-handle')
        ->toContain('cursor-grab')
        ->toContain('GripVertical');
});

test('album card includes data-album-db-id attribute', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)->toContain('data-album-db-id={album.id}');
});

test('reorder preserves order after page reload', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create(['title' => 'First Album']);
    $album2 = Album::factory()->create(['title' => 'Second Album']);
    $album3 = Album::factory()->create(['title' => 'Third Album']);

    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);
    $list->albums()->attach($album3->id, ['position' => 3]);

    // Reorder: third, first, second
    $this->actingAs($user)
        ->putJson(route('lists.albums.reorder', $list), [
            'album_ids' => [$album3->id, $album1->id, $album2->id],
        ])
        ->assertOk();

    // Verify page shows albums in new order
    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.data.0.title', 'Third Album')
            ->where('albums.data.1.title', 'First Album')
            ->where('albums.data.2.title', 'Second Album')
        );
});
