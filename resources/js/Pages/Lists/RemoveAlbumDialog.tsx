import { router } from '@inertiajs/react';
import { Loader2, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/hoopify/Button';
import { CardModal } from '@/components/hoopify/CardModal';

interface RemoveAlbumDialogProps {
    listId: number;
    album: { id: number; title: string } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onRemoved: (albumId: number) => void;
}

export default function RemoveAlbumDialog({
    listId,
    album,
    open,
    onOpenChange,
    onRemoved,
}: RemoveAlbumDialogProps) {
    const [processing, setProcessing] = useState(false);

    function handleConfirm() {
        if (!album) return;

        router.delete(`/lists/${listId}/albums/${album.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => {
                onRemoved(album.id);
                onOpenChange(false);
            },
        });
    }

    if (!open) {
        return null;
    }

    return (
        <CardModal
            label="REMOVE ALBUM"
            ariaLabel="Remove album"
            width={480}
            onClose={() => {
                if (processing) {
                    return;
                }
                onOpenChange(false);
            }}
            footer={
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10 }}>
                    <Button variant="ghost" type="button" onClick={() => onOpenChange(false)} disabled={processing}>
                        Cancel
                    </Button>
                    <Button
                        variant="danger"
                        type="button"
                        icon={processing ? undefined : Trash2}
                        onClick={handleConfirm}
                        disabled={processing}
                    >
                        {processing && <Loader2 size={15} strokeWidth={2} className="animate-spin" />}
                        {processing ? 'Removing...' : 'Remove'}
                    </Button>
                </div>
            }
        >
            <p style={{ margin: 0, fontSize: 14.5, lineHeight: 1.55, color: 'var(--fg2)' }}>
                Are you sure you want to remove{' '}
                <span style={{ fontWeight: 600, color: 'var(--fg1)' }}>{album?.title}</span> from this list?
            </p>
        </CardModal>
    );
}
