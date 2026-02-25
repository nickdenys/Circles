<?php

test('album search component is rendered on the list detail page', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('id="album-search-input"')
        ->toContain('Add an album from Spotify');
});

test('album search uses debounced input with 300ms delay', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('setTimeout')
        ->toContain('300')
        ->toContain('clearTimeout');
});

test('album search requires minimum 2 characters before searching', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)->toContain('q.length < 2');
});

test('album search dropdown displays results with image name and artist', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('id="album-search-dropdown"')
        ->toContain('id="album-search-results"')
        ->toContain('result.name')
        ->toContain('result.artists')
        ->toContain('result.image');
});

test('album search calls the spotify search endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)->toContain('/spotify/search/albums');
});

test('clicking a search result posts to the album store endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('axios.post')
        ->toContain('`/lists/${listId}/albums`')
        ->toContain('spotify_id');
});

test('album search handles 409 conflict gracefully', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('409')
        ->toContain('Album already in this list.');
});

test('search input is cleared and dropdown closes after adding an album', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    // After successful add, query is reset and dropdown closes
    expect($component)
        ->toContain("setQuery('')")
        ->toContain('setResults([])')
        ->toContain('setIsOpen(false)');
});

test('dropdown closes on click outside', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('handleClickOutside')
        ->toContain('mousedown')
        ->toContain('containerRef');
});

test('dropdown closes on escape key', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('Escape')
        ->toContain('onKeyDown');
});

test('album search uses shadcn input component', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)->toContain("from '@/components/ui/input'");
});

test('show page integrates album search component', function () {
    $showComponent = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($showComponent)
        ->toContain("from './AlbumSearch'")
        ->toContain('<AlbumSearch')
        ->toContain('listId={list.id}')
        ->toContain('onAlbumAdded');
});

test('show page updates album count after adding an album', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('setAlbumCount')
        ->toContain('handleAlbumAdded');
});

test('show page appends new album to the list after adding', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($component)
        ->toContain('setOrderedAlbums')
        ->toContain('[...prev, album]');
});

test('added album callback maps snake_case response to camelCase props', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AlbumSearch.tsx'));

    expect($component)
        ->toContain('spotifyId: data.spotify_id')
        ->toContain('coverUrl: data.cover_url')
        ->toContain('runtimeMs: data.runtime_ms')
        ->toContain('albumType: data.album_type')
        ->toContain('totalTracks: data.total_tracks')
        ->toContain('releaseDate: data.release_date')
        ->toContain('spotifyUri: data.spotify_uri')
        ->toContain('genres: data.genres');
});
