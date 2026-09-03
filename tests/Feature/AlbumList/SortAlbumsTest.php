<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
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

test('the reviewed list orders albums by score descending', function () {
    $user = User::factory()->create();
    $list = $user->reviewedList;
    $list->update(['sort' => 'score', 'direction' => 'desc']);

    foreach ([['Middling', 3.0], ['Great', 4.5], ['Poor', 1.5]] as $position => [$title, $rating]) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
        AlbumReview::factory()->create(['user_id' => $user->id, 'album_id' => $album->id, 'rating' => $rating]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Great')
            ->where('albums.data.1.title', 'Middling')
            ->where('albums.data.2.title', 'Poor')
            ->where('sort', 'score')
            ->where('direction', 'desc')
        );
});

test('the reviewed list orders albums by score ascending', function () {
    $user = User::factory()->create();
    $list = $user->reviewedList;
    $list->update(['sort' => 'score', 'direction' => 'asc']);

    foreach ([['Middling', 3.0], ['Great', 4.5], ['Poor', 1.5]] as $position => [$title, $rating]) {
        $album = Album::factory()->create(['title' => $title]);
        $list->albums()->attach($album->id, ['position' => $position + 1]);
        AlbumReview::factory()->create(['user_id' => $user->id, 'album_id' => $album->id, 'rating' => $rating]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Poor')
            ->where('albums.data.1.title', 'Middling')
            ->where('albums.data.2.title', 'Great')
        );
});

test('the score sort keeps unrated albums last in both directions', function () {
    $user = User::factory()->create();
    $list = $user->reviewedList;
    $list->update(['sort' => 'score', 'direction' => 'desc']);

    $unrated = Album::factory()->create(['title' => 'Unrated']);
    $rated = Album::factory()->create(['title' => 'Rated']);
    $list->albums()->attach($unrated->id, ['position' => 1]);
    $list->albums()->attach($rated->id, ['position' => 2]);
    AlbumReview::factory()->create(['user_id' => $user->id, 'album_id' => $rated->id, 'rating' => 2.0]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Rated')
            ->where('albums.data.1.title', 'Unrated')
        );

    $list->update(['direction' => 'asc']);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Rated')
            ->where('albums.data.1.title', 'Unrated')
        );
});

test('the score sort ignores ratings left by other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $list = $user->reviewedList;
    $list->update(['sort' => 'score', 'direction' => 'desc']);

    $mine = Album::factory()->create(['title' => 'Mine']);
    $theirs = Album::factory()->create(['title' => 'Theirs']);
    $list->albums()->attach($mine->id, ['position' => 1]);
    $list->albums()->attach($theirs->id, ['position' => 2]);

    AlbumReview::factory()->create(['user_id' => $user->id, 'album_id' => $mine->id, 'rating' => 1.0]);
    AlbumReview::factory()->create(['user_id' => $other->id, 'album_id' => $theirs->id, 'rating' => 5.0]);

    $this->actingAs($user)
        ->get(route('lists.show', $list))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Mine')
            ->where('albums.data.1.title', 'Theirs')
        );
});

test('the score sort can be stored on the reviewed list', function () {
    $user = User::factory()->create();
    $list = $user->reviewedList;

    $this->actingAs($user)
        ->patch(route('lists.sort.update', $list), ['sort' => 'score', 'direction' => 'desc'])
        ->assertRedirect(route('lists.show', $list));

    expect($list->fresh())
        ->sort->toBe('score')
        ->direction->toBe('desc');
});

test('the score sort is rejected on lists other than reviewed', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('lists.show', $list))
        ->patch(route('lists.sort.update', $list), ['sort' => 'score', 'direction' => 'desc'])
        ->assertSessionHasErrors('sort');

    expect($list->fresh()->sort)->toBe('manual');
});

test('a stored score sort on a non reviewed list falls back to the manual order', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->create(['user_id' => $user->id, 'sort' => 'score', 'direction' => 'asc']);

    foreach (['Zebra', 'Apple'] as $position => $title) {
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

test('the score sort survives infinite scroll pages', function () {
    $user = User::factory()->create();
    $list = $user->reviewedList;
    $list->update(['sort' => 'score', 'direction' => 'asc']);

    foreach (range(1, 25) as $number) {
        $album = Album::factory()->create(['title' => 'Album '.str_pad((string) $number, 2, '0', STR_PAD_LEFT)]);
        $list->albums()->attach($album->id, ['position' => $number]);
        AlbumReview::factory()->create([
            'user_id' => $user->id,
            'album_id' => $album->id,
            'rating' => round($number / 10, 1),
        ]);
    }

    $this->actingAs($user)
        ->get(route('lists.show', ['listSlug' => $list->slug, 'page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('albums.data.0.title', 'Album 21')
        );
});

test('the score sort option is only offered on the reviewed list', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain("SCORE_SORT_OPTION = { value: 'score', label: 'Score' }")
        ->toContain('? [SORT_OPTIONS[0], SCORE_SORT_OPTION, ...SORT_OPTIONS.slice(1)]')
        ->toContain('<SortControl');
});
