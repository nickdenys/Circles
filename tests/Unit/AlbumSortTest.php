<?php

use App\Enums\AlbumSort;

test('coerce maps known values to their case', function () {
    expect(AlbumSort::coerce('manual'))->toBe(AlbumSort::Manual)
        ->and(AlbumSort::coerce('title'))->toBe(AlbumSort::Title)
        ->and(AlbumSort::coerce('artist'))->toBe(AlbumSort::Artist)
        ->and(AlbumSort::coerce('release_date'))->toBe(AlbumSort::ReleaseDate)
        ->and(AlbumSort::coerce('added'))->toBe(AlbumSort::DateAdded);
});

test('coerce falls back to manual for unknown or empty values', function () {
    expect(AlbumSort::coerce('garbage'))->toBe(AlbumSort::Manual)
        ->and(AlbumSort::coerce(null))->toBe(AlbumSort::Manual)
        ->and(AlbumSort::coerce(''))->toBe(AlbumSort::Manual);
});
