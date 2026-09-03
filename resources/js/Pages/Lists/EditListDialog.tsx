import { router } from '@inertiajs/react';
import axios, { AxiosError } from 'axios';
import { Check, Loader2 } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

import { Button } from '@/components/kit/Button';
import { CardModal } from '@/components/kit/CardModal';
import ConfirmSlugOverrideDialog, { type SlugHistoryConflict } from './ConfirmSlugOverrideDialog';
import { DialogField } from './DialogField';

interface EditListDialogProps {
    listId: number;
    title: string;
    description: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function EditListDialog({
    listId,
    title: initialTitle,
    description: initialDescription,
    open,
    onOpenChange,
}: EditListDialogProps) {
    const [title, setTitle] = useState(initialTitle);
    const [description, setDescription] = useState(initialDescription ?? '');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [conflict, setConflict] = useState<SlugHistoryConflict | null>(null);

    useEffect(() => {
        if (open) {
            setTitle(initialTitle);
            setDescription(initialDescription ?? '');
            setErrors({});
            setConflict(null);
        }
    }, [open, initialTitle, initialDescription]);

    function submit(force: boolean) {
        setProcessing(true);
        setErrors({});

        axios
            .put(`/lists/${listId}`, {
                title,
                description,
                force_slug: force,
            })
            .then(() => {
                onOpenChange(false);
                router.reload();
            })
            .catch((error: AxiosError<Record<string, unknown>>) => {
                const data = error.response?.data;
                if (data && data.error === 'slug_history_conflict') {
                    setConflict({
                        conflicting_slug: data.conflicting_slug as string,
                        previous_owner_title: data.previous_owner_title as string,
                        suggested_alternative: data.suggested_alternative as string,
                    });
                    return;
                }
                const fieldErrors = (data?.errors ?? {}) as Record<string, string[]>;
                const mapped: Record<string, string> = {};
                Object.entries(fieldErrors).forEach(([k, v]) => {
                    mapped[k] = Array.isArray(v) ? v[0] : String(v);
                });
                setErrors(mapped);
            })
            .finally(() => setProcessing(false));
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        submit(false);
    }

    function handleOpenChange(value: boolean) {
        if (!value) {
            setErrors({});
            setConflict(null);
        }
        onOpenChange(value);
    }

    return (
        <>
            {open && (
                <CardModal
                    label="EDIT LIST"
                    ariaLabel="Edit list"
                    width={520}
                    onClose={() => {
                        if (conflict) {
                            return;
                        }
                        handleOpenChange(false);
                    }}
                    footer={
                        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10 }}>
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
                                form="edit-list-form"
                                icon={processing ? undefined : Check}
                                disabled={processing}
                            >
                                {processing && <Loader2 size={15} strokeWidth={2} className="animate-spin" />}
                                Save
                            </Button>
                        </div>
                    }
                >
                    <form
                        id="edit-list-form"
                        onSubmit={handleSubmit}
                        style={{ display: 'flex', flexDirection: 'column', gap: 20 }}
                    >
                        <DialogField
                            id="edit-title"
                            label="Title"
                            value={title}
                            onChange={setTitle}
                            error={errors.title}
                            autoFocus
                        />
                        <DialogField
                            id="edit-description"
                            label="Description"
                            value={description}
                            onChange={setDescription}
                            error={errors.description}
                            optional
                            multiline
                        />
                    </form>
                </CardModal>
            )}

            <ConfirmSlugOverrideDialog
                open={conflict !== null}
                conflict={conflict}
                onUseAnyway={() => {
                    setConflict(null);
                    submit(true);
                }}
                onCancel={() => setConflict(null)}
            />
        </>
    );
}
