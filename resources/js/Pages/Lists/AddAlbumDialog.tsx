import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AlbumSearch, { type AddedAlbum } from './AlbumSearch';

interface AddAlbumDialogProps {
    listId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAlbumAdded: (album: AddedAlbum) => void;
}

export default function AddAlbumDialog({
    listId,
    open,
    onOpenChange,
    onAlbumAdded,
}: AddAlbumDialogProps) {
    const [keepOpen, setKeepOpen] = useState(false);
    const [searchKey, setSearchKey] = useState(0);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add an Album</DialogTitle>
                    <DialogDescription>
                        Search Spotify to add an album to this list.
                    </DialogDescription>
                </DialogHeader>

                <AlbumSearch
                    key={searchKey}
                    listId={listId}
                    onAlbumAdded={(album) => {
                        onAlbumAdded(album);
                        if (keepOpen) {
                            setSearchKey((k) => k + 1);
                        } else {
                            onOpenChange(false);
                        }
                    }}
                />

                <label className="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground">
                    <input
                        type="checkbox"
                        checked={keepOpen}
                        onChange={(e) => setKeepOpen(e.target.checked)}
                        className="cursor-pointer accent-primary"
                    />
                    Keep open and add multiple albums
                </label>
            </DialogContent>
        </Dialog>
    );
}
