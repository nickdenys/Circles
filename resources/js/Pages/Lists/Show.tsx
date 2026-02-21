import { Head, InfiniteScroll, router } from '@inertiajs/react';
import axios from 'axios';
import {
    GripVertical,
    Loader2,
    Music,
    RefreshCw,
    ArrowRightLeft,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AlbumSearch, { type AddedAlbum } from './AlbumSearch';
import DeleteListDialog from './DeleteListDialog';
import EditListDialog from './EditListDialog';
import MoveAlbumDialog from './MoveAlbumDialog';
import RemoveAlbumDialog from './RemoveAlbumDialog';
import { useDragReorder } from './useDragReorder';

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

function formatAlbumType(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

function AlbumCard({ album, onMove, onRemove }: { album: AlbumItem; onMove: (album: { id: number; title: string }) => void; onRemove: (album: { id: number; title: string }) => void }) {
    return (
        <Card
            className="album-card group relative flex items-start gap-4 px-4 py-3"
            data-album-db-id={album.id}
        >
            <div className="drag-handle flex cursor-grab items-center self-stretch text-muted-foreground">
                <GripVertical className="h-5 w-5" />
            </div>

            <a
                href={album.spotifyUri}
                className="shrink-0"
            >
                {album.coverUrl ? (
                    <img
                        src={album.coverUrl}
                        alt={album.title}
                        className="h-16 w-16 rounded object-cover"
                    />
                ) : (
                    <div className="flex h-16 w-16 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                        <Music className="h-6 w-6 text-muted-foreground" />
                    </div>
                )}
            </a>

            <div className="min-w-0 flex-1">
                <a
                    href={album.spotifyUri}
                    className="font-medium hover:underline"
                >
                    {album.title}
                </a>
                <p className="text-sm text-muted-foreground">
                    {album.artists}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {formatAlbumType(album.albumType)}
                    {' · '}
                    {album.totalTracks}{' '}
                    {album.totalTracks === 1
                        ? 'track'
                        : 'tracks'}
                    {' · '}
                    {formatRuntime(album.runtimeMs)}
                </p>
                <p className="text-xs text-muted-foreground">
                    {album.releaseDate}
                </p>
            </div>

            <div className="flex shrink-0 items-center gap-1 opacity-0 transition group-hover:opacity-100">
                <Button
                    variant="ghost"
                    size="icon"
                    className="move-album-button h-8 w-8"
                    title="Move to list"
                    data-album-id={album.id}
                    data-album-title={album.title}
                    onClick={() => onMove({ id: album.id, title: album.title })}
                >
                    <ArrowRightLeft className="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    className="remove-album-button h-8 w-8 text-destructive hover:text-destructive"
                    title="Remove from list"
                    data-album-id={album.id}
                    data-album-title={album.title}
                    onClick={() => onRemove({ id: album.id, title: album.title })}
                >
                    <Trash2 className="h-4 w-4" />
                </Button>
            </div>
        </Card>
    );
}

export default function Show({ list, albums }: ShowProps) {
    const [refreshing, setRefreshing] = useState(false);
    const [orderedAlbums, setOrderedAlbums] = useState<AlbumItem[]>(albums.data);
    const [albumCount, setAlbumCount] = useState(list.albumsCount);
    const [albumToMove, setAlbumToMove] = useState<{ id: number; title: string } | null>(null);
    const [moveDialogOpen, setMoveDialogOpen] = useState(false);
    const [albumToRemove, setAlbumToRemove] = useState<{ id: number; title: string } | null>(null);
    const [removeDialogOpen, setRemoveDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const albumsContainerRef = useRef<HTMLDivElement>(null);
    const syncRef = useRef({ count: albums.data.length, firstId: albums.data[0]?.id });

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

    function handleReorder(albumIds: number[]) {
        setOrderedAlbums((prev) => {
            const albumMap = new Map(prev.map((a) => [a.id, a]));
            return albumIds.map((id) => albumMap.get(id)!).filter(Boolean);
        });

        axios.put(`/lists/${list.id}/albums/reorder`, {
            album_ids: albumIds,
        });
    }

    useDragReorder(albumsContainerRef, handleReorder);

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
                <div className="flex items-start justify-between gap-4">
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

                    <div className="flex shrink-0 items-center gap-2">
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
                            {refreshing ? 'Refreshing...' : 'Refresh'}
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
                </div>

                <AlbumSearch
                    listId={list.id}
                    onAlbumAdded={handleAlbumAdded}
                />

                {!hasAlbums ? (
                    <p className="mt-8 text-center text-muted-foreground">
                        No albums yet. Search for albums above to add them to this list.
                    </p>
                ) : (
                    <div className="mt-6" ref={albumsContainerRef}>
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
                                <AlbumCard key={album.id} album={album} onMove={handleMoveAlbum} onRemove={handleRemoveAlbum} />
                            ))}
                        </InfiniteScroll>
                    </div>
                )}
            </div>

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
