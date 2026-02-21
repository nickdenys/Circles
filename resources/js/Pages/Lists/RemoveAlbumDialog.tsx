import { router } from '@inertiajs/react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

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

        router.delete(route('lists.albums.destroy', [listId, album.id]), {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => {
                onRemoved(album.id);
                onOpenChange(false);
            },
        });
    }

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Remove Album</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to remove{' '}
                        <span className="font-medium text-foreground">
                            {album?.title}
                        </span>{' '}
                        from this list?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        onClick={handleConfirm}
                        disabled={processing}
                    >
                        {processing ? 'Removing...' : 'Remove'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
