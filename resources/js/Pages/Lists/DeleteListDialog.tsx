import { router } from '@inertiajs/react';
import { Loader2, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/circles/Button';
import { CardModal } from '@/components/circles/CardModal';

interface DeleteListDialogProps {
    listId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function DeleteListDialog({ listId, open, onOpenChange }: DeleteListDialogProps) {
    const [processing, setProcessing] = useState(false);

    function handleConfirm() {
        router.delete(`/lists/${listId}`, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    }

    if (!open) {
        return null;
    }

    return (
        <CardModal
            label="DELETE LIST"
            ariaLabel="Delete list"
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
                        {processing ? 'Deleting...' : 'Delete'}
                    </Button>
                </div>
            }
        >
            <p style={{ margin: 0, fontSize: 14.5, lineHeight: 1.55, color: 'var(--fg2)' }}>
                This list and all album associations will be permanently deleted. This action cannot be undone.
            </p>
        </CardModal>
    );
}
