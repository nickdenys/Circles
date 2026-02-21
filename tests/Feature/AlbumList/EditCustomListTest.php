<?php

use App\Models\AlbumList;
use App\Models\User;

test('edit button is available for custom lists with pre-filled data', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create([
        'title' => 'My Custom List',
        'description' => 'A great description',
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.title', 'My Custom List')
            ->where('list.description', 'A great description')
            ->where('list.type', 'custom')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain('id="edit-list-button"');
});

test('list data includes id for the update route', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->has('list.id')
        );
});

test('user can update a custom list title and description', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create([
        'title' => 'Original Title',
        'description' => 'Original Description',
    ]);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ])
        ->assertRedirect(route('lists.show', $list));

    $list->refresh();

    expect($list)
        ->title->toBe('Updated Title')
        ->description->toBe('Updated Description');
});

test('user can clear the description when updating', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create([
        'title' => 'Has Description',
        'description' => 'Some description',
    ]);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'Has Description',
            'description' => null,
        ])
        ->assertRedirect(route('lists.show', $list));

    expect($list->refresh()->description)->toBeNull();
});

test('validation requires a title when updating', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create([
        'title' => 'Original Title',
    ]);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => '',
        ])
        ->assertSessionHasErrors('title');

    expect($list->refresh()->title)->toBe('Original Title');
});

test('validation rejects a title longer than 255 characters when updating', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors('title');
});

test('validation rejects a description longer than 1000 characters when updating', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'Valid Title',
            'description' => str_repeat('a', 1001),
        ])
        ->assertSessionHasErrors('description');
});

test('system lists cannot be edited', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->for($user)->create([
        'title' => 'Watchlist',
    ]);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'Renamed Watchlist',
        ])
        ->assertForbidden();

    expect($list->refresh()->title)->toBe('Watchlist');
});

test('edit button is not shown for system lists', function () {
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

test('users cannot edit lists belonging to other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = AlbumList::factory()->for($owner)->create([
        'title' => 'Owner List',
    ]);

    $this->actingAs($other)
        ->put(route('lists.update', $list), [
            'title' => 'Hijacked',
        ])
        ->assertForbidden();

    expect($list->refresh()->title)->toBe('Owner List');
});

test('unauthenticated users cannot update lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create([
        'title' => 'Original',
    ]);

    $this->put(route('lists.update', $list), [
        'title' => 'Updated',
    ])->assertRedirect(route('login'));

    expect($list->refresh()->title)->toBe('Original');
});
