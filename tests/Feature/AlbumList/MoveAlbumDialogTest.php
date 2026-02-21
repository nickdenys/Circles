<?php

test('move album dialog uses shadcn Dialog component', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("from '@/components/ui/dialog'")
        ->toContain('<Dialog')
        ->toContain('<DialogContent');
});

test('move album dialog displays album title being moved', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('album?.title')
        ->toContain('Move Album')
        ->toContain('to another list');
});

test('move album dialog includes searchable list picker with debounced search', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('list-search-input')
        ->toContain('Search for a list...')
        ->toContain('setTimeout')
        ->toContain('300');
});

test('move album dialog search requires minimum 1 character', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('q.length < 1');
});

test('move album dialog excludes the current list from search results', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('exclude: listId');
});

test('move album dialog searches via the lists.search endpoint', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("'/lists/search'")
        ->toContain('axios.get');
});

test('move album dialog shows selected destination list with Badge', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("from '@/components/ui/badge'")
        ->toContain('<Badge')
        ->toContain('selected-list-badge')
        ->toContain('selectedList.title')
        ->toContain('Destination:');
});

test('move album dialog disables confirm button until destination list is selected', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('disabled={!selectedList || processing}');
});

test('move album dialog submits via router.post to lists.albums.move', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('router.post')
        ->toContain('/albums/')
        ->toContain('/move')
        ->toContain('destination_list_id: selectedList.id');
});

test('move album dialog preserves scroll position on move', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('preserveScroll: true');
});

test('move album dialog closes on success and calls onMoved', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('onSuccess')
        ->toContain('onMoved(album.id)')
        ->toContain('onOpenChange(false)');
});

test('move album dialog resets state on close', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('handleOpenChange')
        ->toContain("setQuery('')")
        ->toContain('setResults([])')
        ->toContain('setSelectedList(null)');
});

test('move album dialog has cancel and confirm buttons using shadcn Button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("from '@/components/ui/button'")
        ->toContain('<Button')
        ->toContain('Cancel')
        ->toContain('Confirm');
});

test('move album dialog uses shadcn Input for search field', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("from '@/components/ui/input'")
        ->toContain('<Input');
});

test('move button in album card triggers the move dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('onClick={() => onMove({ id: album.id, title: album.title })')
        ->toContain('handleMoveAlbum')
        ->toContain('setMoveDialogOpen(true)');
});

test('show page renders the MoveAlbumDialog component', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain("from './MoveAlbumDialog'")
        ->toContain('<MoveAlbumDialog')
        ->toContain('album={albumToMove}')
        ->toContain('open={moveDialogOpen}')
        ->toContain('onMoved={handleAlbumMoved}');
});

test('show page decrements album count on move', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('handleAlbumMoved')
        ->toContain('setAlbumCount((prev) => prev - 1)');
});

test('show page removes added album from local state on move', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('handleAlbumMoved');

    // handleAlbumMoved filters orderedAlbums
    expect($content)->toContain('setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId))');
});

test('move album dialog shows processing state during submission', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('processing')
        ->toContain("'Moving...'")
        ->toContain('disabled={!selectedList || processing}');
});

test('move album dialog displays search results in a list', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('list-search-results')
        ->toContain('list-search-result')
        ->toContain('results.map');
});

test('move album dialog handles controlled open state via onOpenChange', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('<Dialog open={open} onOpenChange={handleOpenChange}');
});
