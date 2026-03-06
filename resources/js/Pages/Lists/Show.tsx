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
    Trash2,
    ListMusic,
    Clock,
    Calendar,
    ExternalLink,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { type AddedAlbum } from './AlbumSearch';
import AddAlbumDialog from './AddAlbumDialog';
import DeleteListDialog from './DeleteListDialog';
import EditListDialog from './EditListDialog';
import MoveAlbumDialog from './MoveAlbumDialog';
import RemoveAlbumDialog from './RemoveAlbumDialog';

interface AlbumListDetail {
    id: number;
    title: string;
    description: string | null;
    type: 'system' | 'custom';
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
}

interface PaginatedAlbums {
    data: AlbumItem[];
    next_page_url: string | null;
}

interface ShowProps {
    list: AlbumListDetail;
    albums: PaginatedAlbums;
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

function AlbumCardContent({ album, onMove, onRemove, dragHandleProps }: {
    album: AlbumItem;
    onMove: (album: { id: number; title: string }) => void;
    onRemove: (album: { id: number; title: string }) => void;
    dragHandleProps?: React.HTMLAttributes<HTMLDivElement>;
}) {
    return (
        <Card
            className="album-card relative flex flex-row items-center gap-3 px-4 py-3"
            data-album-db-id={album.id}
        >
            <div
                className="drag-handle flex cursor-grab items-center self-stretch text-muted-foreground"
                {...dragHandleProps}
            >
                <GripVertical className="h-5 w-5" />
            </div>

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
                </div>
            </div>

            <div className="flex shrink-0 items-center gap-1">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8"
                            asChild
                        >
                            <a href={album.spotifyUri}>
                                <ExternalLink className="h-4 w-4" />
                            </a>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Open in Spotify</TooltipContent>
                </Tooltip>
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
                            <ArrowRightLeft className="h-4 w-4" />
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
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Remove from list</TooltipContent>
                </Tooltip>
            </div>
        </Card>
    );
}

function AlbumCard({ album, onMove, onRemove }: {
    album: AlbumItem;
    onMove: (album: { id: number; title: string }) => void;
    onRemove: (album: { id: number; title: string }) => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: album.id });

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
                onMove={onMove}
                onRemove={onRemove}
                dragHandleProps={{ ...attributes, ...listeners }}
            />
        </div>
    );
}

type ViewMode = 'list' | 'grid';

function AlbumGridItemContent({ album }: { album: AlbumItem }) {
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
            </div>
        </div>
    );
}

function AlbumGridItem({ album }: { album: AlbumItem }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: album.id });

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
                opacity: isDragging ? 0.35 : 1,
            }}
            className="cursor-grab touch-none"
            {...attributes}
            {...listeners}
        >
            <AlbumGridItemContent album={album} />
        </div>
    );
}

export default function Show({ list, albums }: ShowProps) {
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
            setOrderedAlbums((prev) => [...prev, ...newItems]);
        } else if (currFirstId !== prevFirstId || currCount < prevCount) {
            setOrderedAlbums(albums.data);
        }

        syncRef.current = { count: currCount, firstId: currFirstId };
    }, [albums.data]);

    function handleDragStart(event: DragStartEvent) {
        const album = orderedAlbums.find((a) => a.id === event.active.id);
        setActiveAlbum(album ?? null);
    }

    function handleDragEnd(event: DragEndEvent) {
        setActiveAlbum(null);

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

    const hasAlbums = orderedAlbums.length > 0;

    return (
        <>
            <Head title={list.title} />

            <div>
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

                        <Button
                            onClick={() => setAddAlbumDialogOpen(true)}
                            id="add-album-button"
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add an Album
                        </Button>

                        {list.type === 'custom' && (
                            <>
                                <Button
                                    variant="outline"
                                    id="edit-list-button"
                                    onClick={() => setEditDialogOpen(true)}
                                >
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    id="delete-list-button"
                                    onClick={() => setDeleteDialogOpen(true)}
                                >
                                    Delete
                                </Button>
                            </>
                        )}
                        </div>

                        {hasAlbums && (
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
                        )}
                    </div>
                </div>

                {!hasAlbums ? (
                    <p className="mt-8 text-center text-muted-foreground">
                        No albums yet. Click "Add an Album" to get started.
                    </p>
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
                                        <AlbumGridItem key={album.id} album={album} />
                                    ))}
                                </InfiniteScroll>
                            </SortableContext>

                            <DragOverlay>
                                {activeAlbum && (
                                    <div className="w-40 scale-[1.05] cursor-grabbing rounded-md shadow-2xl">
                                        <AlbumGridItemContent album={activeAlbum} />
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
                                    {orderedAlbums.map((album) => (
                                        <AlbumCard
                                            key={album.id}
                                            album={album}
                                            onMove={handleMoveAlbum}
                                            onRemove={handleRemoveAlbum}
                                        />
                                    ))}
                                </InfiniteScroll>
                            </SortableContext>

                            <DragOverlay>
                                {activeAlbum && (
                                    <div className="scale-[1.03] cursor-grabbing rounded-xl shadow-2xl">
                                        <AlbumCardContent
                                            album={activeAlbum}
                                            onMove={handleMoveAlbum}
                                            onRemove={handleRemoveAlbum}
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
        </>
    );
}
