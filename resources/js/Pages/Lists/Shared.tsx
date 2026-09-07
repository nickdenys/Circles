/**
 * Public, read-only view of a shared list. Reached through /shared/{hash} by
 * anyone holding the link, with or without an account. Everything that changes
 * data lives on Lists/Show and is deliberately absent here.
 */
import { Head, InfiniteScroll } from '@inertiajs/react';
import { CheckCircle, Clock, Loader2, Music, Play } from 'lucide-react';
import { ReactNode, useState } from 'react';
import { CoverMosaic, MiniCover } from '@/components/kit/CoverArt';
import { Label } from '@/components/kit/Label';
import { Score } from '@/components/kit/Score';
import { StatBlock } from '@/components/kit/StatBlock';
import { listColor } from '@/components/kit/theme';
import { useIsMobile } from '@/hooks/use-is-mobile';
import PublicLayout from '@/Layouts/PublicLayout';

type ListType = 'system' | 'custom' | 'reviewed';

interface SharedList {
    id: number;
    title: string;
    description: string | null;
    type: ListType;
    albumsCount: number;
    totalTracks: number;
    totalRuntimeMs: number;
}

interface SharedAlbum {
    id: number;
    title: string;
    artists: string;
    coverUrl: string | null;
    runtimeMs: number;
    totalTracks: number;
    releaseDate: string;
    spotifyUri: string;
    note: string | null;
    rating: number | null;
}

interface SharedProps {
    list: SharedList;
    albums: { data: SharedAlbum[]; next_page_url: string | null };
    [key: string]: unknown;
}

function formatRuntime(ms: number): string {
    const totalMinutes = Math.round(ms / 60000);
    if (totalMinutes >= 60) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return `${hours}h ${String(minutes).padStart(2, '0')}m`;
    }
    return `${totalMinutes} min`;
}

function formatRuntimeShort(ms: number): string {
    const totalMinutes = Math.round(ms / 60000);
    if (totalMinutes >= 60) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return `${hours}h${String(minutes).padStart(2, '0')}`;
    }
    return `${totalMinutes}m`;
}

function formatRuntimeStat(ms: number): { value: string; unit: string } | null {
    if (ms <= 0) {
        return null;
    }
    const minutes = ms / 60000;
    if (minutes <= 120) {
        return { value: String(Math.round(minutes)), unit: 'min' };
    }
    const hours = minutes / 60;
    if (hours > 20) {
        return { value: String(Math.round(hours)), unit: 'hours' };
    }
    const rounded = Math.round(hours * 10) / 10;
    return { value: Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(1), unit: 'hours' };
}

function AlbumCover({ album, size }: { album: SharedAlbum; size: number }) {
    if (album.coverUrl) {
        return <MiniCover src={album.coverUrl} alt={album.title} size={size} radius={0} />;
    }
    return (
        <div
            style={{
                width: size,
                height: size,
                background: 'var(--surface-2)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'var(--fg3)',
            }}
        >
            <Music size={Math.round(size * 0.36)} strokeWidth={2} />
        </div>
    );
}

function PlayLink({ album }: { album: SharedAlbum }) {
    const [hover, setHover] = useState(false);

    return (
        <a
            href={album.spotifyUri}
            aria-label={`Play ${album.title}`}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                width: 30,
                height: 30,
                borderRadius: 8,
                flex: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                border: '1px solid ' + (hover ? 'var(--line-ink)' : 'var(--line-strong)'),
                background: hover ? 'var(--surface-3)' : 'transparent',
                color: hover ? 'var(--fg1)' : 'var(--fg2)',
                transition: 'all var(--dur-fast) var(--ease-out)',
                textDecoration: 'none',
            }}
        >
            <Play size={14} strokeWidth={2} style={{ marginLeft: 1 }} />
        </a>
    );
}

