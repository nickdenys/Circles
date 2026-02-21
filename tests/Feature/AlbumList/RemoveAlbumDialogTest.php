<?php

test('remove album dialog uses shadcn AlertDialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/alert-dialog'");
    expect($content)->toContain('<AlertDialog');
    expect($content)->toContain('<AlertDialogContent');
});

test('remove album dialog displays album title in confirmation message', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('album?.title');
    expect($content)->toContain('Are you sure you want to remove');
});

test('remove album dialog uses destructive variant for confirm button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('variant="destructive"');
    expect($content)->toContain('<AlertDialogAction');
});

test('remove album dialog has cancel and confirm buttons', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('<AlertDialogCancel');
    expect($content)->toContain('Cancel');
    expect($content)->toContain('<AlertDialogAction');
    expect($content)->toContain('Remove');
});

test('remove album dialog submits via router.delete to lists.albums.destroy', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('router.delete');
    expect($content)->toContain("route('lists.albums.destroy'");
});

test('remove album dialog preserves scroll position on delete', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('preserveScroll: true');
});

test('remove album dialog closes on success', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('onSuccess');
    expect($content)->toContain('onOpenChange(false)');
});

test('remove album dialog supports controlled open state via onOpenChange', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('onOpenChange');
    expect($content)->toContain('<AlertDialog open={open} onOpenChange={onOpenChange}');
});

test('remove button in album card triggers the remove dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('onClick={() => onRemove({ id: album.id, title: album.title })');
    expect($content)->toContain('handleRemoveAlbum');
    expect($content)->toContain('setRemoveDialogOpen(true)');
});

test('show page renders the RemoveAlbumDialog component', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain("from './RemoveAlbumDialog'");
    expect($content)->toContain('<RemoveAlbumDialog');
    expect($content)->toContain('album={albumToRemove}');
    expect($content)->toContain('open={removeDialogOpen}');
    expect($content)->toContain('onRemoved={handleAlbumRemoved}');
});

test('show page decrements album count on removal', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('handleAlbumRemoved');
    expect($content)->toContain('setAlbumCount((prev) => prev - 1)');
});

test('show page removes added album from local state on removal', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId))');
});

test('remove album dialog calls onRemoved callback on success', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('onRemoved(album.id)');
});

test('remove album dialog shows processing state during deletion', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/RemoveAlbumDialog.tsx'));

    expect($content)->toContain('processing');
    expect($content)->toContain("'Removing...'");
    expect($content)->toContain('disabled={processing}');
});
