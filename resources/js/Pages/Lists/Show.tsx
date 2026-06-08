import { Head, InfiniteScroll, router } from '@inertiajs/react';
import axios from 'axios';
import {
    closestCenter,
    DndContext,
    DragEndEvent,
    DragOverlay,
    DragStartEvent,
    PointerSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { arrayMove, rectSortingStrategy, SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    GripVertical,
    LayoutGrid,
    List,
    Loader2,
    Music,
    Plus,
    RefreshCw,
    ArrowRightLeft,
    ArrowDownUp,
    ArrowUp,
    ArrowDown,
    Check,
    Trash2,
    ListMusic,
    Clock,
    Calendar,
    ExternalLink,
    MoreVertical,
} from 'lucide-react';
import { useEffect, useRef, useState, useCallback } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { StarRating } from '@/components/ui/star-rating';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { type ListMode } from './ListModeField';
import { type AddedAlbum } from './AlbumSearch';
import AddAlbumDialog from './AddAlbumDialog';
import DeleteListDialog from './DeleteListDialog';
import EditListDialog from './EditListDialog';
import MoveAlbumDialog from './MoveAlbumDialog';
import RatingDialog, { type RatingDialogAlbum } from './RatingDialog';
import RemoveAlbumDialog from './RemoveAlbumDialog';

type ListType = 'system' | 'custom' | 'reviewed';

interface AlbumListDetail {
    id: number;
    title: string;
    description: string | null;
    type: ListType;
    mode: ListMode;
    albumsCount: number;
}

interface AlbumItem {
    id: number;
    spotifyId: string;
    title: string;
    artists: string;
    coverUrl: string | null;
    runtimeMs: number;
    albumType: string;
    totalTracks: number;
    releaseDate: string;
    spotifyUri: string;
    genres: string[];
    note: string | null;
    rating?: number | null;
    review?: string | null;
}

interface PaginatedAlbums {
    data: AlbumItem[];
    next_page_url: string | null;
}

interface ShowProps {
    list: AlbumListDetail;
    albums: PaginatedAlbums;
    sort: string;
    direction: 'asc' | 'desc';
    [key: string]: unknown;
}

function formatRuntime(ms: number): string {
    const totalMinutes = Math.round(ms / 60000);
    if (totalMinutes >= 60) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return `${hours}h ${minutes}m`;
    }
    return `${totalMinutes} min`;
}

function AlbumNoteEditor({ listId, albumId, note, onNoteUpdated }: {
    listId: number;
    albumId: number;
    note: string | null;
    onNoteUpdated: (albumId: number, note: string | null) => void;
}) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(note ?? '');
    const [saving, setSaving] = useState(false);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const resizeTextarea = useCallback(() => {
        const el = textareaRef.current;
        if (el) {
            el.style.height = 'auto';
            el.style.height = `${el.scrollHeight}px`;
        }
    }, []);

    useEffect(() => {
        if (editing) {
            resizeTextarea();
            textareaRef.current?.focus();
        }
    }, [editing, resizeTextarea]);

    function handleSave() {
        const trimmed = draft.trim();
        const value = trimmed === '' ? null : trimmed;

        setSaving(true);

        axios
            .patch(`/lists/${listId}/albums/${albumId}`, { note: value })
            .then(() => {
                onNoteUpdated(albumId, value);
                setEditing(false);
                toast.success('Note saved');
            })
            .catch(() => {
                toast.error('Could not save note');
            })
            .finally(() => {
                setSaving(false);
            });
    }

    function handleCancel() {
        setDraft(note ?? '');
        setEditing(false);
    }

    if (editing) {
        return (
            <div className="mt-2 space-y-2">
                <textarea
                    ref={textareaRef}
                    value={draft}
                    onChange={(e) => {
                        setDraft(e.target.value);
                        resizeTextarea();
                    }}
                    readOnly={saving}
                    className="w-full resize-none rounded-md border bg-transparent px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    rows={1}
                />
                <div className="flex gap-2">
                    <Button
                        size="sm"
                        onClick={handleSave}
                        disabled={saving}
                    >
                        {saving && <Loader2 className="mr-1 h-3 w-3 animate-spin" />}
                        Save
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={handleCancel}
                        disabled={saving}
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    if (note) {
        return (
            <p
                className="mt-2 cursor-pointer whitespace-pre-wrap text-sm text-muted-foreground"
                onClick={() => {
                    setDraft(note);
                    setEditing(true);
                }}
            >
                {note}
            </p>
        );
    }

    return (
        <button
            type="button"
            className="mt-2 text-xs text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 [@media(hover:none)]:opacity-100"
            onClick={() => {
                setDraft('');
                setEditing(true);
            }}
        >
            + note
        </button>
    );
}

function ListenedButton({ albumId, onClick }: { albumId: number; onClick: () => void }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="default"
                    size="icon-lg"
                    className="listened-button"
                    data-album-id={albumId}
                    onClick={onClick}
                >
                    <Check className="h-6 w-6" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>I listened to this</TooltipContent>
        </Tooltip>
    );
}