function AlbumNote({ note, indent }: { note: string; indent: number }) {
    return (
        <div style={{ padding: `0 16px 13px ${indent}px` }}>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 7, maxWidth: 620 }}>
                <Label accent>Note</Label>
                <p
                    className="album-review"
                    style={{
                        margin: 0,
                        fontSize: 13.5,
                        lineHeight: 1.55,
                        color: 'var(--fg2)',
                        whiteSpace: 'pre-wrap',
                    }}
                >
                    {note}
                </p>
            </div>
        </div>
    );
}

function SharedAlbumRow({
    album,
    index,
    metric,
}: {
    album: SharedAlbum;
    index: number;
    metric: 'runtime' | 'rating';
}) {
    const [hover, setHover] = useState(false);
    const isMobile = useIsMobile();
    const releaseYear = album.releaseDate ? album.releaseDate.slice(0, 4) : '';
    const hasNote = !!album.note && album.note.trim().length > 0;

    if (isMobile) {
        return (
            <div data-album-db-id={album.id} style={{ borderBottom: '1px solid var(--line)' }}>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 12,
                        padding: '10px 2px',
                        minHeight: 72,
                    }}
                >
                    <Label style={{ fontSize: 10, width: 18, flex: 'none' }}>
                        {String(index + 1).padStart(2, '0')}
                    </Label>
                    <div
                        style={{
                            width: 54,
                            height: 54,
                            flex: 'none',
                            borderRadius: 8,
                            overflow: 'hidden',
                            border: '1px solid var(--line)',
                        }}
                    >
                        <AlbumCover album={album} size={54} />
                    </div>
                    <div style={{ flex: 1, minWidth: 0 }}>
                        <div
                            style={{
                                fontSize: 15.5,
                                fontWeight: 600,
                                letterSpacing: '-0.01em',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                            }}
                        >
                            {album.title}
                        </div>
                        <div
                            style={{
                                fontSize: 13,
                                color: 'var(--fg2)',
                                marginTop: 1,
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                            }}
                        >
                            {album.artists}
                        </div>
                        <Label style={{ fontSize: 10, display: 'block', marginTop: 3 }}>
                            {releaseYear && `${releaseYear} · `}
                            {album.totalTracks} TRK
                            {metric === 'runtime' &&
                                ` · ${album.runtimeMs > 0 ? formatRuntimeShort(album.runtimeMs) : '—'}`}
                        </Label>
                    </div>
                    {metric === 'rating' && (
                        <div style={{ flex: 'none' }}>
                            <Score value={album.rating} />
                        </div>
                    )}
                    <div style={{ flex: 'none' }}>
                        <PlayLink album={album} />
                    </div>
                </div>

                {hasNote && <AlbumNote note={album.note as string} indent={84} />}
            </div>
        );
    }

    return (
        <div
            data-album-db-id={album.id}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                borderRadius: 10,
                background: hover ? 'var(--surface-3)' : 'transparent',
                transition: 'background var(--dur-fast)',
            }}
        >
            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: '26px 76px 1fr 40px 200px 50px',
                    alignItems: 'center',
                    gap: 14,
                    padding: '10px 12px',
                }}
            >
                <Label style={{ textAlign: 'right' }}>{String(index + 1).padStart(2, '0')}</Label>
                <div
                    style={{
                        width: 76,
                        height: 76,
                        borderRadius: 9,
                        overflow: 'hidden',
                        border: '1px solid var(--line)',
                        boxShadow: hover ? 'var(--shadow-sm)' : 'none',
                        transition: 'box-shadow var(--dur-fast)',
                    }}
                >
                    <AlbumCover album={album} size={76} />
                </div>
                <div style={{ minWidth: 0 }}>
                    <div
                        style={{
                            fontSize: 17,
                            fontWeight: 600,
                            letterSpacing: '-0.01em',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                        }}
                    >
                        {album.title}
                    </div>
                    <div
                        style={{
                            fontSize: 13.5,
                            color: 'var(--fg2)',
                            marginTop: 1,
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                        }}
                    >
                        {album.artists}
                    </div>
                </div>
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'flex-end',
                        opacity: hover ? 1 : 0,
                        pointerEvents: hover ? 'auto' : 'none',
                        transition: 'opacity var(--dur-fast)',
                    }}
                >
                    <PlayLink album={album} />
                </div>
                <Label
                    className="meta-col"
                    style={{
                        textAlign: 'right',
                        whiteSpace: 'nowrap',
                        fontVariantNumeric: 'tabular-nums',
                    }}
                >
                    <span style={{ display: 'inline-block', minWidth: '4ch', textAlign: 'right' }}>
                        {releaseYear}
                    </span>
                    {' · '}
                    <span style={{ display: 'inline-block', minWidth: '3ch', textAlign: 'right' }}>
                        {album.totalTracks}
                    </span>
                    {' TRK'}
                </Label>
                {metric === 'rating' ? (
                    <span style={{ textAlign: 'right' }}>
                        <Score value={album.rating} />
                    </span>
                ) : (
                    <Label style={{ textAlign: 'right', whiteSpace: 'nowrap', color: 'var(--fg2)' }}>
                        {album.runtimeMs > 0 ? formatRuntime(album.runtimeMs) : '—'}
                    </Label>
                )}
            </div>

            {hasNote && <AlbumNote note={album.note as string} indent={176} />}
        </div>
    );
}

