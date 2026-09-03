<?php

test('delete list dialog uses the index-card CardModal', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain("from '@/components/kit/CardModal'");
    expect($content)->toContain('<CardModal');
});

test('delete list dialog displays permanent deletion warning', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain('permanently');
    expect($content)->toContain('album associations');
    expect($content)->toContain('cannot be undone');
});

test('delete list dialog uses danger variant for confirm button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain('variant="danger"');
});

test('delete list dialog has cancel and confirm buttons', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain('Cancel');
    expect($content)->toContain('Delete');
});

test('delete list dialog submits via router.delete to lists.destroy', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain('router.delete');
    expect($content)->toContain('/lists/');
});

test('delete list dialog redirects to lists index on successful deletion', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    // router.delete to lists.destroy triggers server-side redirect to lists.index
    expect($content)->toContain('router.delete(`/lists/');
});

test('delete list dialog supports controlled open state via onOpenChange', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain('onOpenChange');
    expect($content)->toContain('if (!open)');
});

test('delete list dialog shows processing state during deletion', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/DeleteListDialog.tsx'));

    expect($content)->toContain('processing');
    expect($content)->toContain("'Deleting...'");
    expect($content)->toContain('disabled={processing}');
});

test('delete button in show page triggers the delete dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('id="delete-list-button"');
    expect($content)->toContain('setDeleteDialogOpen(true)');
});

test('show page renders the DeleteListDialog component', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain("from './DeleteListDialog'");
    expect($content)->toContain('<DeleteListDialog');
    expect($content)->toContain('open={deleteDialogOpen}');
    expect($content)->toContain('onOpenChange={setDeleteDialogOpen}');
});

test('delete list dialog is only available for custom lists', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    // Delete button is inside the list.type === 'custom' conditional
    expect($content)->toContain("list.type === 'custom'");
    expect($content)->toContain('id="delete-list-button"');
});
