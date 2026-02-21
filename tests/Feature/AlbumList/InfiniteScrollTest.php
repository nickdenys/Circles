<?php

use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('lists page loads first 20 lists initially', function () {
    $user = User::factory()->create();
    // 25 custom + 1 system watchlist = 26 total
    AlbumList::factory()->count(25)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists', 20)
            ->where('nextPageUrl', fn ($url) => $url !== null)
        );
});

test('lists page loads all lists when under 20', function () {
    $user = User::factory()->create();
    // 5 custom + 1 system watchlist = 6 total
    AlbumList::factory()->count(5)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Index')
            ->has('lists', 6)
            ->where('nextPageUrl', null)
        );
});

test('scroll sentinel is rendered when more pages exist', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('id="scroll-sentinel"');
    expect($content)->toContain('Loading more lists...');
});

test('scroll sentinel depends on nextPageUrl prop', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('nextPageUrl');
    expect($content)->toContain('scroll-sentinel');
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
    // 26 total - 20 first page = 6 remaining
    expect($data['data'])->toHaveCount(6);
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
    // Create 25 custom lists that alphabetically come before "Watchlist"
    AlbumList::factory()->count(25)->create([
        'user_id' => $user->id,
        'title' => fn () => 'Z '.fake()->unique()->word(),
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('lists.index'));

    $data = $response->json('data');
    // Watchlist (system) should be first
    expect($data[0]['title'])->toBe('Watchlist');
});
