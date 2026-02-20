<?php

namespace App\Http\Controllers;

use App\Services\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpotifySearchController extends Controller
{
    /**
     * Search for albums on Spotify.
     */
    public function albums(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $spotify = new SpotifyService($request->user());

        $results = $spotify->searchAlbums($request->input('q'));

        return response()->json(['data' => $results]);
    }
}
