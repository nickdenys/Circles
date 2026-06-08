<?php

use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use App\Models\User;

test('submitting a review from a custom list upserts the review, detaches the album, and ensures it lives in Reviewed', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
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

test('submitting a second review updates the same row and does not duplicate the Reviewed attachment', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
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
    $list = AlbumList::factory()->for($user)->create();
    $album = Album::factory()->create();

    $this->actingAs($user)
        ->postJson(route('lists.albums.review.store', [$list, $album]), ['rating' => 3.5])
        ->assertSuccessful();

    expect($user->reviewedList->albums()->where('album_id', $album->id)->exists())->toBeTrue();
});

test('rating validation rejects out-of-range and wrong-step values', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create();
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
