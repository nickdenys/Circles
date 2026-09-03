import { router } from '@inertiajs/react';
import axios, { AxiosError } from 'axios';
import { Check, Loader2 } from 'lucide-react';
import { FormEvent, useState } from 'react';

import { Button } from '@/components/kit/Button';
import { CardModal } from '@/components/kit/CardModal';
import ConfirmSlugOverrideDialog, { type SlugHistoryConflict } from './ConfirmSlugOverrideDialog';
import { DialogField } from './DialogField';

interface CreateListDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function CreateListDialog({ open, onOpenChange }: CreateListDialogProps) {
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [conflict, setConflict] = useState<SlugHistoryConflict | null>(null);

    function reset() {
        setTitle('');
        setDescription('');
        setErrors({});
        setConflict(null);
    }

    function submit(force: boolean) {
        setProcessing(true);
        setErrors({});

        axios
            .post('/lists', {
                title,
                description,
                force_slug: force,
            })
            .then(() => {
                reset();
                onOpenChange(false);
                router.reload({ only: ['lists'] });
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
            reset();
        }
        onOpenChange(value);
    }

    return (
        <>
            {open && (
                <CardModal
                    label="NEW LIST"
                    ariaLabel="Create list"
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
                                form="create-list-form"
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
                        id="create-list-form"
                        onSubmit={handleSubmit}
                        style={{ display: 'flex', flexDirection: 'column', gap: 20 }}
                    >
                        <DialogField
                            id="title"
                            label="Title"
                            value={title}
                            onChange={setTitle}
                            error={errors.title}
                            autoFocus
                        />
                        <DialogField
                            id="description"
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
