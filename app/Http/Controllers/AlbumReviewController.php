<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumReviewRequest;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use Illuminate\Http\JsonResponse;
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
}
