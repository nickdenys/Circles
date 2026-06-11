import { router } from '@inertiajs/react';
import axios, { AxiosError } from 'axios';
import { FormEvent, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ListModeField, { type ListMode } from '@/Pages/Lists/ListModeField';
import ConfirmSlugOverrideDialog, { type SlugHistoryConflict } from './ConfirmSlugOverrideDialog';

interface EditListDialogProps {
    listId: number;
    title: string;
    description: string | null;
    mode: ListMode;
    type: 'system' | 'custom' | 'reviewed';
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function EditListDialog({
    listId,
    title: initialTitle,
    description: initialDescription,
    mode: initialMode,
    type,
    open,
    onOpenChange,
}: EditListDialogProps) {
    const isSystem = type === 'system';

    const [title, setTitle] = useState(initialTitle);
    const [description, setDescription] = useState(initialDescription ?? '');
    const [mode, setMode] = useState<ListMode>(initialMode);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [conflict, setConflict] = useState<SlugHistoryConflict | null>(null);

    useEffect(() => {
        if (open) {
            setTitle(initialTitle);
            setDescription(initialDescription ?? '');
            setMode(initialMode);
            setErrors({});
            setConflict(null);
        }
    }, [open, initialTitle, initialDescription, initialMode]);

    function submit(force: boolean) {
        setProcessing(true);
        setErrors({});

        if (isSystem) {
            router.patch(
                `/lists/${listId}/mode`,
                { mode },
                {
                    onSuccess: () => onOpenChange(false),
                    onFinish: () => setProcessing(false),
                },
            );
            return;
        }

        axios
            .put(`/lists/${listId}`, {
                title,
                description,
                mode,
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
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit List</DialogTitle>
                    <DialogDescription>
                        Update your list details.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="edit-title">Title</Label>
                        <Input
                            id="edit-title"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            aria-invalid={!!errors.title}
                            disabled={isSystem}
                            className="disabled:pointer-events-auto disabled:cursor-not-allowed"
                        />
                        {errors.title && (
                            <p className="text-sm text-destructive">
                                {errors.title}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edit-description">
                            Description{' '}
                            <span className="text-muted-foreground font-normal">
                                (optional)
                            </span>
                        </Label>
                        <Textarea
                            id="edit-description"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            aria-invalid={!!errors.description}
                            disabled={isSystem}
                        />
                        {errors.description && (
                            <p className="text-sm text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <ListModeField
                        value={mode}
                        onChange={(value) => setMode(value)}
                    />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
            <ConfirmSlugOverrideDialog
                open={conflict !== null}
                conflict={conflict}
                onUseAnyway={() => {
                    setConflict(null);
                    submit(true);
                }}
                onCancel={() => setConflict(null)}
            />
        </Dialog>
    );
}
