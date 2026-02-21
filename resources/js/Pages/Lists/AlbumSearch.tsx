import axios from 'axios';
import { Search, Loader2, Music } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';

interface SearchResult {
    id: string;
    name: string;
    artists: string;
    image: string | null;
    uri: string;
}

interface AlbumSearchProps {
    listId: number;
    onAlbumAdded: (album: AddedAlbum) => void;
}

export interface AddedAlbum {
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

export default function AlbumSearch({ listId, onAlbumAdded }: AlbumSearchProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [searching, setSearching] = useState(false);
    const [adding, setAdding] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const search = useCallback(async (q: string) => {
        if (q.length < 2) {
            setResults([]);
            setIsOpen(false);
            return;
        }

        setSearching(true);
        setError(null);

        try {
            const response = await axios.get('/spotify/search/albums', {
                params: { q },
            });
            setResults(response.data.data);
            setIsOpen(true);
        } catch {
            setResults([]);
            setError('Failed to search. Please try again.');
            setIsOpen(true);
        } finally {
            setSearching(false);
        }
    }, []);

    function handleInputChange(value: string) {
        setQuery(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        if (value.length < 2) {
            setResults([]);
            setIsOpen(false);
            return;
        }

        debounceRef.current = setTimeout(() => {
            search(value);
        }, 300);
    }

    async function handleAdd(result: SearchResult) {
        setAdding(result.id);
        setError(null);

        try {
            const response = await axios.post(`/lists/${listId}/albums`, {
                spotify_id: result.id,
            });

            const data = response.data.data;
            onAlbumAdded({
                id: data.id,
                spotifyId: data.spotify_id,
                title: data.title,
                artists: data.artists,
                coverUrl: data.cover_url,
                runtimeMs: data.runtime_ms,
                albumType: data.album_type,
                totalTracks: data.total_tracks,
                releaseDate: data.release_date,
                spotifyUri: data.spotify_uri,
            });

            setQuery('');
            setResults([]);
            setIsOpen(false);
        } catch (err: unknown) {
            if (axios.isAxiosError(err) && err.response?.status === 409) {
                setError('Album already in this list.');
            } else {
                setError('Failed to add album. Please try again.');
            }
        } finally {
            setAdding(null);
        }
    }

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setIsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function handleKeyDown(e: React.KeyboardEvent) {
        if (e.key === 'Escape') {
            setIsOpen(false);
        }
    }

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    return (
        <div ref={containerRef} className="relative mt-6" onKeyDown={handleKeyDown}>
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                id="album-search-input"
                placeholder="Search for albums on Spotify..."
                className="pl-9"
                value={query}
                onChange={(e) => handleInputChange(e.target.value)}
            />
            {searching && (
                <Loader2 className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
            )}

            {isOpen && (
                <div
                    id="album-search-dropdown"
                    className="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-lg"
                >
                    <div id="album-search-results">
                        {error && (
                            <p className="px-4 py-3 text-sm text-destructive">{error}</p>
                        )}
                        {!error && results.length === 0 && !searching && (
                            <p className="px-4 py-3 text-sm text-muted-foreground">
                                No results found.
                            </p>
                        )}
                        {results.map((result) => (
                            <button
                                key={result.id}
                                type="button"
                                className="album-search-result flex w-full items-center gap-3 px-4 py-2 text-left transition hover:bg-accent disabled:opacity-50"
                                onClick={() => handleAdd(result)}
                                disabled={adding !== null}
                            >
                                {result.image ? (
                                    <img
                                        src={result.image}
                                        alt={result.name}
                                        className="h-10 w-10 shrink-0 rounded object-cover"
                                    />
                                ) : (
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                        <Music className="h-4 w-4 text-muted-foreground" />
                                    </div>
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {result.name}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">
                                        {result.artists}
                                    </p>
                                </div>
                                {adding === result.id && (
                                    <Loader2 className="h-4 w-4 shrink-0 animate-spin text-muted-foreground" />
                                )}
                            </button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
