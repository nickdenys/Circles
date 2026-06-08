<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumReviewRequest;
use App\Models\Album;
use App\Models\AlbumList;
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
            AlbumReview::query()->updateOrCreate(
                ['user_id' => $user->id, 'album_id' => $album->id],
                [
                    'rating' => $request->validated('rating'),
                    'review' => $request->validated('review'),
                ],
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
        ]);
    }

    /**
     * Delete the review for the given album and move it back to the user's Listen Later list.
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

        $listenLaterList = DB::transaction(function () use ($user, $albumList, $album, $review): AlbumList {
            $review->delete();

            $albumList->albums()->detach($album->id);

            $listenLaterList = $user->listenLaterList()->firstOrFail();

            if (! $listenLaterList->albums()->where('album_id', $album->id)->exists()) {
                $maxPosition = $listenLaterList->albums()->max('album_album_list.position') ?? 0;

                $listenLaterList->albums()->attach($album->id, [
                    'position' => $maxPosition + 1,
                ]);
            }

            return $listenLaterList;
        });

        return response()->json([
            'ok' => true,
            'listenLaterListId' => $listenLaterList->id,
        ]);
    }
}
