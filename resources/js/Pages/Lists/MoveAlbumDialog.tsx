import axios from 'axios';
import { Loader2, Search } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/components/ui/popover';

interface ListResult {
    id: number;
    title: string;
    type: string;
}

interface MoveAlbumDialogProps {
    listId: number;
    album: { id: number; title: string } | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onMoved: (albumId: number) => void;
}

export default function MoveAlbumDialog({
    listId,
    album,
    open,
    onOpenChange,
    onMoved,
}: MoveAlbumDialogProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ListResult[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [searching, setSearching] = useState(false);
    const [selectedList, setSelectedList] = useState<ListResult | null>(null);
    const [processing, setProcessing] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const search = useCallback(
        async (q: string) => {
            if (q.length < 1) {
                setResults([]);
                setIsOpen(false);
                return;
            }

            setSearching(true);

            try {
                const response = await axios.get('/lists/search', {
                    params: { q, exclude: listId },
                });
                setResults(response.data.data);
                setIsOpen(true);
            } catch {
                setResults([]);
            } finally {
                setSearching(false);
            }
        },
        [listId],
    );

    function handleInputChange(value: string) {
        setQuery(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        if (value.length < 1) {
            setResults([]);
            setIsOpen(false);
            return;
        }

        debounceRef.current = setTimeout(() => {
            search(value);
        }, 300);
    }

    function handleSelectList(list: ListResult) {
        setSelectedList(list);
        setQuery('');
        setResults([]);
        setIsOpen(false);
    }

    function handleConfirm() {
        if (!album || !selectedList) { return; }

        router.post(
            `/lists/${listId}/albums/${album.id}/move`,
            { destination_list_id: selectedList.id },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    onMoved(album.id);
                    onOpenChange(false);
                },
            },
        );
    }

    function handleOpenChange(value: boolean) {
        if (!value) {
            setQuery('');
            setResults([]);
            setSelectedList(null);
            setIsOpen(false);
        }
        onOpenChange(value);
    }

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Move Album</DialogTitle>
                    <DialogDescription>
                        Move{' '}
                        <span className="font-medium text-foreground">
                            {album?.title}
                        </span>{' '}
                        to another list.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3">
                    <Popover open={isOpen} onOpenChange={setIsOpen}>
                        <PopoverAnchor asChild>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="list-search-input"
                                    placeholder="Search for a list..."
                                    className="pl-9"
                                    value={query}
                                    onChange={(e) => handleInputChange(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Escape' && setIsOpen(false)}
                                />
                                {searching && (
                                    <Loader2 className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                                )}
                            </div>
                        </PopoverAnchor>

                        <PopoverContent
                            id="list-search-results"
                            className="w-[var(--radix-popover-trigger-width)] max-h-48 overflow-y-auto p-0"
                            onOpenAutoFocus={(e) => e.preventDefault()}
                        >
                            {results.map((list) => (
                                <button
                                    key={list.id}
                                    type="button"
                                    className="list-search-result flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition hover:bg-accent"
                                    onClick={() => handleSelectList(list)}
                                >
                                    {list.title}
                                </button>
                            ))}
                        </PopoverContent>
                    </Popover>

                    {selectedList && (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-muted-foreground">
                                Destination:
                            </span>
                            <Badge id="selected-list-badge" variant="secondary">
                                {selectedList.title}
                            </Badge>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => handleOpenChange(false)}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={handleConfirm}
                        disabled={!selectedList || processing}
                    >
                        {processing ? 'Moving...' : 'Confirm'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
