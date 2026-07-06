<?php

function noteDialogSource(): string
{
    return file_get_contents(resource_path('js/Pages/Lists/NoteDialog.tsx'));
}

function listShowSource(): string
{
    return file_get_contents(resource_path('js/Pages/Lists/Show.tsx'));
}

test('note dialog submits to the album update endpoint', function () {
    expect(noteDialogSource())
        ->toContain('.patch(`/lists/${listId}/albums/${album.id}`, { note: value })')
        ->toContain('onSubmitted(album.id, savedNote)');
});

test('note dialog lets an existing note be removed', function () {
    expect(noteDialogSource())
        ->toContain('Remove note')
        ->toContain('saveNote(null)');
});

test('list show wires up the note dialog', function () {
    expect(listShowSource())
        ->toContain("import NoteDialog, { type NoteDialogAlbum } from './NoteDialog'")
        ->toContain('<NoteDialog')
        ->toContain('onSubmitted={handleNoteSubmitted}')
        ->toContain('function handleEditNote(album: AlbumItem)')
        ->toContain('function handleNoteSubmitted(albumId: number, note: string | null)');
});

test('album row menu exposes an add or edit note action', function () {
    expect(listShowSource())
        ->toContain('edit-note-button')
        ->toContain("hasNote ? 'Edit note' : 'Add note'")
        ->toContain('onEditNote={() => handleEditNote(album)}');
});
