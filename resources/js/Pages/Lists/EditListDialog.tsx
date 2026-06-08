import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect } from 'react';
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
    title,
    description,
    mode,
    type,
    open,
    onOpenChange,
}: EditListDialogProps) {
    const isSystem = type === 'system';

    const { data, setData, put, patch, processing, errors, reset } = useForm({
        title: title,
        description: description ?? '',
        mode: mode,
    });

    useEffect(() => {
        if (open) {
            setData({
                title: title,
                description: description ?? '',
                mode: mode,
            });
        }
    }, [open]);

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        const onSuccess = () => {
            reset();
            onOpenChange(false);
        };

        if (isSystem) {
            patch(`/lists/${listId}/mode`, { onSuccess });

            return;
        }

        put(`/lists/${listId}`, { onSuccess });
    }

    function handleOpenChange(value: boolean) {
        if (!value) {
            reset();
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
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
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
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
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
                        value={data.mode}
                        onChange={(value) => setData('mode', value)}
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
        </Dialog>
    );
}
