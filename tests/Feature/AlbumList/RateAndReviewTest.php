<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use App\Models\User;

test('submitting a review from a listening list upserts the review, detaches the album, and ensures it lives in Reviewed', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), [
            'rating' => 4.5,
            'review' => 'Solid record',
        ])
        ->assertSuccessful()
        ->assertJsonPath('rating', 4.5)
        ->assertJsonPath('review', 'Solid record');

    expect(AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->first())
        ->rating->toEqual(4.5)
        ->review->toBe('Solid record');

    expect($list->fresh()->albums()->where('album_id', $album->id)->exists())->toBeFalse();
    expect($user->reviewedList->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('submitting a review from a default custom list is forbidden', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), [
            'rating' => 4.5,
            'review' => 'Solid record',
        ])
        ->assertForbidden();

    expect(AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->exists())->toBeFalse();
    expect($list->fresh()->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('submitting a second review updates the same row and does not duplicate the Reviewed attachment', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 3.0])
        ->assertSuccessful();

    $reviewedList = $user->reviewedList;

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$reviewedList, $album]), [
            'rating' => 5.0,
            'review' => 'Grown on me',
        ])
        ->assertSuccessful();

    expect(AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->count())->toBe(1);
    expect(AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->first())
        ->rating->toEqual(5.0)
        ->review->toBe('Grown on me');
    expect($reviewedList->fresh()->albums()->where('album_id', $album->id)->count())->toBe(1);
});

test('submitting a review from the Reviewed list does not detach the album', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $reviewedList = $user->reviewedList;
    $reviewedList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$reviewedList, $album]), [
            'rating' => 2.5,
            'review' => 'mid',
        ])
        ->assertSuccessful();

    expect($reviewedList->fresh()->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('reviewing an album that is not in any list still attaches it to Reviewed', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;
    $album = Album::factory()->create();

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 3.5])
        ->assertSuccessful();

    expect($user->reviewedList->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('rating validation rejects out-of-range and wrong-step values', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;
    $album = Album::factory()->create();

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors('rating');

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 5.5])
        ->assertStatus(422)
        ->assertJsonValidationErrors('rating');

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 1.3])
        ->assertStatus(422)
        ->assertJsonValidationErrors('rating');

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('rating');
});

test('users cannot submit reviews against another users list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = AlbumList::factory()->for($owner)->create();
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($other)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 4.0])
        ->assertForbidden();
});

test('guests cannot submit reviews', function () {
    $list = AlbumList::factory()->for(User::factory()->create())->create();
    $album = Album::factory()->create();

    $this->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 4.0])
        ->assertUnauthorized();
});

test('the show page wires the listened button to the rating dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('RatingDialog')
        ->toContain('handleRateAlbum')
        ->toContain('handleReviewSubmitted')
        ->toContain("listType === 'reviewed'")
        ->toContain('reviewed-card');
});

test('the show page does not wire a main click action on reviewed-list album cards', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->not->toContain('isReviewedList ? onRate : onMore')
        ->not->toContain('isReviewedList ? onRate : undefined');
});

test('the show page exposes an Edit review menu item for reviewed-list albums', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('Edit review')
        ->toContain('edit-review-button');
});

test('the rating dialog posts to the review endpoint', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RatingDialog.tsx'));

    expect($content)
        ->toContain('/review`')
        ->toContain('StarRating')
        ->toContain('Textarea');
});

test('the move dialog excludes the Reviewed list as a target', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('exclude_reviewed');
});

test('the rating dialog renders the index-card review design', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RatingDialog.tsx'));

    expect($content)
        ->toContain('CardModal')
        ->toContain('StarRating')
        ->toContain('ScoreReadout')
        ->toContain('Your rating')
        ->toContain('Save review');
});

test('the hoopify kit exposes the review-card primitives', function () {
    $index = file_get_contents(resource_path('js/components/hoopify/index.ts'));

    expect($index)
        ->toContain('CardModal')
        ->toContain('StarRating')
        ->toContain('ScoreReadout');

    expect(file_exists(resource_path('js/components/hoopify/CardModal.tsx')))->toBeTrue();
    expect(file_exists(resource_path('js/components/hoopify/StarRating.tsx')))->toBeTrue();
});