function AlbumActionsMenu({ album, onMove, onRemove }: {
    album: AlbumItem;
    onMove: (album: { id: number; title: string }) => void;
    onRemove: (album: { id: number; title: string }) => void;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                render={
                    <Button
                        variant="ghost"
                        size="icon"
                        className="album-actions-button h-8 w-8"
                        aria-label="Album actions"
                    />
                }
            >
                <MoreVertical className="h-6 w-6" />
            </PopoverTrigger>
            <PopoverContent align="end" className="w-48 p-1">
                <Button
                    variant="ghost"
                    size="sm"
                    className="move-album-button w-full justify-start font-normal"
                    data-album-id={album.id}
                    data-album-title={album.title}
                    onClick={() => {
                        setOpen(false);
                        onMove({ id: album.id, title: album.title });
                    }}
                >
                    <ArrowRightLeft className="mr-2 h-4 w-4" />
                    Move to list
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    className="remove-album-button w-full justify-start font-normal text-destructive hover:text-destructive"
                    data-album-id={album.id}
                    data-album-title={album.title}
                    onClick={() => {
                        setOpen(false);
                        onRemove({ id: album.id, title: album.title });
                    }}
                >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Remove from list
                </Button>
            </PopoverContent>
        </Popover>
    );
}

function AlbumCardContent({ album, position, listId, listType, mode, onMove, onRemove, onRate, onNoteUpdated, dragHandleProps, draggable = true }: {
    album: AlbumItem;
    position?: number;
    listId: number;
    listType: ListType;
    mode: ListMode;
    onMove: (album: { id: number; title: string }) => void;
    onRemove: (album: { id: number; title: string }) => void;
    onRate: (album: AlbumItem) => void;
    onNoteUpdated: (albumId: number, note: string | null) => void;
    dragHandleProps?: React.HTMLAttributes<HTMLDivElement>;
    draggable?: boolean;
}) {
    const isReviewed = listType === 'reviewed';

    function handleCardClick() {
        if (isReviewed) {
            onRate(album);
        }
    }

    function handleCardKeyDown(event: React.KeyboardEvent<HTMLDivElement>) {
        if (isReviewed && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            onRate(album);
        }
    }

    return (
        <Card
            className={cn(
                'album-card group relative flex flex-row items-center gap-3 px-4 py-3',
                isReviewed && 'reviewed-card cursor-pointer hover:bg-accent/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
            )}
            data-album-db-id={album.id}
            role={isReviewed ? 'button' : undefined}
            tabIndex={isReviewed ? 0 : undefined}
            onClick={isReviewed ? handleCardClick : undefined}
            onKeyDown={isReviewed ? handleCardKeyDown : undefined}
        >
            {draggable && !isReviewed && (
                <div
                    className="drag-handle flex cursor-grab items-center self-stretch text-muted-foreground"
                    {...dragHandleProps}
                >
                    <GripVertical className="h-4 w-4" />
                </div>
            )}

            {position !== undefined && (
                <span className="w-6 shrink-0 text-center text-sm font-medium text-muted-foreground">
                    {position}
                </span>
            )}

            <div className="flex min-w-0 flex-1 items-center gap-5">
                <div className="shrink-0">
                    {album.coverUrl ? (
                        <img
                            src={album.coverUrl}
                            alt={album.title}
                            className="h-24 w-24 rounded object-cover"
                        />
                    ) : (
                        <div className="flex h-24 w-24 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                            <Music className="h-6 w-6 text-muted-foreground" />
                        </div>
                    )}
                </div>

                <div className="min-w-0 flex-1">
                    <p className="font-medium">{album.title}</p>
                    <p className="text-sm text-muted-foreground">{album.artists}</p>
                    <div className="mt-2 flex flex-wrap items-center gap-4">
                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Calendar className="h-3 w-3 shrink-0" />
                            {album.releaseDate.slice(0, 4)}
                        </p>
                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <ListMusic className="h-3 w-3 shrink-0" />
                            {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}
                        </p>
                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Clock className="h-3 w-3 shrink-0" />
                            {formatRuntime(album.runtimeMs)}
                        </p>
                    </div>
                    {album.genres.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1">
                            {album.genres.map((genre) => (
                                <Badge key={genre} variant="secondary">
                                    {genre}
                                </Badge>
                            ))}
                        </div>
                    )}
                    {isReviewed ? (
                        <div className="mt-2 space-y-2">
                            <StarRating value={album.rating ?? 0} size="md" />
                            {album.review && (
                                <p className="album-review whitespace-pre-wrap text-sm text-muted-foreground">
                                    {album.review}
                                </p>
                            )}
                        </div>
                    ) : (
                        <AlbumNoteEditor
                            listId={listId}
                            albumId={album.id}
                            note={album.note}
                            onNoteUpdated={onNoteUpdated}
                        />
                    )}
                </div>
            </div>

            <div className="flex shrink-0 items-center gap-1">
                {!isReviewed && mode === 'listening' && (
                    <ListenedButton albumId={album.id} onClick={() => onRate(album)} />
                )}

                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8"
                            asChild
                        >
                            <a
                                href={album.spotifyUri}
                                onClick={(event) => event.stopPropagation()}
                            >
                                <ExternalLink className="h-6 w-6" />
                            </a>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Open in Spotify</TooltipContent>
                </Tooltip>

                {!isReviewed && (mode === 'listening' ? (
                    <AlbumActionsMenu album={album} onMove={onMove} onRemove={onRemove} />
                ) : (
                    <>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="move-album-button h-8 w-8"
                                    data-album-id={album.id}
                                    data-album-title={album.title}
                                    onClick={() => onMove({ id: album.id, title: album.title })}
                                >
                                    <ArrowRightLeft className="h-6 w-6" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Move to list</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="remove-album-button h-8 w-8 text-destructive hover:text-destructive"
                                    data-album-id={album.id}
                                    data-album-title={album.title}
                                    onClick={() => onRemove({ id: album.id, title: album.title })}
                                >
                                    <Trash2 className="h-6 w-6" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Remove from list</TooltipContent>
                        </Tooltip>
                    </>
                ))}
            </div>
        </Card>
    );
}

