<?php

use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('lists page renders the Lists/Index Inertia component', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
        );
});

test('lists page displays all user lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(3)->create(['user_id' => $user->id]);

    // 3 custom + 1 system watchlist = 4
    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists.data', 4)
        );
});

test('system lists are displayed before custom lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Custom Alpha']);
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Custom Beta']);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->where('lists.data.0.title', 'Listen Later')
            ->where('lists.data.1.title', 'Custom Alpha')
            ->where('lists.data.2.title', 'Custom Beta')
        );
});

test('each list includes title and album count', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->where('lists.data.0.title', 'Listen Later')
            ->where('lists.data.0.albumsCount', 0)
        );
});

test('each list includes a url to the detail page', function () {
    $user = User::factory()->create();
    $list = $user->albumLists->first();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('lists.data.0.url', route('lists.show', $list))
        );
});

test('lists page only shows lists for the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $otherUser->id, 'title' => 'Other User List']);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists.data', 1)
            ->where('lists.data.0.title', 'Listen Later')
        );
});

test('empty state when user has no lists', function () {
    $user = User::factory()->create();
    $user->albumLists()->delete();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists.data', 0)
        );
});

test('each list includes preview covers from the first 3 albums', function () {
    $user = User::factory()->create();
    $list = $user->albumLists->first();

    $albums = \App\Models\Album::factory()->count(4)->create();
    $list->albums()->attach($albums->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('lists.data.0.previewCovers', 3)
            ->where('lists.data.0.previewCovers.0', $albums[0]->cover_url)
            ->where('lists.data.0.previewCovers.1', $albums[1]->cover_url)
            ->where('lists.data.0.previewCovers.2', $albums[2]->cover_url)
        );
});

test('preview covers is an empty array when list has no albums', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('lists.data.0.previewCovers', [])
        );
});

test('unauthenticated users cannot access the lists page', function () {
    $this->get(route('lists.index'))
        ->assertRedirect(route('login'));
});

test('page component contains Add List button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('Add List');
});

test('page component contains empty state message', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('No lists yet. Create one to get started.');
});

test('page component uses shadcn Card and Button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain("from '@/components/ui/card'");
    expect($content)->toContain("from '@/components/ui/button'");
});

test('page component uses Inertia Link for navigation', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain("from '@inertiajs/react'");
    expect($content)->toContain('<Link');
});

