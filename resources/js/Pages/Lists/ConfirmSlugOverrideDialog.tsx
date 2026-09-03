import { CardModal } from '@/components/kit/CardModal';
import { Button } from '@/components/kit/Button';

export interface SlugHistoryConflict {
    conflicting_slug: string;
    previous_owner_title: string;
    suggested_alternative: string;
}

interface ConfirmSlugOverrideDialogProps {
    open: boolean;
    conflict: SlugHistoryConflict | null;
    onUseAnyway: () => void;
    onCancel: () => void;
}

export default function ConfirmSlugOverrideDialog({
    open,
    conflict,
    onUseAnyway,
    onCancel,
}: ConfirmSlugOverrideDialogProps) {
    if (!open || conflict === null) {
        return null;
    }

    return (
        <CardModal
            label="URL IN USE"
            ariaLabel="Confirm URL override"
            width={480}
            onClose={onCancel}
            footer={
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10 }}>
                    <Button variant="ghost" type="button" onClick={onCancel}>
                        Cancel
                    </Button>
                    <Button variant="danger" type="button" onClick={onUseAnyway}>
                        Use /{conflict.conflicting_slug} anyway
                    </Button>
                </div>
            }
        >
            <p style={{ margin: 0, fontSize: 17, fontWeight: 600, color: 'var(--fg1)' }}>
                Take URL{' '}
                <span style={{ fontFamily: 'var(--font-mono)', color: 'var(--accent)' }}>
                    /{conflict.conflicting_slug}
                </span>
                ?
            </p>
            <p style={{ margin: '12px 0 0', fontSize: 14.5, lineHeight: 1.55, color: 'var(--fg2)' }}>
                This URL was previously used by your{' '}
                <span style={{ fontWeight: 600, color: 'var(--fg1)' }}>
                    "{conflict.previous_owner_title}"
                </span>{' '}
                list. Visitors who follow old links will land on this list instead.
            </p>
        </CardModal>
    );
}
