<?php

use App\Models\AlbumList;
use App\Models\User;

test('edit list dialog uses the index-card CardModal', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from '@/components/hoopify/CardModal'");
    expect($content)->toContain('<CardModal');
});

test('edit list dialog includes title and description form fields', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('id="edit-title"');
    expect($content)->toContain('id="edit-description"');
});

test('edit list dialog uses the shared DialogField component', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from './DialogField'");
    expect($content)->toContain('<DialogField');
});

test('edit list dialog displays validation errors', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('errors.title');
    expect($content)->toContain('errors.description');
});

test('edit list dialog submits a PUT to the list update endpoint', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('.put(`/lists/');
});

test('edit list dialog has Cancel and Save buttons using the hoopify Button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from '@/components/hoopify/Button'");
    expect($content)->toContain('Cancel');
    expect($content)->toContain('Save');
    expect($content)->toContain('<Button');
});

test('edit list dialog closes on successful submission', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('onOpenChange(false)');
});

test('edit list dialog pre-populates fields with current values', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('setTitle(initialTitle)');
    expect($content)->toContain("setDescription(initialDescription ?? '')");
});

test('edit list dialog syncs form data when opened', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('useEffect');
    expect($content)->toContain('if (open)');
    expect($content)->toContain('setTitle');
});

test('edit list dialog description field is marked as optional', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('optional');
});

test('show page integrates the edit list dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('EditListDialog');
    expect($content)->toContain('editDialogOpen');
    expect($content)->toContain('setEditDialogOpen');
});

test('edit button opens the edit dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('onClick={() => setEditDialogOpen(true)');
});

test('show page passes list data to edit dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('listId={list.id}');
    expect($content)->toContain('title={list.title}');
    expect($content)->toContain('description={list.description}');
});

test('update route redirects to list show page on success', function () {
    $user = User::factory()->create();
    $list = AlbumList::factory()->for($user)->create([
        'title' => 'Original Title',
    ]);

    $this->actingAs($user)
        ->put(route('lists.update', $list), [
            'title' => 'Updated Title',
            'description' => 'New description',
        ])
        ->assertRedirect(route('lists.show', $list->refresh()));

    expect($list)
        ->title->toBe('Updated Title')
        ->description->toBe('New description');
});

test('edit button is available for every list', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain('id="edit-list-button"');
});

test('delete button is only available for custom lists', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)
        ->toContain("list.type === 'custom'")
        ->toContain('id="delete-list-button"');
});