test('reviewing from a non-Reviewed list records the source list, position, and original added timestamp', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;
    $album = Album::factory()->create();
    \App\Models\AlbumListAlbum::query()->insert([
        'album_list_id' => $list->id,
        'album_id' => $album->id,
        'position' => 7,
        'created_at' => '2020-01-15 10:00:00',
        'updated_at' => '2020-01-15 10:00:00',
    ]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 4.0])
        ->assertSuccessful();

    $review = AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->first();
    expect($review->source_album_list_id)->toBe($list->id);
    expect($review->source_position)->toBe(7);
    expect($review->source_created_at?->format('Y-m-d H:i:s'))->toBe('2020-01-15 10:00:00');
});

test('un-review with restore_to_source preserves the pivot created_at so date-added sort orders match the original', function () {
    $user = User::factory()->create();
    $listenLater = $user->listenLaterList;
    $album = Album::factory()->create();
    \App\Models\AlbumListAlbum::query()->insert([
        'album_list_id' => $listenLater->id,
        'album_id' => $album->id,
        'position' => 3,
        'created_at' => '2020-02-01 09:00:00',
        'updated_at' => '2020-02-01 09:00:00',
    ]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$listenLater, $album]), ['rating' => 4.0])
        ->assertSuccessful();

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$user->reviewedList, $album]).'?restore_to_source=1')
        ->assertSuccessful();

    $pivot = \App\Models\AlbumListAlbum::query()
        ->where('album_list_id', $listenLater->id)
        ->where('album_id', $album->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->position)->toBe(3);
    expect($pivot->created_at->format('Y-m-d H:i:s'))->toBe('2020-02-01 09:00:00');
});

test('reviewing from the Reviewed list does not overwrite a previously recorded source', function () {
    $user = User::factory()->create();
    $list = $user->listenLaterList;
    $album = Album::factory()->create();
    $list->albums()->attach($album->id, ['position' => 3]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 3.0])
        ->assertSuccessful();

    $reviewedList = $user->reviewedList;

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$reviewedList, $album]), ['rating' => 4.5])
        ->assertSuccessful();

    $review = AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->first();
    expect($review->source_album_list_id)->toBe($list->id);
    expect($review->source_position)->toBe(3);
});

test('un-review with restore_to_source restores the album at its original source position and shifts later albums', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->for($user)->create(['mode' => 'listening']);
    $album = Album::factory()->create();
    $sourceList->albums()->attach($album->id, ['position' => 2]);

    $other = Album::factory()->create();
    $another = Album::factory()->create();
    $sourceList->albums()->attach($other->id, ['position' => 1]);
    $sourceList->albums()->attach($another->id, ['position' => 3]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$sourceList, $album]), ['rating' => 4.0])
        ->assertSuccessful();

    $reviewedList = $user->reviewedList;

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$reviewedList, $album]).'?restore_to_source=1')
        ->assertSuccessful()
        ->assertJsonPath('restoredListId', $sourceList->id);

    $sourceList->refresh();
    expect($sourceList->albums()->where('album_id', $other->id)->first()->pivot->position)->toBe(1);
    expect($sourceList->albums()->where('album_id', $album->id)->first()->pivot->position)->toBe(2);
    expect($sourceList->albums()->where('album_id', $another->id)->first()->pivot->position)->toBe(4);
    expect($user->listenLaterList->albums()->where('album_id', $album->id)->exists())->toBeFalse();
});

