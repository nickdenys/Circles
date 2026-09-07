<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesListAlbums;
use App\Models\AlbumList;
use App\Models\AlbumReview;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SharedAlbumListController extends Controller
{
    use ResolvesListAlbums;

    /**
     * Display a shared list to anyone holding its link, account or not.
     *
     * The route binds {shareHash} through App\Providers\AppServiceProvider::bindShareHash,
     * which only resolves lists whose sharing is currently switched on. Nothing
     * here reaches beyond the one list: no sidebar, no sibling lists, no owner.
     *
     * A share link is handed out, not published, so the response asks crawlers
     * to keep it out of their index.
     */
    public function __invoke(Request $request, AlbumList $shareHash): Response
    {
        $albumList = $shareHash;

        $albumList->loadCount('albums');

        [$sort, $direction] = $this->resolveSort($albumList);

        $page = Inertia::render('Lists/Shared', [
            'list' => [
                'id' => $albumList->id,
                'title' => $albumList->title,
                'description' => $albumList->description,
                'type' => $albumList->type,
                'albumsCount' => $albumList->albums_count,
                ...$this->listTotals($albumList),
            ],
            'albums' => Inertia::scroll(function () use ($albumList, $sort, $direction) {
                $paginator = $this->sortedAlbums($albumList, $sort, $direction);
                $reviews = $this->reviewsFor($albumList, $paginator->getCollection()->pluck('id'));

                return $paginator->through(fn ($album) => [
                    'id' => $album->id,
                    'title' => $album->title,
                    'artists' => $album->artists,
                    'coverUrl' => $album->cover_url,
                    'runtimeMs' => $album->runtime_ms,
                    'totalTracks' => $album->total_tracks,
                    'releaseDate' => $album->release_date,
                    'spotifyUri' => $album->spotify_uri,
                    'note' => $album->pivot->note,
                    'rating' => $albumList->isReviewed()
                        ? $reviews->get($album->id)?->rating
                        : null,
                ]);
            }),
        ]);

        return $page->toResponse($request)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * The owner's reviews keyed by album id, but only for the Reviewed list,
     * which is the only list that shows scores.
     *
     * @param  Collection<int, int>  $albumIds
     * @return EloquentCollection<int, AlbumReview>
     */
    private function reviewsFor(AlbumList $albumList, Collection $albumIds): EloquentCollection
    {
        if (! $albumList->isReviewed() || $albumIds->isEmpty()) {
            return new EloquentCollection;
        }

        return AlbumReview::query()
            ->where('user_id', $albumList->user_id)
            ->whereIn('album_id', $albumIds)
            ->get()
            ->keyBy('album_id');
    }
}
