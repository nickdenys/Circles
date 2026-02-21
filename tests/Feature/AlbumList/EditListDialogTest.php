<?php

use App\Models\AlbumList;
use App\Models\User;

test('edit list dialog component uses shadcn Dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/dialog'");
    expect($content)->toContain('<Dialog');
    expect($content)->toContain('<DialogContent');
});

test('edit list dialog includes title and description form fields', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('id="edit-title"');
    expect($content)->toContain('id="edit-description"');
});

test('edit list dialog uses shadcn Input and Textarea', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/input'");
    expect($content)->toContain("from '@/components/ui/textarea'");
    expect($content)->toContain('<Input');
    expect($content)->toContain('<Textarea');
});

test('edit list dialog uses shadcn Label for form fields', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/label'");
    expect($content)->toContain('<Label');
});

test('edit list dialog displays validation errors', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('errors.title');
    expect($content)->toContain('errors.description');
    expect($content)->toContain('text-destructive');
});

test('edit list dialog submits via Inertia put to lists.update', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('useForm');
    expect($content)->toContain("put(route('lists.update'");
});

test('edit list dialog has Cancel and Save buttons using shadcn Button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/button'");
    expect($content)->toContain('Cancel');
    expect($content)->toContain('Save');
    expect($content)->toContain('<Button');
});

test('edit list dialog closes on successful submission', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('onSuccess');
    expect($content)->toContain('onOpenChange(false)');
});

test('edit list dialog resets form on close', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('reset()');
});

test('edit list dialog pre-populates fields with current values', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('title: title');
    expect($content)->toContain("description: description ?? ''");
});

test('edit list dialog syncs form data when opened', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('useEffect');
    expect($content)->toContain('if (open)');
    expect($content)->toContain('setData');
});

test('edit list dialog description field is marked as optional', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/EditListDialog.tsx'));

    expect($content)->toContain('(optional)');
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
        ->assertRedirect(route('lists.show', $list));

    expect($list->refresh())
        ->title->toBe('Updated Title')
        ->description->toBe('New description');
});

test('edit dialog is only available for custom lists', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));

    expect($content)->toContain("list.type === 'custom'");
    expect($content)->toContain('id="edit-list-button"');
});
