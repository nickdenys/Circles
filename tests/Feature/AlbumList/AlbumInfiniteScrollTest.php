<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;

test('list detail page loads first 20 albums initially', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(25)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 20)
            ->where('albums.next_page_url', fn ($url) => $url !== null)
        );
});

test('list detail page loads all albums when under 20', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(5)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->has('albums.data', 5)
            ->where('albums.next_page_url', null)
        );
});

test('albums prop has next_page_url when more pages exist', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(25)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.next_page_url', fn ($url) => $url !== null)
        );
});

test('albums prop has null next_page_url when all albums fit on one page', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(3)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('albums.next_page_url', null)
        );
});

test('show page component uses InfiniteScroll from Inertia', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('InfiniteScroll');
    expect($content)->toContain("from '@inertiajs/react'");
});

test('show page component shows loading spinner while fetching', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('Loader2');
    expect($content)->toContain('animate-spin');
});

test('show page component renders scroll sentinel during loading', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('id="album-scroll-sentinel"');
});

test('InfiniteScroll component uses albums data prop', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('data="albums"');
});

test('show page deduplicates merged albums by id to avoid duplicate React keys', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('existingIds');
    expect($content)->toContain('!existingIds.has(a.id)');
});

test('json endpoint returns paginated album data', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(25)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $response = $this->actingAs($user)
        ->getJson(route('lists.show', $list));

    $response->assertSuccessful();

    $data = $response->json();
    expect($data)->toHaveKey('data');
    expect($data)->toHaveKey('next_page_url');
    expect($data['data'])->toHaveCount(20);
    expect($data['next_page_url'])->not->toBeNull();
});

test('json endpoint returns correct album fields', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 0]);

    $response = $this->actingAs($user)
        ->getJson(route('lists.show', $list));

    $first = $response->json('data.0');
    expect($first)->toHaveKeys([
        'id', 'spotify_id', 'title', 'artists', 'cover_url',
        'runtime_ms', 'album_type', 'total_tracks', 'release_date', 'spotify_uri',
    ]);
});

test('json endpoint second page returns remaining albums', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(25)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $response = $this->actingAs($user)
        ->getJson(route('lists.show', ['listSlug' => $list->slug, 'page' => 2]));

    $data = $response->json();
    expect($data['data'])->toHaveCount(5);
    expect($data['next_page_url'])->toBeNull();
});

test('json endpoint returns empty data when list has no albums', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson(route('lists.show', $list));

    expect($response->json('data'))->toBeEmpty();
    expect($response->json('next_page_url'))->toBeNull();
});

test('albums are ordered by position across pages', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(25)->create();

    // Attach in reverse order so position != creation order
    foreach ($albums->reverse()->values() as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $page1 = $this->actingAs($user)
        ->getJson(route('lists.show', $list))
        ->json('data');

    $page2 = $this->actingAs($user)
        ->getJson(route('lists.show', ['listSlug' => $list->slug, 'page' => 2]))
        ->json('data');

    // Verify we got all 25 unique albums across both pages
    $allIds = array_merge(array_column($page1, 'id'), array_column($page2, 'id'));
    expect(array_unique($allIds))->toHaveCount(25);
});

test('album count still displays correctly with pagination', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $albums = Album::factory()->count(25)->create();

    foreach ($albums as $index => $album) {
        $list->albums()->attach($album->id, ['position' => $index]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn ($page) => $page
            ->where('list.albumsCount', 25)
        );
});
