import axios from 'axios';
import {
    Check,
    CornerDownLeft,
    Disc3,
    Loader2,
    LucideIcon,
    Plus,
    Search,
    X,
    Zap,
} from 'lucide-react';
import {
    KeyboardEvent,
    RefObject,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/circles/Button';
import { CardModal } from '@/components/circles/CardModal';
import { MiniCover } from '@/components/circles/CoverArt';
import { IconButton } from '@/components/circles/IconButton';
import { Label } from '@/components/circles/Label';

const ROW_KEYFRAMES = '@keyframes rapidAddRowIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:none}}';

interface SearchResult {
    id: string;
    name: string;
    artists: string;
    image: string | null;
    uri: string;
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
    genres: string[];
    note: string | null;
}

interface AddAlbumDialogProps {
    listId: number;
    listName?: string;
    existingSpotifyIds?: string[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAlbumAdded: (album: AddedAlbum) => void;
    onAlbumRemoved?: (albumId: number) => void;
}

export default function AddAlbumDialog({
    listId,
    listName,
    existingSpotifyIds = [],
    open,
    onOpenChange,
    onAlbumAdded,
    onAlbumRemoved,
}: AddAlbumDialogProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [searching, setSearching] = useState(false);
    const [added, setAdded] = useState<AddedAlbum[]>([]);
    const [addingId, setAddingId] = useState<string | null>(null);
    const [removingId, setRemovingId] = useState<number | null>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const existingIds = useMemo(() => new Set(existingSpotifyIds), [existingSpotifyIds]);
    const addedIds = useMemo(() => new Set(added.map((album) => album.spotifyId)), [added]);
    const visibleResults = results.filter((result) => !existingIds.has(result.id) && !addedIds.has(result.id));

    useEffect(() => {
        if (!open) {
            return;
        }

        const frame = requestAnimationFrame(() => inputRef.current?.focus());

        return () => cancelAnimationFrame(frame);
    }, [open]);

    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    function search(term: string): void {
        setSearching(true);

        axios
            .get('/spotify/search/albums', { params: { q: term } })
            .then((response) => {
                setResults(response.data.data);
            })
            .catch((error) => {
                if (
                    axios.isAxiosError(error) &&
                    error.response?.status === 401 &&
                    error.response.data?.reconnect_url
                ) {
                    window.location.href = error.response.data.reconnect_url;

                    return;
                }

                setResults([]);
                toast.error('Could not search Spotify. Please try again.');
            })
            .finally(() => {
                setSearching(false);
            });
    }

    function handleQueryChange(value: string): void {
        setQuery(value);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        if (value.trim().length < 2) {
            setResults([]);
            setSearching(false);

            return;
        }

        debounceRef.current = setTimeout(() => search(value.trim()), 300);
    }

    function addAlbum(result?: SearchResult): void {
        if (!result || addingId) {
            return;
        }

        setAddingId(result.id);

        axios
            .post(`/lists/${listId}/albums`, { spotify_id: result.id })
            .then((response) => {
                const data = response.data.data;
                const album: AddedAlbum = {
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
                    genres: data.genres,
                    note: data.note ?? null,
                };

                onAlbumAdded(album);
                setAdded((previous) => [album, ...previous]);

                if (debounceRef.current) {
                    clearTimeout(debounceRef.current);
                }

                setQuery('');
                setResults([]);
                inputRef.current?.focus();
            })
            .catch((error) => {
                if (
                    axios.isAxiosError(error) &&
                    error.response?.status === 401 &&
                    error.response.data?.reconnect_url
                ) {
                    window.location.href = error.response.data.reconnect_url;

                    return;
                }

                if (axios.isAxiosError(error) && error.response?.status === 409) {
                    toast.error(`"${result.name}" is already in this list.`);

                    return;
                }

                toast.error('Could not add that album. Please try again.');
            })
            .finally(() => {
                setAddingId(null);
            });
    }

    function removeAlbum(album: AddedAlbum): void {
        setRemovingId(album.id);

        axios
            .delete(`/lists/${listId}/albums/${album.id}`)
            .then(() => {
                setAdded((previous) => previous.filter((entry) => entry.id !== album.id));
                onAlbumRemoved?.(album.id);
            })
            .catch(() => {
                toast.error('Could not undo that add. Please try again.');
            })
            .finally(() => {
                setRemovingId(null);
            });
    }

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>): void {
        if (event.key === 'Enter') {
            event.preventDefault();
            addAlbum(visibleResults[0]);
        }
    }

    function handleClose(): void {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        setQuery('');
        setResults([]);
        setAdded([]);
        setSearching(false);
        setAddingId(null);
        setRemovingId(null);
        onOpenChange(false);
    }

    if (!open) {
        return null;
    }

    const hasQuery = query.trim().length >= 2;

    return (
        <CardModal
            label="ADD TO LIST"
            ariaLabel="Add albums to list"
            meta={listName}
            width={620}
            contentPad="0"
            onClose={handleClose}
            footer={
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 14,
                    }}
                >
                    <Label style={{ fontSize: 10.5, display: 'inline-flex', gap: 6, alignItems: 'center' }}>
                        <CornerDownLeft size={13} strokeWidth={2} /> Enter adds top result
                    </Label>
                    <Button
                        variant={added.length ? 'primary' : 'secondary'}
                        size="sm"
                        icon={Check}
                        onClick={handleClose}
                    >
                        Done{added.length ? ` · ${added.length} added` : ''}
                    </Button>
                </div>
            }
        >
            <style>{ROW_KEYFRAMES}</style>

            <div
                style={{
                    position: 'sticky',
                    top: 0,
                    zIndex: 2,
                    flex: 'none',
                    padding: '18px 18px 14px',
                    background: 'var(--surface)',
                    borderBottom: hasQuery && visibleResults.length ? '1px solid var(--line)' : 'none',
                }}
            >
                <SearchField
                    inputRef={inputRef}
                    value={query}
                    searching={searching}
                    onChange={handleQueryChange}
                    onKeyDown={handleKeyDown}
                />
            </div>

            {hasQuery && (
                <div
                    id="album-search-results"
                    style={{ padding: '8px 12px', borderBottom: added.length ? '1px solid var(--line)' : 'none' }}
                >
                    {visibleResults.length ? (
                        visibleResults.slice(0, 5).map((result, index) => (
                            <div key={result.id} style={{ position: 'relative' }}>
                                <ResultRow
                                    result={result}
                                    adding={addingId === result.id}
                                    onAdd={() => addAlbum(result)}
                                />
                                {index === 0 && (
                                    <Label
                                        style={{
                                            position: 'absolute',
                                            right: 52,
                                            top: '50%',
                                            transform: 'translateY(-50%)',
                                            fontSize: 9,
                                            pointerEvents: 'none',
                                        }}
                                    >
                                        ↵ enter
                                    </Label>
                                )}
                            </div>
                        ))
                    ) : searching ? (
                        <EmptyHint icon={Loader2} title="Searching Spotify…" />
                    ) : (
                        <EmptyHint icon={Disc3} title={`No albums match "${query.trim()}"`} sub="Try an artist or album name" />
                    )}
                </div>
            )}

            {added.length > 0 && (
                <div id="rapid-add-log" style={{ padding: '12px 12px 16px' }}>
                    <Label style={{ display: 'block', padding: '0 10px 8px' }}>
                        Added this session · {added.length}
                    </Label>
                    <div style={{ display: 'flex', flexDirection: 'column' }}>
                        {added.map((album) => (
                            <div
                                key={album.id}
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: '38px 1fr auto',
                                    alignItems: 'center',
                                    gap: 12,
                                    padding: '8px 10px',
                                    borderRadius: 11,
                                    animation: 'rapidAddRowIn var(--dur-base) var(--ease-out)',
                                }}
                            >
                                <MiniCover
                                    src={album.coverUrl}
                                    alt={album.title}
                                    size={38}
                                    radius={7}
                                    style={{ border: '1px solid var(--line)' }}
                                />
                                <span style={{ minWidth: 0 }}>
                                    <span
                                        style={{
                                            display: 'block',
                                            fontSize: 14.5,
                                            fontWeight: 600,
                                            whiteSpace: 'nowrap',
                                            overflow: 'hidden',
                                            textOverflow: 'ellipsis',
                                        }}
                                    >
                                        {album.title}
                                    </span>
                                    <span style={{ display: 'block', fontSize: 12.5, color: 'var(--fg2)' }}>
                                        {album.artists}
                                    </span>
                                </span>
                                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 6 }}>
                                    {removingId === album.id ? (
                                        <Loader2 size={16} strokeWidth={2} className="animate-spin" style={{ color: 'var(--fg3)' }} />
                                    ) : (
                                        <Check size={16} strokeWidth={2} style={{ color: 'var(--accent)' }} />
                                    )}
                                    <IconButton
                                        icon={X}
                                        label={`Undo adding ${album.title}`}
                                        size={15}
                                        boxSize={28}
                                        disabled={removingId === album.id}
                                        onClick={() => removeAlbum(album)}
                                    />
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {!hasQuery && added.length === 0 && (
                <EmptyHint
                    icon={Zap}
                    title="Type a term, hit enter, repeat"
                    sub="File a whole crate without leaving"
                />
            )}
        </CardModal>
    );
}