function AlbumCard({ album, position, listId, listType, mode, onMove, onRemove, onRate, onNoteUpdated, draggable = true }: {
    album: AlbumItem;
    position?: number;
    listId: number;
    listType: ListType;
    mode: ListMode;
    onMove: (album: { id: number; title: string }) => void;
    onRemove: (album: { id: number; title: string }) => void;
    onRate: (album: AlbumItem) => void;
    onNoteUpdated: (albumId: number, note: string | null) => void;
    draggable?: boolean;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: album.id,
        disabled: !draggable,
    });

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
                opacity: isDragging ? 0.35 : 1,
            }}
        >
            <AlbumCardContent
                album={album}
                position={position}
                listId={listId}
                listType={listType}
                mode={mode}
                onMove={onMove}
                onRemove={onRemove}
                onRate={onRate}
                onNoteUpdated={onNoteUpdated}
                draggable={draggable}
                dragHandleProps={{ ...attributes, ...listeners }}
            />
        </div>
    );
}

type ViewMode = 'list' | 'grid';

function AlbumGridItemContent({ album, showRating = false }: { album: AlbumItem; showRating?: boolean }) {
    return (
        <div className="flex flex-col gap-2">
            {album.coverUrl ? (
                <img
                    src={album.coverUrl}
                    alt={album.title}
                    className="aspect-square w-full rounded-md object-cover"
                />
            ) : (
                <div className="flex aspect-square w-full items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-800">
                    <Music className="h-8 w-8 text-muted-foreground" />
                </div>
            )}
            <div className="min-w-0">
                <p className="truncate text-sm font-medium">{album.title}</p>
                <p className="truncate text-xs text-muted-foreground">{album.artists}</p>
                {showRating && (
                    <div className="mt-1">
                        <StarRating value={album.rating ?? 0} size="sm" />
                    </div>
                )}
            </div>
        </div>
    );
}

