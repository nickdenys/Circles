<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('refresh button is displayed on the list detail page', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="refresh-button"')
        ->toContain('Refresh');
});

test('refresh button is shown on both system and custom lists', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    // Refresh button is outside the custom-only conditional
    expect($component)->toContain('id="refresh-button"');

    // Verify it renders for system list (not conditionally hidden)
    $user = User::factory()->create();
    $systemList = AlbumList::factory()->system()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $systemList))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Lists/Show'));
});

test('refresh button has a loading state with spinner', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="refresh-spinner"')
        ->toContain('Refreshing...')
        ->toContain('refreshing');
});

test('clicking refresh updates album data from spotify', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album = Album::factory()->create([
        'spotify_id' => 'abc123',
        'title' => 'Old Title',
        'artists' => 'Old Artist',
        'total_tracks' => 10,
    ]);
    $list->albums()->attach($album->id, ['position' => 1]);

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response([
            'id' => 'abc123',
            'name' => 'Updated Title',
            'artists' => [['name' => 'Updated Artist']],
            'album_type' => 'album',
            'total_tracks' => 15,
            'release_date' => '2025-01-01',
            'images' => [['url' => 'https://i.scdn.co/image/updated']],
            'uri' => 'spotify:album:abc123',
            'tracks' => ['items' => [['duration_ms' => 300000]]],
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('lists.refresh', $list))
        ->assertRedirect(route('lists.show', $list));

    $album->refresh();
    expect($album->title)->toBe('Updated Title');
    expect($album->artists)->toBe('Updated Artist');
    expect($album->total_tracks)->toBe(15);
});

test('refresh bypasses spotify cache', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album = Album::factory()->create(['spotify_id' => 'abc123']);
    $list->albums()->attach($album->id, ['position' => 1]);

    $callCount = 0;

    Http::fake([
        'api.spotify.com/v1/albums/*' => function () use (&$callCount) {
            $callCount++;

            return Http::response([
                'id' => 'abc123',
                'name' => 'Album v'.$callCount,
                'artists' => [['name' => 'Artist']],
                'album_type' => 'album',
                'total_tracks' => 10,
                'release_date' => '2025-01-01',
                'images' => [['url' => 'https://i.scdn.co/image/cover']],
                'uri' => 'spotify:album:abc123',
                'tracks' => ['items' => [['duration_ms' => 200000]]],
            ]);
        },
    ]);

    // First call populates cache
    $this->actingAs($user)
        ->post(route('lists.refresh', $list));

    // Second call should still hit API (bypasses cache)
    $this->actingAs($user)
        ->post(route('lists.refresh', $list));

    expect($callCount)->toBe(2);
});

test('refresh handles empty list gracefully', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    Http::fake();

    $this->actingAs($user)
        ->post(route('lists.refresh', $list))
        ->assertRedirect(route('lists.show', $list));

    Http::assertNothingSent();
});

test('refresh handles failed spotify api responses gracefully', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album = Album::factory()->create([
        'spotify_id' => 'abc123',
        'title' => 'Original Title',
    ]);
    $list->albums()->attach($album->id, ['position' => 1]);

    Http::fake([
        'api.spotify.com/v1/albums/*' => Http::response([], 500),
    ]);

    $this->actingAs($user)
        ->post(route('lists.refresh', $list))
        ->assertRedirect(route('lists.show', $list));

    $album->refresh();
    expect($album->title)->toBe('Original Title');
});

test('users cannot refresh another users list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->post(route('lists.refresh', $list))
        ->assertForbidden();
});

test('unauthenticated users cannot refresh a list', function () {
    $list = AlbumList::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->post(route('lists.refresh', $list))
        ->assertRedirect(route('login'));
});

test('refresh updates multiple albums in the list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $album1 = Album::factory()->create(['spotify_id' => 'id1', 'title' => 'Old Title 1']);
    $album2 = Album::factory()->create(['spotify_id' => 'id2', 'title' => 'Old Title 2']);
    $list->albums()->attach($album1->id, ['position' => 1]);
    $list->albums()->attach($album2->id, ['position' => 2]);

    Http::fake([
        'api.spotify.com/v1/albums/id1' => Http::response([
            'id' => 'id1',
            'name' => 'New Title 1',
            'artists' => [['name' => 'Artist 1']],
            'album_type' => 'album',
            'total_tracks' => 10,
            'release_date' => '2025-01-01',
            'images' => [['url' => 'https://i.scdn.co/image/1']],
            'uri' => 'spotify:album:id1',
            'tracks' => ['items' => [['duration_ms' => 200000]]],
        ]),
        'api.spotify.com/v1/albums/id2' => Http::response([
            'id' => 'id2',
            'name' => 'New Title 2',
            'artists' => [['name' => 'Artist 2']],
            'album_type' => 'single',
            'total_tracks' => 3,
            'release_date' => '2024-06-15',
            'images' => [['url' => 'https://i.scdn.co/image/2']],
            'uri' => 'spotify:album:id2',
            'tracks' => ['items' => [['duration_ms' => 180000]]],
        ]),
    ]);

    $this->actingAs($user)
        ->post(route('lists.refresh', $list))
        ->assertRedirect(route('lists.show', $list));

    $album1->refresh();
    $album2->refresh();
    expect($album1->title)->toBe('New Title 1');
    expect($album2->title)->toBe('New Title 2');
    expect($album2->album_type)->toBe('single');
});

test('refresh button uses router.post to the refresh route', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)->toContain('/lists/${list.id}/refresh');
});