interface SearchFieldProps {
    value: string;
    searching: boolean;
    onChange: (value: string) => void;
    onKeyDown: (event: KeyboardEvent<HTMLInputElement>) => void;
    inputRef: RefObject<HTMLInputElement | null>;
}

function SearchField({ value, searching, onChange, onKeyDown, inputRef }: SearchFieldProps) {
    const [focused, setFocused] = useState(false);

    return (
        <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
            <span
                style={{
                    position: 'absolute',
                    left: 16,
                    display: 'inline-flex',
                    color: focused ? 'var(--accent)' : 'var(--fg3)',
                    transition: 'color var(--dur-fast)',
                    pointerEvents: 'none',
                }}
            >
                <Search size={21} strokeWidth={2} />
            </span>
            <input
                id="album-search-input"
                ref={inputRef}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={onKeyDown}
                onFocus={() => setFocused(true)}
                onBlur={() => setFocused(false)}
                placeholder="Search Spotify and hit enter"
                style={{
                    width: '100%',
                    boxSizing: 'border-box',
                    padding: '15px 44px 15px 46px',
                    borderRadius: 12,
                    border: `1.5px solid ${focused ? 'var(--accent)' : 'var(--line-strong)'}`,
                    boxShadow: focused ? '0 0 0 3px var(--accent-weak)' : 'none',
                    background: 'var(--surface-2)',
                    fontFamily: 'var(--font-sans)',
                    fontSize: 17,
                    fontWeight: 500,
                    color: 'var(--fg1)',
                    outline: 'none',
                    transition: 'border-color var(--dur-fast), box-shadow var(--dur-fast)',
                }}
            />
            {searching ? (
                <span style={{ position: 'absolute', right: 14, display: 'inline-flex', color: 'var(--fg3)' }}>
                    <Loader2 size={18} strokeWidth={2} className="animate-spin" />
                </span>
            ) : (
                value && (
                    <button
                        type="button"
                        aria-label="Clear search"
                        onClick={() => {
                            onChange('');
                            inputRef.current?.focus();
                        }}
                        style={{
                            position: 'absolute',
                            right: 8,
                            width: 30,
                            height: 30,
                            borderRadius: 8,
                            border: 'none',
                            cursor: 'pointer',
                            background: 'transparent',
                            color: 'var(--fg3)',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                        }}
                    >
                        <X size={16} strokeWidth={2} />
                    </button>
                )
            )}
        </div>
    );
}

