<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\AlbumSort;
use App\Models\AlbumList;
use App\Models\AlbumListAlbum;
use Illuminate\Contracts\Pagination\Paginator;

trait ResolvesListAlbums
{
    /**
     * Resolve the list's stored sort, falling back to manual for sorts that
     * need reviews only the Reviewed list carries.
     *
     * @return array{0: AlbumSort, 1: string}
     */
    protected function resolveSort(AlbumList $albumList): array
    {
        $sort = AlbumSort::coerce($albumList->sort);
        $direction = $albumList->direction === 'desc' ? 'desc' : 'asc';

        if ($sort->requiresReviews() && ! $albumList->isReviewed()) {
            $sort = AlbumSort::Manual;
        }

        return [$sort, $direction];
    }

    /**
     * Paginate a list's albums with the list's stored sort applied.
     */
    protected function sortedAlbums(AlbumList $albumList, AlbumSort $sort, string $direction): Paginator
    {
        $query = $albumList->albums();

        $sort->applyTo($query, $direction, $albumList->user_id);

        return $query->simplePaginate(20);
    }

    /**
     * Summed track count and runtime across every album filed in the list.
     *
     * @return array{totalTracks: int, totalRuntimeMs: int}
     */
    protected function listTotals(AlbumList $albumList): array
    {
        $totals = AlbumListAlbum::query()
            ->join('albums', 'albums.id', '=', 'album_album_list.album_id')
            ->where('album_album_list.album_list_id', $albumList->id)
            ->selectRaw('COALESCE(SUM(albums.total_tracks), 0) as total_tracks, COALESCE(SUM(albums.runtime_ms), 0) as runtime_ms')
            ->first();

        return [
            'totalTracks' => (int) $totals->total_tracks,
            'totalRuntimeMs' => (int) $totals->runtime_ms,
        ];
    }
}
