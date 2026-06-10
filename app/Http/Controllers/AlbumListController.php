<?php

namespace App\Http\Controllers;

use App\Enums\AlbumListMode;
use App\Enums\AlbumSort;
use App\Http\Requests\DestroyAlbumListRequest;
use App\Http\Requests\StoreAlbumListRequest;
use App\Http\Requests\UpdateAlbumListModeRequest;
use App\Http\Requests\UpdateAlbumListRequest;
use App\Http\Requests\UpdateAlbumListSortRequest;
use App\Jobs\FetchAlbumGenres;
use App\Models\AlbumList;
use App\Services\SpotifyService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
            ->with(['albums' => fn ($q) => $q->orderBy('position')->limit(4)])
            ->orderByRaw("CASE WHEN type IN ('system', 'reviewed') THEN 0 ELSE 1 END")
            ->orderBy('title');

        if ($request->wantsJson()) {
            $lists = $query->simplePaginate(20);

            return response()->json([
                'data' => $lists->getCollection()->map(fn ($list) => [
                    'id' => $list->id,
                    'title' => $list->title,
                    'description' => $list->description,
                    'type' => $list->type,
                    'albums_count' => $list->albums_count ?? 0,
                    'preview_covers' => $list->albums->pluck('cover_url')->values()->all(),
                    'updated_label' => $this->shortRelative($list->updated_at),
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
                    'description' => $list->description,
                    'type' => $list->type,
                    'albumsCount' => $list->albums_count ?? 0,
                    'previewCovers' => $list->albums->pluck('cover_url')->values()->all(),
                    'updatedLabel' => $this->shortRelative($list->updated_at),
                    'url' => route('lists.show', $list),
                ])
            ),
        ]);
    }

    /**
     * Compact relative-time string for catalog labels, e.g. "4H AGO", "YESTERDAY", "2D AGO".
     */
    private function shortRelative(?\DateTimeInterface $timestamp): ?string
    {
        if (! $timestamp) {
            return null;
        }

        $carbon = \Carbon\Carbon::instance($timestamp);
        $minutes = $carbon->diffInMinutes(now());

        if ($minutes < 1) {
            return 'JUST NOW';
        }
        if ($minutes < 60) {
            return $minutes.'M AGO';
        }
        $hours = (int) $carbon->diffInHours(now());
        if ($hours < 24) {
            return $hours.'H AGO';
        }
        $days = (int) $carbon->diffInDays(now());
        if ($days === 1) {
            return 'YESTERDAY';
        }
        if ($days < 7) {
            return $days.'D AGO';
        }
        if ($days < 30) {
            return ((int) floor($days / 7)).'W AGO';
        }
        if ($days < 365) {
            return ((int) floor($days / 30)).'MO AGO';
        }

        return ((int) floor($days / 365)).'Y AGO';
    }

    /**
     * Display the list detail page.
     */
    public function show(Request $request, AlbumList $albumList): Response|JsonResponse
    {
        abort_unless($albumList->user_id === $request->user()->id, 403);

        $albumList->loadCount('albums');

        $sort = AlbumSort::coerce($albumList->sort);
        $direction = $albumList->direction === 'desc' ? 'desc' : 'asc';

        if ($request->wantsJson()) {
            $albums = $this->sortedAlbums($albumList, $sort, $direction);
            $reviews = $this->reviewsFor($request, $albumList, $albums->getCollection()->pluck('id'));

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
                    'genres' => $album->genres ?? [],
                    'note' => $album->pivot->note,
                    ...$this->reviewPayloadFor($albumList, $reviews, $album->id),
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
                'mode' => $albumList->mode->value,
                'albumsCount' => $albumList->albums_count,
            ],
            'sort' => $sort->value,
            'direction' => $direction,
            'albums' => Inertia::scroll(function () use ($request, $albumList, $sort, $direction) {
                $paginator = $this->sortedAlbums($albumList, $sort, $direction);
                $reviews = $this->reviewsFor($request, $albumList, $paginator->getCollection()->pluck('id'));

                return $paginator->through(fn ($album) => [
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
                    'genres' => $album->genres ?? [],
                    'note' => $album->pivot->note,
                    ...$this->reviewPayloadFor($albumList, $reviews, $album->id),
                ]);
            }),
        ]);
    }

    /**
     * Update the list's stored sort setting.
     */
    public function updateSort(UpdateAlbumListSortRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->update($request->validated());

        return to_route('lists.show', $albumList);
    }

    /**
     * Update the list's stored mode setting.
     */
    public function updateMode(UpdateAlbumListModeRequest $request, AlbumList $albumList): RedirectResponse
    {
        $albumList->update($request->validated());

        return to_route('lists.show', $albumList);
    }

    /**
     * Paginate a list's albums with the list's stored sort applied.
     */
    private function sortedAlbums(AlbumList $albumList, AlbumSort $sort, string $direction): Paginator
    {
        $query = $albumList->albums();

        $sort->applyTo($query, $direction);

        return $query->simplePaginate(20);
    }

    /**
     * Load the user's reviews keyed by album id for the given album ids, but only when the list is the Reviewed list.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $albumIds
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\AlbumReview>
     */
    private function reviewsFor(Request $request, AlbumList $albumList, $albumIds)
    {
        if (! $albumList->isReviewed() || $albumIds->isEmpty()) {
            return new EloquentCollection;
        }

        return $request->user()
            ->reviews()
            ->whereIn('album_id', $albumIds)
            ->get()
            ->keyBy('album_id');
    }

    /**
     * Build the rating/review payload fragment for an album row.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\AlbumReview>  $reviews
     * @return array<string, float|string|null>
     */
    private function reviewPayloadFor(AlbumList $albumList, $reviews, int $albumId): array
    {
        if (! $albumList->isReviewed()) {
            return [];
        }

        $review = $reviews->get($albumId);

        return [
            'rating' => $review ? (float) $review->rating : null,
            'review' => $review?->review,
        ];
    }

    /**
     * Search user's lists for autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'exclude' => ['nullable', 'integer'],
            'exclude_reviewed' => ['nullable', 'boolean'],
        ]);

        $query = $request->input('q');
        $exclude = $request->input('exclude');

        $lists = $request->user()
            ->albumLists()
            ->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($query).'%'])
            ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))
            ->when($request->boolean('exclude_reviewed'), fn ($q) => $q->where('type', '!=', 'reviewed'))
            ->orderByRaw("CASE WHEN type IN ('system', 'reviewed') THEN 0 ELSE 1 END")
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
            'mode' => $request->validated('mode') ?? AlbumListMode::Default->value,
        ]);

        return redirect()->route('lists.index');
    }

    /**
     * Update the specified custom list.
     */
    public function update(UpdateAlbumListRequest $request, AlbumList $albumList): RedirectResponse
    {
        $data = [
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
        ];

        if ($mode = $request->validated('mode')) {
            $data['mode'] = $mode;
        }

        $albumList->update($data);

        return redirect()->route('lists.show', $albumList);
    }

    /**
     * Refresh album data from external APIs for the given list.
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
                    FetchAlbumGenres::dispatch($album, bypassCache: true);
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