interface ResultRowProps {
    result: SearchResult;
    adding: boolean;
    onAdd: () => void;
}

function ResultRow({ result, adding, onAdd }: ResultRowProps) {
    const [hover, setHover] = useState(false);

    return (
        <button
            type="button"
            className="album-search-result"
            onClick={onAdd}
            disabled={adding}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'grid',
                gridTemplateColumns: '46px 1fr auto',
                alignItems: 'center',
                gap: 14,
                width: '100%',
                textAlign: 'left',
                padding: '9px 12px',
                borderRadius: 11,
                border: '1.5px solid transparent',
                background: hover ? 'var(--surface-3)' : 'transparent',
                cursor: adding ? 'default' : 'pointer',
                font: 'inherit',
                transition: 'background var(--dur-fast)',
            }}
        >
            <MiniCover
                src={result.image}
                alt={result.name}
                size={46}
                radius={7}
                style={{ border: '1px solid var(--line)', boxShadow: 'var(--shadow-sm)' }}
            />
            <span style={{ minWidth: 0 }}>
                <span
                    style={{
                        display: 'block',
                        fontSize: 15.5,
                        fontWeight: 600,
                        color: 'var(--fg1)',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                    }}
                >
                    {result.name}
                </span>
                <span
                    style={{
                        display: 'block',
                        fontSize: 13,
                        color: 'var(--fg2)',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                    }}
                >
                    {result.artists}
                </span>
            </span>
            <span
                style={{
                    width: 30,
                    height: 30,
                    borderRadius: 8,
                    flex: 'none',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: hover ? 'var(--surface)' : 'transparent',
                    border: '1.5px solid var(--line-strong)',
                    color: 'var(--fg2)',
                    transition: 'all var(--dur-fast) var(--ease-spring)',
                }}
            >
                {adding ? <Loader2 size={16} strokeWidth={2} className="animate-spin" /> : <Plus size={17} strokeWidth={2} />}
            </span>
        </button>
    );
}

interface EmptyHintProps {
    icon: LucideIcon;
    title: string;
    sub?: string;
}

function EmptyHint({ icon: Icon, title, sub }: EmptyHintProps) {
    return (
        <div
            style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 12,
                padding: '44px 24px',
                textAlign: 'center',
            }}
        >
            <span
                style={{
                    width: 52,
                    height: 52,
                    borderRadius: 14,
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: 'var(--surface-2)',
                    border: '1px solid var(--line)',
                    color: 'var(--fg3)',
                }}
            >
                <Icon size={24} strokeWidth={2} className={Icon === Loader2 ? 'animate-spin' : undefined} />
            </span>
            <div style={{ fontSize: 15, fontWeight: 600, color: 'var(--fg2)' }}>{title}</div>
            {sub && <Label style={{ fontSize: 10.5 }}>{sub}</Label>}
        </div>
    );
}
