<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\AlbumList;
use App\Services\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlbumListAlbumController extends Controller
{
    /**
     * Add an album to the given list.
     */
    public function store(Request $request, AlbumList $albumList): JsonResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $request->validate([
            'spotify_id' => ['required', 'string'],
        ]);

        $spotifyId = $request->input('spotify_id');

        if ($albumList->albums()->where('spotify_id', $spotifyId)->exists()) {
            return response()->json(['message' => 'Album already in this list.'], 409);
        }

        $album = Album::where('spotify_id', $spotifyId)->first();

        if (! $album) {
            $spotify = new SpotifyService($request->user());
            $albumData = $spotify->getAlbum($spotifyId);

            if (! $albumData) {
                return response()->json(['message' => 'Album not found on Spotify.'], 404);
            }

            $album = Album::create($albumData);
        }

        $maxPosition = $albumList->albums()->max('album_album_list.position') ?? 0;

        $albumList->albums()->attach($album->id, [
            'position' => $maxPosition + 1,
        ]);

        return response()->json([
            'data' => [
                'id' => $album->id,
                'spotify_id' => $album->spotify_id,
                'title' => $album->title,
                'artists' => $album->artists,
                'cover_url' => $album->cover_url,
                'runtime_ms' => $album->runtime_ms,
                'album_type' => $album->album_type,
                'total_tracks' => $album->total_tracks,
                'release_date' => $album->release_date,
                'spotify_uri' => $album->spotify_uri,
            ],
        ], 201);
    }

    /**
     * Remove an album from the given list.
     */
    public function destroy(Request $request, AlbumList $albumList, Album $album): RedirectResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $albumList->albums()->detach($album->id);

        return redirect()->route('lists.show', $albumList);
    }
}
