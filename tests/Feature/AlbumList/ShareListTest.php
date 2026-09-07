<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('sharing a list mints a hash and switches sharing on', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('lists.share.store', $list->id))
        ->assertRedirect();

    $list->refresh();

    expect($list->share_hash)->not->toBeNull()
        ->and($list->isShared())->toBeTrue();
});

test('sharing an already shared list keeps the same hash', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('lists.share.store', $list->id));
    $hash = $list->refresh()->share_hash;

    $this->actingAs($user)->post(route('lists.share.store', $list->id));

    expect($list->refresh()->share_hash)->toBe($hash);
});

test('unsharing keeps the hash so the same link comes back on a re-share', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('lists.share.store', $list->id));
    $hash = $list->refresh()->share_hash;

    $this->actingAs($user)
        ->delete(route('lists.share.destroy', $list->id))
        ->assertRedirect();

    expect($list->refresh()->isShared())->toBeFalse()
        ->and($list->share_hash)->toBe($hash);

    $this->actingAs($user)->post(route('lists.share.store', $list->id));

    expect($list->refresh()->isShared())->toBeTrue()
        ->and($list->share_hash)->toBe($hash);
});

test('a user cannot share someone else\'s list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->post(route('lists.share.store', $list->id))
        ->assertForbidden();

    expect($list->refresh()->isShared())->toBeFalse();
});

test('a guest can open a shared list without an account', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Sunday Spins']);
    $list->share();

    $this->get($list->shareUrl())
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Lists/Shared')
            ->where('list.title', 'Sunday Spins')
        );
});

test('a shared list carries its albums', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'title', 'direction' => 'asc']);

    foreach (['Zebra', 'Apple'] as $position => $title) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
    }

    $list->share();

    $this->get($list->shareUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Apple')
            ->where('albums.data.1.title', 'Zebra')
            ->where('list.albumsCount', 2)
        );
});

test('a shared Reviewed list shows the owner scores', function () {
    $user = User::factory()->create();
    $list = $user->reviewedList;
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);
    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 4.5,
    ]);

    $list->share();

    $this->get($list->shareUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page->where('albums.data.0.rating', 4.5));
});

test('an unshared list is a 404 on its share url', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $list->share();
    $url = $list->shareUrl();

    $list->unshare();

    $this->get($url)->assertNotFound();
});

test('an unknown hash is a 404', function () {
    $this->get(route('lists.shared', ['shareHash' => 'nosuchhashatall']))->assertNotFound();
});

test('the shared page never carries the sidebar lists of the owner', function () {
    $user = User::factory()->create();
    AlbumList::factory()->create(['user_id' => $user->id, 'title' => 'Private Stash']);
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $list->share();

    $this->get($list->shareUrl())
        ->assertInertia(fn (AssertableInertia $page) => $page->where('sidebarLists', []))
        ->assertDontSee('Private Stash');
});

test('a guest reaching a shared list still cannot open the owner list pages', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);
    $list->share();

    $this->get($list->shareUrl())->assertSuccessful();

    $this->get(route('lists.show', ['listSlug' => $list->slug]))->assertRedirect(route('login'));
    $this->get(route('lists.index'))->assertRedirect(route('login'));
});

test('the list detail page passes the sharing state to the owner', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('list.isShared', false)
            ->where('list.shareUrl', null)
        );

    $list->share();

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('list.isShared', true)
            ->where('list.shareUrl', $list->shareUrl())
        );
});

test('the shared page renders outside the authenticated shell', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Shared.tsx'));

    expect($component)
        ->toContain('Shared.layout')
        ->toContain('PublicLayout');
});

test('the share button and dialog are wired into the list detail page', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('id="share-list-button"')
        ->toContain('ShareListDialog');
});
