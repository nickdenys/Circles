<?php

test('move album dialog uses the index-card CardModal', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("from '@/components/circles/CardModal'")
        ->toContain('<CardModal')
        ->toContain('MOVE ALBUM');
});

test('move album dialog shows the album being moved with cover and source list', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('function MovingAlbum')
        ->toContain('MiniCover')
        ->toContain('album.title')
        ->toContain('album.artists')
        ->toContain('FROM')
        ->toContain('source.title');
});

test('move album dialog offers a move and copy mode toggle', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('function ModeToggle')
        ->toContain('mode-toggle-')
        ->toContain("id: 'move'")
        ->toContain("id: 'copy'")
        ->toContain('CornerUpRight')
        ->toContain('Copy');
});

test('move album dialog filters the shared sidebar lists client-side', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('usePage')
        ->toContain('sidebarLists')
        ->toContain('list-filter-input')
        ->toContain('Filter your lists');
});

test('move album dialog excludes the current list and reviewed lists as destinations', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('list.id !== listId')
        ->toContain("list.type !== 'reviewed'");
});

test('move album dialog renders single-select destination rows with counts and glyph', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('function DestRow')
        ->toContain('dest-list-row')
        ->toContain('filtered.map')
        ->toContain('selectedListId')
        ->toContain('listColor')
        ->toContain('albumsCount')
        ->toContain('SYSTEM');
});

test('move album dialog fetches the albums existing list memberships on open', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('axios')
        ->toContain('.get(`/albums/${album.id}/list-memberships`)')
        ->toContain('setLockedListIds');
});

test('move album dialog locks destination lists that already contain the album', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('lockedSet')
        ->toContain('disabled={lockedSet.has(list.id)}')
        ->toContain('Already here');
});

test('move album dialog posts to the move endpoint in move mode', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('router.post')
        ->toContain('/move')
        ->toContain('destination_list_id: selectedList.id');
});

test('move album dialog posts to the copy endpoint in copy mode', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('/copy')
        ->toContain("mode === 'move'");
});

test('move album dialog preserves scroll position on submit', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('preserveScroll: true');
});

test('move album dialog removes the album on move but keeps it on copy', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('onMoved(album.id)')
        ->toContain('toast.success');
});

test('move album dialog surfaces a duplicate destination error from the flash', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('flash?.error')
        ->toContain('toast.error');
});

test('move album dialog has cancel and confirm buttons using the circles Button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain("from '@/components/circles/Button'")
        ->toContain('<Button')
        ->toContain('Cancel')
        ->toContain('} to ${selectedList.title}');
});

test('move album dialog disables confirm until a destination is selected', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)->toContain('disabled={!selectedList || processing}');
});

test('move album dialog shows a processing state during submission', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('processing')
        ->toContain('Moving')
        ->toContain('Copying');
});

test('move album dialog resets state on close', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('handleOpenChange')
        ->toContain('resetState')
        ->toContain('setSelectedListId(null)')
        ->toContain("setFilter('')")
        ->toContain("setMode('move')");
});

test('move album dialog handles controlled open state via onOpenChange', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('onOpenChange')
        ->toContain('if (!open || !album)');
});

test('move button in album card passes the album details to the move dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('onMove({')
        ->toContain('artists: album.artists')
        ->toContain('coverUrl: album.coverUrl')
        ->toContain('releaseDate: album.releaseDate')
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

test('show page removes the moved album from local state', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain('handleAlbumMoved')
        ->toContain('setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId))');
});

test('move album dialog starts the leaving highlight when the move request goes out', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/MoveAlbumDialog.tsx'));

    expect($content)
        ->toContain('onMoving(album.id)')
        ->toContain('onError: () => onMoveFailed(album.id)')
        ->toContain('onMoveFailed(album.id);');
});
