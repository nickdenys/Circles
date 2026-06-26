<?php

use App\Enums\AlbumListMode;
use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('a list defaults to the default mode', function () {
    $list = AlbumList::factory()->create();

    expect($list->fresh()->mode)->toBe(AlbumListMode::Default);
});

test('a new user\'s Listen Later list defaults to the listening mode', function () {
    $user = User::factory()->create();

    expect($user->listenLaterList->mode)->toBe(AlbumListMode::Listening);
});

test('a new user\'s Reviewed list stays in the default mode', function () {
    $user = User::factory()->create();

    expect($user->reviewedList->mode)->toBe(AlbumListMode::Default);
});

test('creating a list without a mode defaults to default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Plain list']);

    $list = $user->albumLists()->where('title', 'Plain list')->first();

    expect($list->mode)->toBe(AlbumListMode::Default);
});

test('a custom list cannot be created in the listening mode', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'Deep cuts',
            'mode' => 'listening',
        ])
        ->assertRedirect(route('lists.index'));

    $list = $user->albumLists()->where('title', 'Deep cuts')->first();

    expect($list->mode)->toBe(AlbumListMode::Default);
});

test('updating a custom list cannot set the listening mode', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create(['title' => 'My list']);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'My list',
            'mode' => 'listening',
        ])
        ->assertRedirect(route('lists.show', $list->refresh()));

    expect($list->fresh()->mode)->toBe(AlbumListMode::Default);
});

test('the list show page exposes the mode', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('list.mode', 'listening')
        );
});

test('the show page renders the listening mode action bar', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain("mode === 'listening'")
        ->toContain('listened-button')
        ->toContain('album-actions-button')
        ->toContain('I listened to this');
});

test('the list dialogs no longer expose a mode field', function () {
    $create = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));
    $edit = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($create)->not->toContain('ListModeField');
    expect($edit)->not->toContain('ListModeField');
});
