<?php

test('add album dialog renders the rapid-add search input', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('id="album-search-input"')
        ->toContain('Search Spotify and hit enter');
});

test('add album dialog uses debounced input with 300ms delay', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('setTimeout')
        ->toContain('300')
        ->toContain('clearTimeout');
});

test('add album dialog requires minimum 2 characters before searching', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)->toContain('value.trim().length < 2');
});

test('add album dialog displays results with image name and artist', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('id="album-search-results"')
        ->toContain('result.name')
        ->toContain('result.artists')
        ->toContain('result.image');
});

test('add album dialog calls the spotify search endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)->toContain('/spotify/search/albums');
});

test('add album dialog posts to the album store endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('axios')
        ->toContain('`/lists/${listId}/albums`')
        ->toContain('spotify_id');
});

test('add album dialog handles 409 conflict gracefully', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('409')
        ->toContain('already in this list');
});

test('add album dialog clears and refocuses the input after adding an album', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain("setQuery('')")
        ->toContain('setResults([])')
        ->toContain('inputRef.current?.focus()');
});

test('add album dialog commits the top result on enter', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain("event.key === 'Enter'")
        ->toContain('addAlbum(visibleResults[0])');
});

test('add album dialog keeps a running added-this-session log', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('Added this session')
        ->toContain('setAdded');
});

test('add album dialog can undo an add via the album destroy endpoint', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('axios')
        ->toContain('.delete(`/lists/${listId}/albums/${album.id}`)')
        ->toContain('onAlbumRemoved');
});

test('add album dialog renders the index-card modal chrome', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

    expect($component)
        ->toContain('CardModal')
        ->toContain('ADD TO LIST')
        ->toContain('Done');
});

test('added album callback maps snake_case response to camelCase props', function () {
    $component = file_get_contents(resource_path('js/Pages/Lists/AddAlbumDialog.tsx'));

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

test('show page integrates the add album dialog', function () {
    $showComponent = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($showComponent)
        ->toContain("from './AddAlbumDialog'")
        ->toContain('<AddAlbumDialog')
        ->toContain('listId={list.id}')
        ->toContain('onAlbumAdded')
        ->toContain('onAlbumRemoved')
        ->toContain('existingSpotifyIds');
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
