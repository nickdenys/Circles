import { Head, router } from '@inertiajs/react';
import {
    GripVertical,
    Loader2,
    Music,
    RefreshCw,
    Search,
    ArrowRightLeft,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

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

export default function Show({ list, albums }: ShowProps) {
    const [refreshing, setRefreshing] = useState(false);

    function handleRefresh() {
        router.post(route('lists.refresh', list.id), {}, {
            onStart: () => setRefreshing(true),
            onFinish: () => setRefreshing(false),
        });
    }

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
                            {list.albumsCount}{' '}
                            {list.albumsCount === 1 ? 'album' : 'albums'}
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
                                >
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    id="delete-list-button"
                                >
                                    Delete
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                <div className="relative mt-6">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="album-search-input"
                        placeholder="Search for albums on Spotify..."
                        className="pl-9"
                    />
                    <div id="album-search-dropdown" className="hidden">
                        <div id="album-search-results" />
                    </div>
                </div>

                {albums.data.length === 0 ? (
                    <p className="mt-8 text-center text-muted-foreground">
                        No albums yet. Search for albums above to add them to this list.
                    </p>
                ) : (
                    <div className="mt-6 space-y-3">
                        {albums.data.map((album) => (
                            <Card
                                key={album.id}
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
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
