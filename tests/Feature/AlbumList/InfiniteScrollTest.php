<?php

use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('lists page loads first 20 lists initially', function () {
    $user = User::factory()->create();
    // 25 custom + Listen Later + Reviewed = 27 total
    AlbumList::factory()->count(25)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists.data', 20)
        );
});

test('lists page loads all lists when under 20', function () {
    $user = User::factory()->create();
    // 5 custom + Listen Later + Reviewed = 7 total
    AlbumList::factory()->count(5)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists.data', 7)
            ->where('lists.next_page_url', null)
        );
});

test('lists prop includes next_page_url when more pages exist', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(25)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('lists.next_page_url', fn ($url) => $url !== null)
        );
});

test('page component uses InfiniteScroll from Inertia', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('InfiniteScroll');
    expect($content)->toContain("from '@inertiajs/react'");
});

test('page component shows loading spinner while fetching', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('Loader2');
    expect($content)->toContain('animate-spin');
});

test('page component renders scroll sentinel during loading', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('id="scroll-sentinel"');
});

test('InfiniteScroll component uses lists data prop', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('data="lists"');
});

test('json endpoint returns paginated lists data', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(25)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson(route('lists.index'));

    $response->assertSuccessful();

    $data = $response->json();
    expect($data)->toHaveKey('data');
    expect($data)->toHaveKey('next_page_url');
    expect($data['data'])->toHaveCount(20);
    expect($data['next_page_url'])->not->toBeNull();
});

test('json endpoint returns correct list fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('lists.index'));

    $first = $response->json('data.0');
    expect($first)->toHaveKeys(['id', 'title', 'albums_count', 'url']);
});

test('json endpoint second page returns remaining lists', function () {
    $user = User::factory()->create();
    AlbumList::factory()->count(25)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson(route('lists.index', ['page' => 2]));

    $data = $response->json();
    // 27 total - 20 first page = 7 remaining
    expect($data['data'])->toHaveCount(7);
    expect($data['next_page_url'])->toBeNull();
});

test('json endpoint returns empty data when no lists exist', function () {
    $user = User::factory()->create();
    $user->albumLists()->delete();

    $response = $this->actingAs($user)
        ->getJson(route('lists.index'));

    expect($response->json('data'))->toBeEmpty();
    expect($response->json('next_page_url'))->toBeNull();
});

test('system lists appear first across paginated results', function () {
    $user = User::factory()->create();
    // Create 25 custom lists that alphabetically come before "Listen Later"
    AlbumList::factory()->count(25)->create([
        'user_id' => $user->id,
        'title' => fn () => 'Z '.fake()->unique()->word(),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('lists.index'));

    $data = $response->json('data');
    // Listen Later (system) should be first
    expect($data[0]['title'])->toBe('Listen Later');
});
