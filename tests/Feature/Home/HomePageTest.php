<?php

use App\Models\Album;
use App\Models\AlbumList;
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

test('home page displays total lists count', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(3)->for($user)->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('totalLists', 5) // 3 custom + Listen Later (system) + Reviewed
        );
});

test('home page displays total albums count', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
    $albums = Album::factory()->count(5)->create();
    $list->albums()->attach($albums->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('totalAlbums', 5)
        );
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('mostPopulatedList.title', 'Big List')
            ->where('mostPopulatedList.albumsCount', 5)
        );
});

test('home page shows most populated list with zero albums when no albums exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('mostPopulatedList.albumsCount', 0)
        );
});

test('home page includes system lists in total count', function () {
    $user = User::factory()->create();

    // User gets the auto-created Listen Later + Reviewed system lists
    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('totalLists', 2)
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
            ->where('totalAlbums', 5)
        );
});

test('home page requires authentication', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});

test('home page returns inertia response with correct component', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('totalLists')
            ->has('totalAlbums')
        );
});

test('most populated list shows singular count for one album', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create(['title' => 'Solo List']);
    $list->albums()->attach(Album::factory()->create(), ['position' => 0]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('mostPopulatedList.title', 'Solo List')
            ->where('mostPopulatedList.albumsCount', 1)
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

test('home page uses shadcn card components', function () {
    // Verify the Home component imports and uses shadcn Card
    $content = file_get_contents(resource_path('js/Pages/Home.tsx'));

    expect($content)->toContain("from '@/components/ui/card'")
        ->and($content)->toContain('<Card>')
        ->and($content)->toContain('<CardHeader>')
        ->and($content)->toContain('<CardContent>')
        ->and($content)->toContain('<CardTitle');
});
