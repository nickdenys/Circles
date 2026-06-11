<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the stored sort orders albums by title ascending', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'title', 'direction' => 'asc']);

    foreach (['Zebra', 'Apple', 'Mango'] as $position => $title) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Apple')
            ->where('albums.data.1.title', 'Mango')
            ->where('albums.data.2.title', 'Zebra')
            ->where('sort', 'title')
            ->where('direction', 'asc')
        );
});

test('the stored sort orders albums by title descending', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'title', 'direction' => 'desc']);

    foreach (['Apple', 'Mango', 'Zebra'] as $position => $title) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Zebra')
            ->where('albums.data.1.title', 'Mango')
            ->where('albums.data.2.title', 'Apple')
        );
});

test('a list defaults to the manual position order', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    expect($list->fresh()->sort)->toBe('manual');

    foreach (['Zebra', 'Apple', 'Mango'] as $position => $title) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Zebra')
            ->where('albums.data.1.title', 'Apple')
            ->where('albums.data.2.title', 'Mango')
            ->where('sort', 'manual')
            ->where('direction', 'asc')
        );
});

test('the stored sort orders by release date chronologically across mixed granularity', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'release_date', 'direction' => 'asc']);

    foreach (['2024-05-01', '2023-12-31', '2024', '2024-01-15'] as $position => $releaseDate) {
        $album = Album::factory()->create(['release_date' => $releaseDate]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.releaseDate', '2023-12-31')
            ->where('albums.data.1.releaseDate', '2024')
            ->where('albums.data.2.releaseDate', '2024-01-15')
            ->where('albums.data.3.releaseDate', '2024-05-01')
        );
});

test('the stored sort orders by date added to the list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'added', 'direction' => 'desc']);

    $older = Album::factory()->create();
    $newer = Album::factory()->create();

    $this->travelTo(now()->subDays(2));
    $list->albums()->attach($older->id, ['position' => 1]);
    $this->travelBack();

    $list->albums()->attach($newer->id, ['position' => 2]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.id', $newer->id)
            ->where('albums.data.1.id', $older->id)
        );
});

test('an unrecognised stored sort falls back to the manual position order', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'garbage']);

    foreach (['Zebra', 'Apple', 'Mango'] as $position => $title) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Zebra')
            ->where('sort', 'manual')
        );
});

test('updating the sort persists it to the list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('lists.sort.update', $list), ['sort' => 'title', 'direction' => 'desc'])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh())
        ->sort->toBe('title')
        ->direction->toBe('desc');
});

test('the sort can be set back to manual', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'title', 'direction' => 'desc']);

    $this->actingAs($user)
        ->patch(route('lists.sort.update', $list), ['sort' => 'manual', 'direction' => 'asc'])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh()->sort)->toBe('manual');
});

test('the sort must be a known value', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('lists.show', $list))
        ->patch(route('lists.sort.update', $list), ['sort' => 'garbage', 'direction' => 'asc'])
        ->assertSessionHasErrors('sort');

    expect($list->fresh()->sort)->toBe('manual');
});

test('the direction must be asc or desc', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('lists.show', $list))
        ->patch(route('lists.sort.update', $list), ['sort' => 'title', 'direction' => 'sideways'])
        ->assertSessionHasErrors('direction');
});

test('users cannot change the sort of another users list', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs($user)
        ->patch(route('lists.sort.update', $list), ['sort' => 'title', 'direction' => 'asc'])
        ->assertForbidden();
});

test('guests cannot change the sort', function () {
    $list = AlbumList::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->patch(route('lists.sort.update', $list), ['sort' => 'title', 'direction' => 'asc'])
        ->assertRedirect(route('login'));
});

test('system lists can be sorted', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->system()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('lists.sort.update', $list), ['sort' => 'title', 'direction' => 'asc'])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh()->sort)->toBe('title');
});

test('infinite scroll keeps the stored sort on later pages', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'title', 'direction' => 'asc']);

    foreach (range(1, 25) as $number) {
        $album = Album::factory()->create(['title' => 'Album '.str_pad((string) $number, 2, '0', STR_PAD_LEFT)]);
        $list->albums()->attach($album->id, ['position' => $number]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', ['listSlug' => $list->slug, 'page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Album 21')
        );
});

test('list detail page renders the sort control', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)->toContain('id="sort-control"');
});

test('changing the sort persists through the sort endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('router.patch(`/lists/${listId}/sort`')
        ->toContain('sort: value');
});

test('albums are not draggable while a sort is active', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('draggable={isManual && !isReviewedList}')
        ->toContain('disabled: !draggable');
});
