<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('home page displays welcome greeting with user name', function () {
    $user = User::factory()->create(['name' => 'Alice']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('auth.user.name', 'Alice')
        );
});

test('home page exposes total lists count in the stats payload', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(3)->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('stats.totalLists', 5) // 3 custom + Listen Later + Reviewed
            ->where('stats.userListCount', 3)
        );
});

test('home page exposes total albums count in the stats payload', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
    $albums = Album::factory()->count(5)->create();
    $list->albums()->attach($albums->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('stats.totalAlbums', 5)
        );
});

test('home page exposes the per-list overview with covers', function () {
    $user = User::factory()->create();

    $list = AlbumList::factory()->for($user)->create(['title' => 'Big List']);
    $albums = Album::factory()->count(5)->create();
    $list->albums()->attach($albums->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('lists', 3) // Listen Later + Reviewed + Big List
            ->where('lists.2.title', 'Big List')
            ->where('lists.2.albumsCount', 5)
        );
});

test('home page exposes zero stats for a fresh account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('stats.totalAlbums', 0)
            ->where('stats.reviewedCount', 0)
            ->where('stats.userListCount', 0)
        );
});

test('home page includes system lists in total list count', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('stats.totalLists', 2)
        );
});

test('home page counts albums across multiple lists', function () {
    $user = User::factory()->create();

    $listA = AlbumList::factory()->for($user)->create();
    $listB = AlbumList::factory()->for($user)->create();

    $albumsA = Album::factory()->count(3)->create();
    $listA->albums()->attach($albumsA->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $albumsB = Album::factory()->count(2)->create();
    $listB->albums()->attach($albumsB->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('stats.totalAlbums', 5)
        );
});

test('home page requires authentication', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});

test('home page returns inertia response with the design system stats block', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('stats.totalLists')
            ->has('stats.totalAlbums')
            ->has('stats.reviewedCount')
            ->has('stats.averageRating')
            ->has('lists')
        );
});

test('average rating is computed across the users reviews', function () {
    $user = User::factory()->create();
    $albums = Album::factory()->count(2)->create();

    AlbumReview::factory()->create(['user_id' => $user->id, 'album_id' => $albums[0]->id, 'rating' => 8.0]);
    AlbumReview::factory()->create(['user_id' => $user->id, 'album_id' => $albums[1]->id, 'rating' => 9.0]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('stats.averageRating', 8.5)
        );
});

test('home page shares auth user data', function () {
    $user = User::factory()->create(['name' => 'Test User']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('auth.user.id')
            ->where('auth.user.name', 'Test User')
        );
});

test('home page renders with the Hoopify design system primitives', function () {
    $content = file_get_contents(resource_path('js/Pages/Home.tsx'));

    expect($content)
        ->toContain("from '@/components/hoopify/")
        ->toContain('StatTile')
        ->toContain('ListCard')
        ->toContain('TopBar');
});
