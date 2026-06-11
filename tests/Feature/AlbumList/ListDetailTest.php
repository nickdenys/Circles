<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('list detail page renders the Lists/Show inertia component', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'My Favorites']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Lists/Show')
            ->where('list.title', 'My Favorites')
        );
});

test('list detail page passes the list description', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'description' => 'A collection of my favorite albums',
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.description', 'A collection of my favorite albums')
        );
});

test('list detail page passes null description when not set', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create([
        'user_id' => $user->id,
        'description' => null,
    ]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('list.description', null)
        );
});

test('list detail page passes album count', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.albumsCount', 0)
        );
});

test('list detail page passes list type for custom lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'type' => 'custom']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.type', 'custom')
        );
});

test('edit and delete buttons are rendered for custom lists', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="edit-list-button"')
        ->toContain('id="delete-list-button"')
        ->toContain("list.type === 'custom'");
});

test('edit and delete buttons are hidden for system lists', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.type', 'system')
        );

    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    expect($component)->toContain("list.type === 'custom'");
});

test('users cannot view another users list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);

    // Slug binding is per-user (App\Providers\AppServiceProvider::bindListSlug).
    // Cross-user access returns 404 rather than 403, so we don't leak existence.
    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertNotFound();
});

test('unauthenticated users cannot access the list detail page', function () {
    $list = AlbumList::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->get(route('lists.show', $list))
        ->assertRedirect(route('login'));
});

test('list detail page shows empty state when no albums', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)->toContain('No albums yet. Click "Add an Album" to get started.');
});

test('list detail page includes note field on each album via inertia', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(2)->create();

    foreach ($albums as $i => $album) {
        $list->albums()->attach($album->id, ['position' => $i + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 2)
            ->where('albums.data.0.note', null)
            ->where('albums.data.1.note', null)
        );
});

test('list detail json response includes note field on each album', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->getJson(route('lists.show', $list))
        ->assertOk()
        ->assertJsonPath('data.0.note', null);
});

test('list description is rendered with pre-line so newlines show as line breaks', function () {
    $show = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
    $index = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($show)->toContain("whiteSpace: 'pre-line'");
    expect($index)->toContain("whiteSpace: 'pre-line'");
});

test('list detail page uses Head component with list title', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('<Head title={list.title}')
        ->toContain("from '@inertiajs/react'");
});

test('list detail page renders a Total runtime stat alongside the other stat blocks', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('formatRuntimeStat')
        ->toContain("unit: 'min'")
        ->toContain("unit: 'hours'")
        ->toContain('caption="Albums filed"')
        ->toContain('caption="Total tracks"')
        ->toContain('caption="Total runtime"');
});

test('runtime helper switches to hours above 120 minutes and drops decimals above 20 hours', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('minutes <= 120')
        ->toContain('hours > 20')
        ->toContain('hours * 10');
});

test('list detail page exposes server-computed totals for tracks and runtime regardless of pagination', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $a = Album::factory()->create(['total_tracks' => 10, 'runtime_ms' => 1_800_000]);
    $b = Album::factory()->create(['total_tracks' => 12, 'runtime_ms' => 2_400_000]);
    $c = Album::factory()->create(['total_tracks' => 8, 'runtime_ms' => 900_000]);
    $list->albums()->attach($a->id, ['position' => 1]);
    $list->albums()->attach($b->id, ['position' => 2]);
    $list->albums()->attach($c->id, ['position' => 3]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.totalTracks', 30)
            ->where('list.totalRuntimeMs', 5_100_000)
        );
});

test('list detail page returns zero totals for an empty list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.totalTracks', 0)
            ->where('list.totalRuntimeMs', 0)
        );
});
