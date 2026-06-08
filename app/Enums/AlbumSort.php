<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

enum AlbumSort: string
{
    case Manual = 'manual';
    case Title = 'title';
    case Artist = 'artist';
    case ReleaseDate = 'release_date';
    case DateAdded = 'added';

    public static function coerce(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Manual;
    }

    /**
     * Apply this sort to the album list relationship query.
     *
     * Clears the relationship's default position ordering first, then applies a
     * stable tiebreaker so equal values paginate deterministically across the
     * infinite scroll.
     */
    public function applyTo(BelongsToMany $query, string $direction): void
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->reorder();

        match ($this) {
            self::Manual => $query->orderBy('album_album_list.position', $direction),
            self::Title => $query->orderBy('albums.title', $direction),
            self::Artist => $query->orderBy('albums.artists', $direction),
            self::ReleaseDate => $query->orderByRaw("substr(albums.release_date || '-01-01', 1, 10) {$direction}"),
            self::DateAdded => $query->orderBy('album_album_list.created_at', $direction),
        };

        $query->orderBy('album_album_list.id');
    }
}
