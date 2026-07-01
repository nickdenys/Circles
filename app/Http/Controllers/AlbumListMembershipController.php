<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlbumListMembershipController extends Controller
{
    /**
     * Return the ids of the current user's lists that already contain the album.
     */
    public function __invoke(Request $request, Album $album): JsonResponse
    {
        $listIds = $request->user()
            ->albumLists()
            ->whereHas('albums', fn ($query) => $query->whereKey($album->id))
            ->pluck('album_lists.id')
            ->all();

        return response()->json(['data' => $listIds]);
    }
}
