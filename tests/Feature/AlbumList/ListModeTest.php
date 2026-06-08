<?php

use App\Enums\AlbumListMode;
use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('a list defaults to the default mode', function () {
    $list = AlbumList::factory()->create();

    expect($list->fresh()->mode)->toBe(AlbumListMode::Default);
});

test('creating a list with the listening mode persists it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'Deep cuts',
            'mode' => 'listening',
        ])
        ->assertRedirect(route('lists.index'));

    $list = $user->albumLists()->where('title', 'Deep cuts')->first();

    expect($list->mode)->toBe(AlbumListMode::Listening);
});

test('creating a list without a mode defaults to default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), ['title' => 'Plain list']);

    $list = $user->albumLists()->where('title', 'Plain list')->first();

    expect($list->mode)->toBe(AlbumListMode::Default);
});

test('the create mode must be a known value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('lists.index'))
        ->post(route('lists.store'), ['title' => 'Bad mode', 'mode' => 'nonsense'])
        ->assertSessionHasErrors('mode');
});

test('updating a custom list changes its mode', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create(['title' => 'My list']);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'My list',
            'mode' => 'listening',
        ])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh()->mode)->toBe(AlbumListMode::Listening);
});

test('updating a custom list without a mode keeps the current mode', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->listening()->for($user)->create(['title' => 'My list']);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'Renamed list',
            'description' => 'Updated',
        ])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh())
        ->title->toBe('Renamed list')
        ->mode->toBe(AlbumListMode::Listening);
});

test('the mode endpoint updates a custom list mode', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('lists.mode.update', $list), ['mode' => 'listening'])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh()->mode)->toBe(AlbumListMode::Listening);
});

test('system lists can change mode through the mode endpoint', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('lists.mode.update', $list), ['mode' => 'listening'])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh()->mode)->toBe(AlbumListMode::Listening);
});

test('the mode endpoint rejects unknown modes', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();

    $this->actingAs($user)
        ->from(route('lists.show', $list))
        ->patch(route('lists.mode.update', $list), ['mode' => 'nonsense'])
        ->assertSessionHasErrors('mode');

    expect($list->fresh()->mode)->toBe(AlbumListMode::Default);
});

test('users cannot change the mode of another users list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for(User::factory()->create())->create();

    $this->actingAs($user)
        ->patch(route('lists.mode.update', $list), ['mode' => 'listening'])
        ->assertForbidden();
});

test('guests cannot change the mode', function () {
    $list = AlbumList::factory()->for(User::factory()->create())->create();

    $this->patch(route('lists.mode.update', $list), ['mode' => 'listening'])
        ->assertRedirect(route('login'));
});

test('the list show page exposes the mode', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->listening()->for($user)->create();

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

test('the list dialogs include the mode field', function () {
    $create = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));
    $edit = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($create)->toContain('ListModeField');
    expect($edit)->toContain('ListModeField');
});

test('the edit dialog submits the system mode through the mode endpoint', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)
        ->toContain('isSystem')
        ->toContain('/mode`');
});