function AlbumGridItem({ album, listType, onRate, draggable = true }: {
    album: AlbumItem;
    listType: ListType;
    onRate: (album: AlbumItem) => void;
    draggable?: boolean;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: album.id,
        disabled: !draggable,
    });

    const isReviewed = listType === 'reviewed';

    function handleClick() {
        if (isReviewed) {
            onRate(album);
        }
    }

    function handleKeyDown(event: React.KeyboardEvent<HTMLDivElement>) {
        if (isReviewed && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            onRate(album);
        }
    }

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
                opacity: isDragging ? 0.35 : 1,
            }}
            className={cn(
                draggable && !isReviewed && 'cursor-grab touch-none',
                isReviewed && 'reviewed-card cursor-pointer rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
            )}
            {...(isReviewed
                ? {
                      role: 'button' as const,
                      tabIndex: 0,
                      onClick: handleClick,
                      onKeyDown: handleKeyDown,
                  }
                : draggable
                    ? { ...attributes, ...listeners }
                    : {})}
        >
            <AlbumGridItemContent album={album} showRating={isReviewed} />
        </div>
    );
}

const SORT_OPTIONS = [
    { value: 'manual', label: 'Manual' },
    { value: 'title', label: 'Title' },
    { value: 'artist', label: 'Artist' },
    { value: 'release_date', label: 'Release date' },
    { value: 'added', label: 'Date added' },
] as const;

const DEFAULT_SORT_DIRECTION: Record<string, 'asc' | 'desc'> = {
    title: 'asc',
    artist: 'asc',
    release_date: 'desc',
    added: 'desc',
};

function SortControl({ listId, sort, direction }: { listId: number; sort: string; direction: 'asc' | 'desc' }) {
    const [open, setOpen] = useState(false);
    const activeLabel = SORT_OPTIONS.find((option) => option.value === sort)?.label ?? 'Manual';

    function applySort(value: string) {
        setOpen(false);

        const nextDirection = value === 'manual'
            ? 'asc'
            : value === sort
                ? (direction === 'asc' ? 'desc' : 'asc')
                : DEFAULT_SORT_DIRECTION[value];

        router.patch(`/lists/${listId}/sort`, { sort: value, direction: nextDirection }, {
            preserveScroll: false,
        });
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger render={<Button variant="outline" size="sm" id="sort-control" />}>
                <ArrowDownUp className="mr-1.5 h-4 w-4" />
                Sort: {activeLabel}
                {sort !== 'manual' && (
                    direction === 'asc'
                        ? <ArrowUp className="ml-1.5 h-3.5 w-3.5" />
                        : <ArrowDown className="ml-1.5 h-3.5 w-3.5" />
                )}
            </PopoverTrigger>
            <PopoverContent align="end" className="w-44 p-1">
                {SORT_OPTIONS.map((option) => {
                    const isActive = option.value === sort;

                    return (
                        <Button
                            key={option.value}
                            variant="ghost"
                            size="sm"
                            className="w-full justify-between font-normal"
                            onClick={() => applySort(option.value)}
                        >
                            {option.label}
                            {isActive && option.value === 'manual' && <Check className="h-3.5 w-3.5" />}
                            {isActive && option.value !== 'manual' && (
                                direction === 'asc'
                                    ? <ArrowUp className="h-3.5 w-3.5" />
                                    : <ArrowDown className="h-3.5 w-3.5" />
                            )}
                        </Button>
                    );
                })}
            </PopoverContent>
        </Popover>
    );
}