export default function Shared({ list, albums }: SharedProps) {
    const isMobile = useIsMobile();
    const metric: 'runtime' | 'rating' = list.type === 'reviewed' ? 'rating' : 'runtime';
    const runtimeStat = formatRuntimeStat(list.totalRuntimeMs);
    const hasAlbums = albums.data.length > 0;
    const TypeIcon = list.type === 'reviewed' ? CheckCircle : list.type === 'system' ? Clock : null;

    return (
        <>
            <Head title={`${list.title} — shared list`} />

            <div
                style={{
                    maxWidth: 1140,
                    margin: '0 auto',
                    padding: isMobile
                        ? '0 16px calc(64px + env(safe-area-inset-bottom))'
                        : '0 40px 96px',
                }}
            >
                <div
                    style={
                        isMobile
                            ? {
                                  display: 'flex',
                                  flexDirection: 'column-reverse',
                                  alignItems: 'flex-start',
                                  gap: 20,
                                  padding: '20px 0 18px',
                              }
                            : {
                                  display: 'flex',
                                  justifyContent: 'space-between',
                                  alignItems: 'flex-start',
                                  gap: 48,
                                  padding: '26px 0 22px',
                                  flexWrap: 'wrap',
                              }
                    }
                >
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: isMobile ? 12 : 14,
                            flex: 1,
                            minWidth: isMobile ? 0 : 320,
                            maxWidth: 620,
                            width: isMobile ? '100%' : undefined,
                        }}
                    >
                        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                            {TypeIcon ? (
                                <TypeIcon size={18} strokeWidth={2} style={{ color: 'var(--accent)' }} />
                            ) : (
                                <span
                                    style={{
                                        width: 13,
                                        height: 13,
                                        borderRadius: '50%',
                                        background: listColor(list.id),
                                        boxShadow: `0 0 0 4px ${listColor(list.id)}22`,
                                    }}
                                />
                            )}
                            <Label ink>SHARED LIST</Label>
                        </div>
                        <h1
                            style={{
                                fontFamily: 'var(--font-display)',
                                fontWeight: 800,
                                fontSize: isMobile ? 'clamp(32px, 10vw, 42px)' : 54,
                                lineHeight: isMobile ? 1.02 : 0.98,
                                letterSpacing: '-0.03em',
                                margin: 0,
                                color: 'var(--fg1)',
                                textWrap: 'balance',
                            }}
                        >
                            {list.title}
                        </h1>
                        {list.description && (
                            <p
                                style={{
                                    fontSize: isMobile ? 15.5 : 17,
                                    margin: 0,
                                    maxWidth: 540,
                                    color: 'var(--fg2)',
                                    lineHeight: 1.55,
                                    whiteSpace: 'pre-line',
                                }}
                            >
                                {list.description}
                            </p>
                        )}
                    </div>

                    <CoverMosaic
                        covers={albums.data.slice(0, 4).map((album) => album.coverUrl)}
                        size={isMobile ? 132 : 208}
                        gap={3}
                        radius={14}
                        inner={4}
                        hard
                    />
                </div>

                <div
                    style={{
                        display: 'flex',
                        gap: isMobile ? 32 : 48,
                        alignItems: 'center',
                        padding: isMobile ? '14px 0' : '16px 0',
                        borderTop: '1px solid var(--line)',
                        borderBottom: '1px solid var(--line)',
                        flexWrap: 'wrap',
                    }}
                >
                    <StatBlock value={list.albumsCount} caption="Albums filed" size={isMobile ? 22 : 26} />
                    {list.totalTracks > 0 && (
                        <StatBlock value={list.totalTracks} caption="Total tracks" size={isMobile ? 22 : 26} />
                    )}
                    {runtimeStat && (
                        <StatBlock
                            value={runtimeStat.value}
                            unit={runtimeStat.unit}
                            caption="Total runtime"
                            size={isMobile ? 22 : 26}
                        />
                    )}
                </div>

                {!hasAlbums ? (
                    <div
                        style={{
                            textAlign: 'center',
                            padding: isMobile ? '56px 0' : '96px 0',
                            color: 'var(--fg3)',
                        }}
                    >
                        <Music
                            size={42}
                            strokeWidth={2}
                            style={{ color: 'var(--line-strong)', margin: '0 auto 16px' }}
                        />
                        <div
                            style={{
                                fontFamily: 'var(--font-display)',
                                fontWeight: 700,
                                fontSize: isMobile ? 21 : 24,
                                color: 'var(--fg1)',
                                marginBottom: 6,
                            }}
                        >
                            Nothing filed here yet.
                        </div>
                        <Label>This list is empty for now.</Label>
                    </div>
                ) : (
                    <div style={{ marginTop: isMobile ? 16 : 22 }}>
                        {!isMobile && (
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: '26px 76px 1fr 40px 200px 50px',
                                    alignItems: 'center',
                                    gap: 14,
                                    padding: '0 12px 10px',
                                    borderBottom: '1px solid var(--line)',
                                    marginBottom: 6,
                                }}
                            >
                                <Label style={{ textAlign: 'right' }}>#</Label>
                                <span />
                                <Label>Album</Label>
                                <span />
                                <Label className="meta-col" style={{ textAlign: 'right' }}>
                                    Year · Tracks
                                </Label>
                                <Label style={{ textAlign: 'right' }}>
                                    {metric === 'rating' ? 'Rating' : 'Runtime'}
                                </Label>
                            </div>
                        )}
                        <InfiniteScroll
                            data="albums"
                            style={{ display: 'flex', flexDirection: 'column', gap: isMobile ? 0 : 2 }}
                            loading={() => (
                                <div
                                    id="album-scroll-sentinel"
                                    style={{ display: 'flex', justifyContent: 'center', padding: 16 }}
                                >
                                    <Loader2
                                        size={20}
                                        strokeWidth={2}
                                        className="animate-spin"
                                        style={{ color: 'var(--fg3)' }}
                                    />
                                </div>
                            )}
                        >
                            {albums.data.map((album, index) => (
                                <SharedAlbumRow
                                    key={album.id}
                                    album={album}
                                    index={index}
                                    metric={metric}
                                />
                            ))}
                        </InfiniteScroll>
                    </div>
                )}
            </div>
        </>
    );
}

Shared.layout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
