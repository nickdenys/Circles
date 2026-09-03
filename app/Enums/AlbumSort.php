<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\JoinClause;

enum AlbumSort: string
{
    case Manual = 'manual';
    case Title = 'title';
    case Artist = 'artist';
    case ReleaseDate = 'release_date';
    case DateAdded = 'added';
    case Score = 'score';

    public static function coerce(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Manual;
    }

    /**
     * Determine if this sort needs the user's reviews, which only the Reviewed list carries.
     */
    public function requiresReviews(): bool
    {
        return $this === self::Score;
    }

    /**
     * Apply this sort to the album list relationship query.
     *
     * Clears the relationship's default position ordering first, then applies a
     * stable tiebreaker so equal values paginate deterministically across the
     * infinite scroll.
     */
    public function applyTo(BelongsToMany $query, string $direction, int $userId): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->reorder();

        match ($this) {
            self::Manual => $query->orderBy('album_album_list.position', $direction),
            self::Title => $query->orderBy('albums.title', $direction),
            self::Artist => $query->orderBy('albums.artists', $direction),
            self::ReleaseDate => $query->orderByRaw("substr(albums.release_date || '-01-01', 1, 10) {$direction}"),
            self::DateAdded => $query->orderBy('album_album_list.created_at', $direction),
            self::Score => $this->applyScoreOrder($query, $direction, $userId),
        };

        $query->orderBy('album_album_list.id');
    }

    /**
     * Order by the user's rating for each album, keeping unrated albums last in both directions.
     */
    private function applyScoreOrder(BelongsToMany $query, string $direction, int $userId): void
    {
        $query
            ->leftJoin('album_reviews', function (JoinClause $join) use ($userId) {
                $join->on('album_reviews.album_id', '=', 'albums.id')
                    ->where('album_reviews.user_id', $userId);
            })
            ->orderByRaw('album_reviews.rating IS NULL')
            ->orderBy('album_reviews.rating', $direction);
    }
}
