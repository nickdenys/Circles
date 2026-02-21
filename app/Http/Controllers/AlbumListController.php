<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyAlbumListRequest;
use App\Http\Requests\StoreAlbumListRequest;
use App\Http\Requests\UpdateAlbumListRequest;
use App\Models\AlbumList;
use App\Services\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlbumListController extends Controller
{
    /**
     * Display the lists overview page.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $query = $request->user()
            ->albumLists()
            ->withCount('albums')
            ->orderByRaw("CASE WHEN type = 'system' THEN 0 ELSE 1 END")
            ->orderBy('title');

        if ($request->wantsJson()) {
            $lists = $query->simplePaginate(20);

            return response()->json([
                'data' => $lists->getCollection()->map(fn ($list) => [
                    'id' => $list->id,
                    'title' => $list->title,
                    'albums_count' => $list->albums_count ?? 0,
                    'url' => route('lists.show', $list),
                ]),
                'next_page_url' => $lists->nextPageUrl(),
            ]);
        }

        return Inertia::render('Lists/Index', [
            'lists' => Inertia::scroll(
                fn () => $query->simplePaginate(20)->through(fn ($list) => [
                    'id' => $list->id,
                    'title' => $list->title,
                    'albumsCount' => $list->albums_count ?? 0,
                    'url' => route('lists.show', $list),
                ])
            ),
        ]);
    }

    /**
     * Display the list detail page.
     */
    public function show(Request $request, AlbumList $albumList): Response|JsonResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $albumList->loadCount('albums');

        if ($request->wantsJson()) {
            $albums = $albumList->albums()->simplePaginate(20);

            return response()->json([
                'data' => $albums->getCollection()->map(fn ($album) => [
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
                ]),
                'next_page_url' => $albums->nextPageUrl(),
            ]);
        }

        return Inertia::render('Lists/Show', [
            'list' => [
                'id' => $albumList->id,
                'title' => $albumList->title,
                'description' => $albumList->description,
                'type' => $albumList->type,
                'albumsCount' => $albumList->albums_count,
            ],
            'albums' => Inertia::scroll(
                fn () => $albumList->albums()->simplePaginate(20)->through(fn ($album) => [
                    'id' => $album->id,
                    'spotifyId' => $album->spotify_id,
                    'title' => $album->title,
                    'artists' => $album->artists,
                    'coverUrl' => $album->cover_url,
                    'runtimeMs' => $album->runtime_ms,
                    'albumType' => $album->album_type,
                    'totalTracks' => $album->total_tracks,
                    'releaseDate' => $album->release_date,
                    'spotifyUri' => $album->spotify_uri,
                ])
            ),
        ]);
    }

    /**
     * Search user's lists for autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'exclude' => ['nullable', 'integer'],
        ]);

        $query = $request->input('q');
        $exclude = $request->input('exclude');

        $lists = $request->user()
            ->albumLists()
            ->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))
            ->orderByRaw("CASE WHEN type = 'system' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'title', 'type']);

        return response()->json([
            'data' => $lists->map(fn ($list) => [
                'id' => $list->id,
                'title' => $list->title,
                'type' => $list->type,
            ]),
        ]);
    }

    /**
     * Store a newly created custom list.
     */
    public function store(StoreAlbumListRequest $request): RedirectResponse
    {
        $request->user()->albumLists()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'type' => 'custom',
        ]);

        return redirect()->route('lists.index');
    }

    /**
     * Update the specified custom list.
     */
    public function update(UpdateAlbumListRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->update([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('lists.show', $albumList);
    }

    /**
     * Refresh album data from Spotify for the given list.
     */
    public function refresh(Request $request, AlbumList $albumList): RedirectResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $albums = $albumList->albums()->get();

        if ($albums->isNotEmpty()) {
            $spotify = (new SpotifyService($request->user()))->bypassCache();

            foreach ($albums as $album) {
                $freshData = $spotify->getAlbum($album->spotify_id);

                if ($freshData) {
                    $album->update($freshData);
                }
            }
        }

        return redirect()->route('lists.show', $albumList);
    }

    /**
     * Delete the specified custom list.
     */
    public function destroy(DestroyAlbumListRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->delete();

        return redirect()->route('lists.index');
    }
}