test('un-review with restore_to_source falls back to Listen Later when the source list is gone', function () {
    $user = User::factory()->create();
    $sourceList = AlbumList::factory()->for($user)->create(['mode' => 'listening']);
    $album = Album::factory()->create();
    $sourceList->albums()->attach($album->id, ['position' => 2]);

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$sourceList, $album]), ['rating' => 4.0])
        ->assertSuccessful();

    $sourceList->delete();

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$user->reviewedList, $album]).'?restore_to_source=1')
        ->assertSuccessful()
        ->assertJsonPath('restoredListId', $user->listenLaterList->id);

    expect($user->listenLaterList->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('un-review deletes the review row, detaches from Reviewed, and attaches to Listen Later', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);
    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 4.5,
        'review' => 'great',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$reviewedList, $album]))
        ->assertSuccessful()
        ->assertExactJson([
            'ok' => true,
            'restoredListId' => $user->listenLaterList->id,
            'restoredListSlug' => $user->listenLaterList->slug,
            'listenLaterListId' => $user->listenLaterList->id,
            'listenLaterListSlug' => $user->listenLaterList->slug,
        ]);

    expect(AlbumReview::where('user_id', $user->id)->where('album_id', $album->id)->exists())->toBeFalse();
    expect($reviewedList->fresh()->albums()->where('album_id', $album->id)->exists())->toBeFalse();
    expect($user->listenLaterList->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('un-review is idempotent when the album is already in Listen Later', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $listenLaterList = $user->listenLaterList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);
    $listenLaterList->albums()->attach($album->id, ['position' => 1]);
    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 3.0,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$reviewedList, $album]))
        ->assertSuccessful();

    expect($listenLaterList->fresh()->albums()->where('album_id', $album->id)->count())->toBe(1);
});

test('un-review is forbidden when called against a non-Reviewed list', function () {
    $user = User::factory()->create();
    $customList = AlbumList::factory()->for($user)->create();
    $album = Album::factory()->create();
    $customList->albums()->attach($album->id, ['position' => 1]);
    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 3.0,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$customList, $album]))
        ->assertForbidden();
});

test('un-review returns 404 when the album is not in the Reviewed list', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 3.0,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$reviewedList, $album]))
        ->assertNotFound();
});

test('un-review returns 404 when no review row exists', function () {
    $user = User::factory()->create();
    $reviewedList = $user->reviewedList;
    $album = Album::factory()->create();
    $reviewedList->albums()->attach($album->id, ['position' => 1]);

    $this->actingAs($user)
        ->deleteJson(route('lists.albums.review.destroy', [$reviewedList, $album]))
        ->assertNotFound();
});

test('users cannot un-review against another users Reviewed list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $album = Album::factory()->create();
    $owner->reviewedList->albums()->attach($album->id, ['position' => 1]);
    AlbumReview::factory()->create([
        'user_id' => $owner->id,
        'album_id' => $album->id,
        'rating' => 4.0,
    ]);

    $this->actingAs($other)
        ->deleteJson(route('lists.albums.review.destroy', [$owner->reviewedList, $album]))
        ->assertForbidden();
});

test('guests cannot un-review', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $user->reviewedList->albums()->attach($album->id, ['position' => 1]);
    AlbumReview::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'rating' => 4.0,
    ]);

    $this->deleteJson(route('lists.albums.review.destroy', [$user->reviewedList, $album]))
        ->assertUnauthorized();
});

test('the show page wires the rating dialog with un-review on the Reviewed list', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('allowUnreview={isReviewedList}')
        ->toContain('handleAlbumUnreviewed')
        ->toContain('onUnreviewed={handleAlbumUnreviewed}');
});

test('the rating dialog can delete a review via the DELETE endpoint', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RatingDialog.tsx'));

    expect($content)
        ->toContain('.delete(`/lists/')
        ->toContain('/review`)')
        ->toContain('AlertDialog')
        ->toContain('Un-review');
});

test('the rating dialog toasts undo and view-list actions after moving an album to Reviewed', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RatingDialog.tsx'));

    expect($content)
        ->toContain('reviewedListId')
        ->toContain('to Reviewed')
        ->toContain("label: 'Undo'")
        ->toContain('undoReview(reviewedListId')
        ->toContain('restore_to_source')
        ->toContain("label: 'View list'")
        ->toContain('router.visit(`/lists/');
});

test('the rating dialog toasts a link to the Listen Later list after un-reviewing', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RatingDialog.tsx'));

    expect($content)
        ->toContain('listenLaterListSlug')
        ->toContain('to Listen Later');
});

test('the Reviewed list uses its own empty-state copy', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('No reviewed albums yet.')
        ->toContain("Rate an album from any list and it'll show up here.")
        ->toContain('isReviewedList');
});
