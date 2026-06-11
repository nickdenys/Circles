<?php

use App\Models\User;

test('create list dialog component uses shadcn Dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/dialog'");
    expect($content)->toContain('<Dialog');
    expect($content)->toContain('<DialogContent');
});

test('create list dialog includes title and description form fields', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain('id="title"');
    expect($content)->toContain('id="description"');
});

test('create list dialog uses shadcn Input and Textarea', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/input'");
    expect($content)->toContain("from '@/components/ui/textarea'");
    expect($content)->toContain('<Input');
    expect($content)->toContain('<Textarea');
});

test('create list dialog uses shadcn Label for form fields', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/label'");
    expect($content)->toContain('<Label');
});

test('create list dialog displays validation errors', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain('errors.title');
    expect($content)->toContain('errors.description');
    expect($content)->toContain('text-destructive');
});

test('create list dialog submits a POST to the lists.store endpoint', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain(".post('/lists'");
});

test('create list dialog has Cancel and Save buttons using shadcn Button', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain("from '@/components/ui/button'");
    expect($content)->toContain('Cancel');
    expect($content)->toContain('Save');
    expect($content)->toContain('<Button');
});

test('create list dialog closes on successful submission', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain('onOpenChange(false)');
});

test('create list dialog resets form on close', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain('reset()');
});

test('lists index page integrates the create list dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('CreateListDialog');
    expect($content)->toContain('createDialogOpen');
    expect($content)->toContain('setCreateDialogOpen');
});

test('add list button opens the create dialog', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/Index.tsx'));

    expect($content)->toContain('onClick={() => setCreateDialogOpen(true)');
});

test('store route redirects to lists index on success', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => 'Test Dialog List',
        ])
        ->assertRedirect(route('lists.index'));

    expect($user->albumLists()->where('title', 'Test Dialog List')->exists())->toBeTrue();
});

test('store route returns validation errors for missing title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('lists.store'), [
            'title' => '',
        ])
        ->assertSessionHasErrors('title');
});

test('description field is marked as optional', function () {
    $content = file_get_contents(resource_path('js/Pages/Lists/CreateListDialog.tsx'));

    expect($content)->toContain('(optional)');
});
