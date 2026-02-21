<?php

use App\Models\AlbumList;
use App\Models\User;

test('delete button is shown on custom list detail page', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->component('Lists/Show')
            ->where('list.type', 'custom')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain('id="delete-list-button"');
});

test('delete button renders with confirmation pattern', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="delete-list-button"')
        ->toContain('Delete');
});

test('list id is available for the delete action', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->has('list.id')
        );
});

test('user can delete a custom list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('lists.destroy', $list))
        ->assertRedirect(route('lists.index'));

    $this->assertDatabaseMissing('album_lists', ['id' => $list->id]);
});

test('system lists cannot be deleted', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->for($user)->create([
        'title' => 'Watchlist',
    ]);

    $this->actingAs($user)
        ->delete(route('lists.destroy', $list))
        ->assertForbidden();

    $this->assertDatabaseHas('album_lists', ['id' => $list->id]);
});

test('delete button is not shown for system lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.type', 'system')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain("list.type === 'custom'");
});

test('users cannot delete lists belonging to other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = AlbumList::factory()->for($owner)->create();

    $this->actingAs($other)
        ->delete(route('lists.destroy', $list))
        ->assertForbidden();

    $this->assertDatabaseHas('album_lists', ['id' => $list->id]);
});

test('unauthenticated users cannot delete lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->delete(route('lists.destroy', $list))
        ->assertRedirect(route('login'));

    $this->assertDatabaseHas('album_lists', ['id' => $list->id]);
});