export default function Show({ list, albums, sort, direction }: ShowProps) {
    const isManual = sort === 'manual';
    const [refreshing, setRefreshing] = useState(false);
    const [viewMode, setViewMode] = useState<ViewMode>(() => {
        const stored = localStorage.getItem(`list-${list.id}-view`);
        return stored === 'grid' ? 'grid' : 'list';
    });

    function changeViewMode(mode: ViewMode) {
        setViewMode(mode);
        localStorage.setItem(`list-${list.id}-view`, mode);
    }
    const [orderedAlbums, setOrderedAlbums] = useState<AlbumItem[]>(albums.data);
    const [albumCount, setAlbumCount] = useState(list.albumsCount);
    const [albumToMove, setAlbumToMove] = useState<{ id: number; title: string } | null>(null);
    const [moveDialogOpen, setMoveDialogOpen] = useState(false);
    const [albumToRemove, setAlbumToRemove] = useState<{ id: number; title: string } | null>(null);
    const [removeDialogOpen, setRemoveDialogOpen] = useState(false);
    const [addAlbumDialogOpen, setAddAlbumDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [activeAlbum, setActiveAlbum] = useState<AlbumItem | null>(null);
    const [albumToReview, setAlbumToReview] = useState<RatingDialogAlbum | null>(null);
    const [ratingDialogOpen, setRatingDialogOpen] = useState(false);

    const isReviewedList = list.type === 'reviewed';

    const syncRef = useRef({ count: albums.data.length, firstId: albums.data[0]?.id });

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: { distance: 5 },
        }),
        useSensor(TouchSensor, {
            activationConstraint: { delay: 150, tolerance: 5 },
        }),
    );

    useEffect(() => {
        const { count: prevCount, firstId: prevFirstId } = syncRef.current;
        const currCount = albums.data.length;
        const currFirstId = albums.data[0]?.id;

        if (currCount > prevCount && currFirstId === prevFirstId) {
            const newItems = albums.data.slice(prevCount);
            setOrderedAlbums((prev) => {
                const existingIds = new Set(prev.map((a) => a.id));
                return [...prev, ...newItems.filter((a) => !existingIds.has(a.id))];
            });
        } else if (currFirstId !== prevFirstId || currCount < prevCount) {
            setOrderedAlbums(albums.data);
        }

        syncRef.current = { count: currCount, firstId: currFirstId };
    }, [albums.data]);

    useEffect(() => {
        setOrderedAlbums(albums.data);
        syncRef.current = { count: albums.data.length, firstId: albums.data[0]?.id };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sort, direction]);

    function handleDragStart(event: DragStartEvent) {
        if (!isManual) {
            return;
        }

        const album = orderedAlbums.find((a) => a.id === event.active.id);
        setActiveAlbum(album ?? null);
    }

    function handleDragEnd(event: DragEndEvent) {
        setActiveAlbum(null);

        if (!isManual) {
            return;
        }

        const { active, over } = event;
        if (!over || active.id === over.id) {
            return;
        }

        setOrderedAlbums((prev) => {
            const oldIndex = prev.findIndex((a) => a.id === active.id);
            const newIndex = prev.findIndex((a) => a.id === over.id);
            const reordered = arrayMove(prev, oldIndex, newIndex);

            axios.put(`/lists/${list.id}/albums/reorder`, {
                album_ids: reordered.map((a) => a.id),
            });

            return reordered;
        });
    }

    function handleRefresh() {
        router.post(`/lists/${list.id}/refresh`, {}, {
            onStart: () => setRefreshing(true),
            onFinish: () => setRefreshing(false),
        });
    }

    function handleAlbumAdded(album: AddedAlbum) {
        setOrderedAlbums((prev) => [...prev, album]);
        setAlbumCount((prev) => prev + 1);
    }

    function handleMoveAlbum(album: { id: number; title: string }) {
        setAlbumToMove(album);
        setMoveDialogOpen(true);
    }

    function handleAlbumMoved(albumId: number) {
        setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId));
        setAlbumCount((prev) => prev - 1);
    }

    function handleRemoveAlbum(album: { id: number; title: string }) {
        setAlbumToRemove(album);
        setRemoveDialogOpen(true);
    }

    function handleAlbumRemoved(albumId: number) {
        setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId));
        setAlbumCount((prev) => prev - 1);
    }

    function handleNoteUpdated(albumId: number, note: string | null) {
        setOrderedAlbums((prev) =>
            prev.map((a) => (a.id === albumId ? { ...a, note } : a)),
        );
    }

    function handleRateAlbum(album: AlbumItem) {
        setAlbumToReview({
            id: album.id,
            title: album.title,
            currentRating: album.rating ?? null,
            currentReview: album.review ?? null,
        });
        setRatingDialogOpen(true);
    }

    function handleReviewSubmitted(albumId: number, rating: number, review: string | null) {
        if (isReviewedList) {
            setOrderedAlbums((prev) =>
                prev.map((a) => (a.id === albumId ? { ...a, rating, review } : a)),
            );

            return;
        }

        setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId));
        setAlbumCount((prev) => prev - 1);
    }

    function handleAlbumUnreviewed(albumId: number) {
        setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId));
        setAlbumCount((prev) => prev - 1);
    }

    const hasAlbums = orderedAlbums.length > 0;

    return (
        <>
            <Head title={list.title} />

            <div className="pb-48">
                <div className="flex justify-between gap-4">
                    <div className="min-w-0">
                        <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                            {list.title}
                        </h1>
                        {list.description && (
                            <p className="mt-1 text-muted-foreground">
                                {list.description}
                            </p>
                        )}
                        <p className="mt-2 text-sm text-muted-foreground">
                            {albumCount}{' '}
                            {albumCount === 1 ? 'album' : 'albums'}
                        </p>
                    </div>

                    <div className="flex shrink-0 flex-col items-end gap-8">
                        <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            onClick={handleRefresh}
                            disabled={refreshing}
                            id="refresh-button"
                        >
                            {refreshing ? (
                                <Loader2 id="refresh-spinner" className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="mr-2 h-4 w-4" />
                            )}
                            {refreshing ? 'Refreshing...' : 'Refresh Album Data'}
                        </Button>

                        {!isReviewedList && (
                            <Button
                                onClick={() => setAddAlbumDialogOpen(true)}
                                id="add-album-button"
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Add an Album
                            </Button>
                        )}

                        {!isReviewedList && (
                            <Button
                                variant="outline"
                                id="edit-list-button"
                                onClick={() => setEditDialogOpen(true)}
                            >
                                Edit
                            </Button>
                        )}
                        {list.type === 'custom' && (
                            <Button
                                variant="outline"
                                id="delete-list-button"
                                onClick={() => setDeleteDialogOpen(true)}
                            >
                                Delete
                            </Button>
                        )}
                        </div>

                        {hasAlbums && (
                            <div className="flex items-center gap-2">
                                <SortControl listId={list.id} sort={sort} direction={direction} />

                                <div className="inline-flex items-center rounded-md border">
                                    <Button
                                        variant={viewMode === 'list' ? 'default' : 'ghost'}
                                        size="sm"
                                        className="rounded-r-none border-0"
                                        onClick={() => changeViewMode('list')}
                                        id="view-mode-list"
                                    >
                                        <List className="mr-1.5 h-4 w-4" />
                                        List
                                    </Button>
                                    <Button
                                        variant={viewMode === 'grid' ? 'default' : 'ghost'}
                                        size="sm"
                                        className="rounded-l-none border-0"
                                        onClick={() => changeViewMode('grid')}
                                        id="view-mode-grid"
                                    >
                                        <LayoutGrid className="mr-1.5 h-4 w-4" />
                                        Grid
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {!hasAlbums ? (
                    isReviewedList ? (
                        <div className="mt-8 text-center text-muted-foreground">
                            <p>No reviewed albums yet.</p>
                            <p>Rate an album from any list and it'll show up here.</p>
                        </div>
                    ) : (
                        <p className="mt-8 text-center text-muted-foreground">
                            No albums yet. Click "Add an Album" to get started.
                        </p>
                    )
                ) : viewMode === 'grid' ? (
                    <div className="mt-6">
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragStart={handleDragStart}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={orderedAlbums.map((a) => a.id)}
                                strategy={rectSortingStrategy}
                            >
                                <InfiniteScroll
                                    data="albums"
                                    className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                                    loading={() => (
                                        <div
                                            id="album-scroll-sentinel"
                                            className="col-span-full flex justify-center py-4"
                                        >
                                            <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                                        </div>
                                    )}
                                >
                                    {orderedAlbums.map((album) => (
                                        <AlbumGridItem
                                            key={album.id}
                                            album={album}
                                            listType={list.type}
                                            onRate={handleRateAlbum}
                                            draggable={isManual && !isReviewedList}
                                        />
                                    ))}
                                </InfiniteScroll>
                            </SortableContext>

                            <DragOverlay>
                                {activeAlbum && (
                                    <div className="w-40 scale-[1.05] cursor-grabbing rounded-md shadow-2xl">
                                        <AlbumGridItemContent album={activeAlbum} showRating={isReviewedList} />
                                    </div>
                                )}
                            </DragOverlay>
                        </DndContext>
                    </div>
                ) : (
                    <div className="mt-6">
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragStart={handleDragStart}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={orderedAlbums.map((a) => a.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <InfiniteScroll
                                    data="albums"
                                    className="space-y-3"
                                    loading={() => (
                                        <div
                                            id="album-scroll-sentinel"
                                            className="flex justify-center py-4"
                                        >
                                            <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                                        </div>
                                    )}
                                >
                                    {orderedAlbums.map((album, index) => (
                                        <AlbumCard
                                            key={album.id}
                                            album={album}
                                            position={index + 1}
                                            listId={list.id}
                                            listType={list.type}
                                            mode={list.mode}
                                            onMove={handleMoveAlbum}
                                            onRemove={handleRemoveAlbum}
                                            onRate={handleRateAlbum}
                                            onNoteUpdated={handleNoteUpdated}
                                            draggable={isManual && !isReviewedList}
                                        />
                                    ))}
                                </InfiniteScroll>
                            </SortableContext>

                            <DragOverlay>
                                {activeAlbum && (
                                    <div className="scale-[1.03] cursor-grabbing rounded-xl shadow-2xl">
                                        <AlbumCardContent
                                            album={activeAlbum}
                                            listId={list.id}
                                            listType={list.type}
                                            mode={list.mode}
                                            onMove={handleMoveAlbum}
                                            onRemove={handleRemoveAlbum}
                                            onRate={handleRateAlbum}
                                            onNoteUpdated={handleNoteUpdated}
                                        />
                                    </div>
                                )}
                            </DragOverlay>
                        </DndContext>
                    </div>
                )}
            </div>

            <AddAlbumDialog
                listId={list.id}
                open={addAlbumDialogOpen}
                onOpenChange={setAddAlbumDialogOpen}
                onAlbumAdded={handleAlbumAdded}
            />

            <DeleteListDialog
                listId={list.id}
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            />

            <EditListDialog
                listId={list.id}
                title={list.title}
                description={list.description}
                mode={list.mode}
                type={list.type}
                open={editDialogOpen}
                onOpenChange={setEditDialogOpen}
            />

            <MoveAlbumDialog
                listId={list.id}
                album={albumToMove}
                open={moveDialogOpen}
                onOpenChange={setMoveDialogOpen}
                onMoved={handleAlbumMoved}
            />

            <RemoveAlbumDialog
                listId={list.id}
                album={albumToRemove}
                open={removeDialogOpen}
                onOpenChange={setRemoveDialogOpen}
                onRemoved={handleAlbumRemoved}
            />

            <RatingDialog
                listId={list.id}
                album={albumToReview}
                open={ratingDialogOpen}
                onOpenChange={setRatingDialogOpen}
                onSubmitted={handleReviewSubmitted}
                allowUnreview={isReviewedList}
                onUnreviewed={handleAlbumUnreviewed}
            />
        </>
    );
}
