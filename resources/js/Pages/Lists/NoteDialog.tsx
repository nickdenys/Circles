import axios from 'axios';
import { Check, Loader2, Trash2 } from 'lucide-react';
import { CSSProperties, FormEvent, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/hoopify/Button';
import { CardModal } from '@/components/hoopify/CardModal';
import { MiniCover } from '@/components/hoopify/CoverArt';
import { Label } from '@/components/hoopify/Label';
import { Textarea } from '@/components/ui/textarea';

export interface NoteDialogAlbum {
    id: number;
    title: string;
    artist?: string | null;
    coverUrl?: string | null;
    releaseDate?: string | null;
    albumType?: string | null;
    totalTracks?: number | null;
    currentNote?: string | null;
}

interface NoteDialogProps {
    listId: number;
    album: NoteDialogAlbum | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmitted: (albumId: number, note: string | null) => void;
}

function noteFieldStyle(focused: boolean): CSSProperties {
    return {
        width: '100%',
        minHeight: 148,
        resize: 'none',
        padding: '13px 15px',
        borderRadius: 10,
        border: `1.5px solid ${focused ? 'var(--accent)' : 'var(--line-strong)'}`,
        boxShadow: focused ? '0 0 0 3px var(--accent-weak)' : 'none',
        background: 'var(--surface-2)',
        fontFamily: 'var(--font-sans)',
        fontSize: 14.5,
        color: 'var(--fg1)',
        lineHeight: 1.55,
        transition: 'border-color var(--dur-fast), box-shadow var(--dur-fast)',
    };
}

export default function NoteDialog({
    listId,
    album,
    open,
    onOpenChange,
    onSubmitted,
}: NoteDialogProps) {
    const [note, setNote] = useState<string>('');
    const [noteFocused, setNoteFocused] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (open && album) {
            setNote(album.currentNote ?? '');
            setNoteFocused(false);
        }
    }, [open, album]);

    function saveNote(value: string | null) {
        if (!album) {
            return;
        }

        setProcessing(true);

        axios
            .patch(`/lists/${listId}/albums/${album.id}`, { note: value })
            .then((response) => {
                const savedNote = (response.data?.note ?? null) as string | null;
                toast.success(savedNote ? 'Note saved.' : 'Note removed.');
                onSubmitted(album.id, savedNote);
                onOpenChange(false);
            })
            .catch(() => {
                toast.error('Could not save your note.');
            })
            .finally(() => {
                setProcessing(false);
            });
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const trimmed = note.trim();
        saveNote(trimmed === '' ? null : trimmed);
    }

    function handleOpenChange(value: boolean) {
        if (!value && processing) {
            return;
        }
        if (!value) {
            setNote('');
        }
        onOpenChange(value);
    }

    const hasExistingNote = !!album?.currentNote && album.currentNote.trim().length > 0;
    const dirty = !!album && note !== (album.currentNote ?? '');
    const canSave = dirty && !processing;

    const metadataParts = album
        ? [
              album.releaseDate ? album.releaseDate.slice(0, 4) : null,
              album.albumType ? album.albumType.toUpperCase() : null,
              album.totalTracks ? `${album.totalTracks} TRACKS` : null,
          ].filter((part): part is string => Boolean(part))
        : [];

    if (!open || !album) {
        return null;
    }

    return (
        <CardModal
            label=""
            ariaLabel="Note card"
            width={600}
            onClose={() => handleOpenChange(false)}
            footer={
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: hasExistingNote ? 'space-between' : 'flex-end',
                        gap: 12,
                    }}
                >
                    {hasExistingNote && (
                        <Button
                            variant="danger"
                            size="sm"
                            type="button"
                            icon={Trash2}
                            id="remove-note-button"
                            onClick={() => saveNote(null)}
                            disabled={processing}
                        >
                            Remove note
                        </Button>
                    )}
                    <div style={{ display: 'flex', gap: 10 }}>
                        <Button
                            variant="ghost"
                            type="button"
                            onClick={() => handleOpenChange(false)}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            type="submit"
                            form="note-dialog-form"
                            icon={processing ? undefined : Check}
                            disabled={!canSave}
                        >
                            {processing && (
                                <Loader2 size={15} strokeWidth={2} className="animate-spin" />
                            )}
                            Save note
                        </Button>
                    </div>
                </div>
            }
        >
            <form
                id="note-dialog-form"
                onSubmit={handleSubmit}
                style={{ display: 'flex', flexDirection: 'column', gap: 24 }}
            >
                <div style={{ display: 'flex', gap: 18, alignItems: 'center' }}>
                    <div
                        style={{
                            width: 92,
                            height: 92,
                            flex: 'none',
                            borderRadius: 12,
                            overflow: 'hidden',
                            border: '1px solid var(--line)',
                            boxShadow: 'var(--shadow-md)',
                        }}
                    >
                        <MiniCover
                            src={album.coverUrl}
                            alt={album.title}
                            size={92}
                            radius={0}
                            style={{ width: 92, height: 92 }}
                        />
                    </div>
                    <div style={{ minWidth: 0 }}>
                        <h2
                            style={{
                                fontFamily: 'var(--font-display)',
                                fontWeight: 800,
                                fontSize: 30,
                                letterSpacing: '-0.025em',
                                margin: 0,
                                lineHeight: 1.02,
                            }}
                        >
                            {album.title}
                        </h2>
                        {album.artist && (
                            <div
                                style={{
                                    fontSize: 16,
                                    color: 'var(--fg2)',
                                    fontWeight: 500,
                                    marginTop: 4,
                                }}
                            >
                                {album.artist}
                            </div>
                        )}
                        {metadataParts.length > 0 && (
                            <Label style={{ display: 'block', marginTop: 8 }}>
                                {metadataParts.join(' · ')}
                            </Label>
                        )}
                    </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                    <Label>Note</Label>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                        <Textarea
                            autoFocus
                            id="note-dialog-note"
                            value={note}
                            onChange={(event) => setNote(event.target.value)}
                            onFocus={() => setNoteFocused(true)}
                            onBlur={() => setNoteFocused(false)}
                            rows={1}
                            placeholder="A line for future you. Why it's here, when to play it, who to share it with."
                            style={noteFieldStyle(noteFocused)}
                        />
                        <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                            <Label style={{ fontSize: 10 }}>
                                {note.trim().length} chars · saved to this list
                            </Label>
                        </div>
                    </div>
                </div>
            </form>
        </CardModal>
    );
}
