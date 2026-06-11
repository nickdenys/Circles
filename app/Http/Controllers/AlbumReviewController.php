<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumReviewRequest;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumListAlbum;
use App\Models\AlbumReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlbumReviewController extends Controller
{
    /**
     * Store a rating + review for the given album and route it into the user's Reviewed list.
     */
    public function store(StoreAlbumReviewRequest $request, AlbumList $albumList, Album $album): JsonResponse
    {
        $user = $request->user();

        $reviewedList = DB::transaction(function () use ($request, $user, $albumList, $album): AlbumList {
            $attributes = [
                'rating' => $request->validated('rating'),
                'review' => $request->validated('review'),
            ];

            if (! $albumList->isReviewed()) {
                $sourcePivot = AlbumListAlbum::query()
                    ->where('album_list_id', $albumList->id)
                    ->where('album_id', $album->id)
                    ->first();

                if ($sourcePivot) {
                    $attributes['source_album_list_id'] = $albumList->id;
                    $attributes['source_position'] = $sourcePivot->position;
                    $attributes['source_created_at'] = $sourcePivot->created_at;
                }
            }

            AlbumReview::query()->updateOrCreate(
                ['user_id' => $user->id, 'album_id' => $album->id],
                $attributes,
            );

            $reviewedList = $user->reviewedList()->firstOrFail();

            if (! $albumList->isReviewed()) {
                $albumList->albums()->detach($album->id);

                if (! $reviewedList->albums()->where('album_id', $album->id)->exists()) {
                    $maxPosition = $reviewedList->albums()->max('album_album_list.position') ?? 0;

                    $reviewedList->albums()->attach($album->id, [
                        'position' => $maxPosition + 1,
                    ]);
                }
            }

            return $reviewedList;
        });

        return response()->json([
            'rating' => (float) $request->validated('rating'),
            'review' => $request->validated('review'),
            'reviewedListId' => $reviewedList->id,
            'reviewedListSlug' => $reviewedList->slug,
        ]);
    }

    /**
     * Delete the review for the given album and move it back to the user's library.
     *
     * Without `restore_to_source`, the album lands at the end of Listen Later. With
     * `restore_to_source`, the album is placed back into the list and position it
     * came from (recorded when the review was first created); existing albums at or
     * after that position shift one step to make room. Falls back to Listen Later if
     * the source list no longer exists.
     */
    public function destroy(Request $request, AlbumList $albumList, Album $album): JsonResponse
    {
        $user = $request->user();

        abort_unless($albumList->user_id === $user->id, 403);
        abort_unless($albumList->isReviewed(), 403);
        abort_unless($albumList->albums()->where('album_id', $album->id)->exists(), 404);

        $review = AlbumReview::query()
            ->where('user_id', $user->id)
            ->where('album_id', $album->id)
            ->first();

        abort_unless($review, 404);

        $restoreToSource = $request->boolean('restore_to_source');

        $targetList = DB::transaction(function () use ($user, $albumList, $album, $review, $restoreToSource): AlbumList {
            $targetList = null;
            $targetPosition = null;

            if ($restoreToSource && $review->source_album_list_id !== null) {
                $candidate = AlbumList::query()
                    ->where('id', $review->source_album_list_id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($candidate) {
                    $targetList = $candidate;
                    $targetPosition = $review->source_position;
                }
            }

            if ($targetList === null) {
                $targetList = $user->listenLaterList()->firstOrFail();
            }

            $review->delete();
            $albumList->albums()->detach($album->id);

            if ($targetList->albums()->where('album_id', $album->id)->exists()) {
                return $targetList;
            }

            if ($targetPosition !== null) {
                AlbumListAlbum::query()
                    ->where('album_list_id', $targetList->id)
                    ->where('position', '>=', $targetPosition)
                    ->increment('position');

                $now = now();
                AlbumListAlbum::query()->insert([
                    'album_list_id' => $targetList->id,
                    'album_id' => $album->id,
                    'position' => $targetPosition,
                    'created_at' => $review->source_created_at ?? $now,
                    'updated_at' => $now,
                ]);
            } else {
                $maxPosition = $targetList->albums()->max('album_album_list.position') ?? 0;

                $targetList->albums()->attach($album->id, [
                    'position' => $maxPosition + 1,
                ]);
            }

            return $targetList;
        });

        $listenLaterList = $user->listenLaterList()->firstOrFail();

        return response()->json([
            'ok' => true,
            'restoredListId' => $targetList->id,
            'restoredListSlug' => $targetList->slug,
            'listenLaterListId' => $listenLaterList->id,
            'listenLaterListSlug' => $listenLaterList->slug,
        ]);
    }
}
