<?php

namespace App\Http\Controllers;

use App\Jobs\FetchAlbumGenres;
use App\Models\Album;
use App\Models\AlbumList;
use App\Models\AlbumListAlbum;
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
            FetchAlbumGenres::dispatch($album);
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
                'genres' => $album->genres ?? [],
                'note' => null,
            ],
        ], 201);
    }

    /**
     * Move an album from one list to another.
     */
    public function move(Request $request, AlbumList $albumList, Album $album): RedirectResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $request->validate([
            'destination_list_id' => ['required', 'integer', 'exists:album_lists,id'],
        ]);

        $destinationList = AlbumList::findOrFail($request->input('destination_list_id'));

        abort_unless($destinationList->user_id === $request->user()->id, 403);

        if ($destinationList->albums()->where('album_id', $album->id)->exists()) {
            return redirect()->route('lists.show', $albumList)
                ->with('error', 'Album already exists in the destination list.');
        }

        $maxPosition = $destinationList->albums()->max('album_album_list.position') ?? 0;

        AlbumListAlbum::query()
            ->where('album_list_id', $albumList->id)
            ->where('album_id', $album->id)
            ->update([
                'album_list_id' => $destinationList->id,
                'position' => $maxPosition + 1,
            ]);

        return redirect()->route('lists.show', $albumList);
    }

    /**
     * Reorder albums in the given list.
     */
    public function reorder(Request $request, AlbumList $albumList): JsonResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $request->validate([
            'album_ids' => ['required', 'array'],
            'album_ids.*' => ['required', 'integer'],
        ]);

        $albumIds = $request->input('album_ids');

        foreach ($albumIds as $position => $albumId) {
            $albumList->albums()->updateExistingPivot($albumId, [
                'position' => $position + 1,
            ]);
        }

        return response()->json(['message' => 'Order updated.']);
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
