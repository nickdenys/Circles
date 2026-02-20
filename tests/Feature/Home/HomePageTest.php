<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('home page displays welcome greeting with user name', function () {
    $user = User::factory()->create(['name' => 'Alice']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Welcome back, Alice');
});

test('home page displays total lists count', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(3)->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Total Lists')
        ->assertViewHas('totalLists', 4); // 3 custom + 1 system Watchlist
});

test('home page displays total albums count', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
    $albums = Album::factory()->count(5)->create();
    $list->albums()->attach($albums->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Total Albums')
        ->assertViewHas('totalAlbums', 5);
});

test('home page displays most populated list', function () {
    $user = User::factory()->create();

    $smallList = AlbumList::factory()->for($user)->create(['title' => 'Small List']);
    $smallList->albums()->attach(Album::factory()->create(), ['position' => 0]);

    $bigList = AlbumList::factory()->for($user)->create(['title' => 'Big List']);
    $albums = Album::factory()->count(5)->create();
    $bigList->albums()->attach($albums->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Most Populated List')
        ->assertSee('Big List')
        ->assertSee('5 albums');
});

test('home page shows dash when no albums exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Most Populated List')
        ->assertSee('&mdash;', false);
});

test('home page includes system list in total count', function () {
    $user = User::factory()->create();

    // User gets auto-created Watchlist (1 system list)
    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertViewHas('totalLists', 1);
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
        ->assertViewHas('totalAlbums', 5);
});

test('home page requires authentication', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});

test('home page uses app layout with title', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Hoopify');
});

test('most populated list shows singular album for count of one', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create(['title' => 'Solo List']);
    $list->albums()->attach(Album::factory()->create(), ['position' => 0]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Solo List')
        ->assertSee('1 album');
});
