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
import {
    arrayMove,
    rectSortingStrategy,
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    ArrowDown,
    ArrowDownUp,
    ArrowRightLeft,
    ArrowUp,
    Calendar,
    Check,
    CheckCircle,
    Clock,
    ExternalLink,
    GripVertical,
    LayoutGrid,
    List as ListIcon,
    ListMusic,
    Loader2,
    MoreHorizontal,
    Music,
    Pencil,
    Play,
    Plus,
    RefreshCw,
    Star,
    StickyNote,
    Trash2,
} from 'lucide-react';
import { CSSProperties, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/kit/Button';
import { Chip } from '@/components/kit/Chip';
import { CoverMosaic, MiniCover } from '@/components/kit/CoverArt';
import { IconButton } from '@/components/kit/IconButton';
import { Label } from '@/components/kit/Label';
import { Score } from '@/components/kit/Score';
import { StatBlock } from '@/components/kit/StatBlock';
import { listColor } from '@/components/kit/theme';
import { TopBar } from '@/components/kit/TopBar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useIsMobile } from '@/hooks/use-is-mobile';
import AddAlbumDialog, { type AddedAlbum } from './AddAlbumDialog';
import DeleteListDialog from './DeleteListDialog';
import EditListDialog from './EditListDialog';
import MoveAlbumDialog, { MoveTarget } from './MoveAlbumDialog';
import NoteDialog, { type NoteDialogAlbum } from './NoteDialog';
import RatingDialog, { type RatingDialogAlbum } from './RatingDialog';
import RemoveAlbumDialog from './RemoveAlbumDialog';

type ListType = 'system' | 'custom' | 'reviewed';
type ListMode = 'default' | 'listening';

interface AlbumListDetail {
    id: number;
    title: string;
    description: string | null;
    type: ListType;
    mode: ListMode;
    albumsCount: number;
    totalTracks: number;
    totalRuntimeMs: number;
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

type ViewMode = 'grid' | 'table';

const HIGHLIGHT_DURATION_MS = 2600;

/**
 * The green highlight spends its last 30% fading out, so a leaving album mirrors it and
 * spends that same stretch fading in. Both ends use ease-out rather than a literal
 * `direction: reverse`, which would ease *into* the colour and read as a delay.
 */
const LEAVE_COLOUR_MS = HIGHLIGHT_DURATION_MS * 0.3;

const LEAVE_ANIMATION_MS = 520;

const LEAVE_DURATION_MS = LEAVE_COLOUR_MS + LEAVE_ANIMATION_MS;

const HIGHLIGHT_KEYFRAMES = [
    '@keyframes albumHighlight{0%{background:var(--highlight-weak);box-shadow:inset 0 0 0 1.5px var(--highlight-strong)}70%{background:var(--highlight-weak);box-shadow:inset 0 0 0 1.5px var(--highlight-strong)}100%{background:transparent;box-shadow:inset 0 0 0 1.5px transparent}}',
    '@keyframes albumHighlightIn{0%{background:transparent;box-shadow:inset 0 0 0 1.5px transparent}100%{background:var(--highlight-weak);box-shadow:inset 0 0 0 1.5px var(--highlight-strong)}}',
    '@keyframes albumLeaveRow{0%{opacity:1;grid-template-rows:1fr}50%{opacity:0;grid-template-rows:1fr}100%{opacity:0;grid-template-rows:0fr}}',
    '@keyframes albumLeaveCard{0%{opacity:1;transform:scale(1)}50%{opacity:0;transform:scale(0.94)}100%{opacity:0;transform:scale(0.94)}}',
].join('');

/**
 * An added album fades its highlight out over the tail of a long run; a leaving album
 * fades the mirror image in, over just that tail's worth of time.
 */
const HIGHLIGHT_KINDS = {
    added: {
        weak: 'var(--accent-weak)',
        strong: 'var(--accent)',
        keyframes: 'albumHighlight',
        colourMs: HIGHLIGHT_DURATION_MS,
        holdMs: HIGHLIGHT_DURATION_MS,
    },
    removed: {
        weak: 'var(--critical-weak)',
        strong: 'var(--critical)',
        keyframes: 'albumHighlightIn',
        colourMs: LEAVE_COLOUR_MS,
        holdMs: LEAVE_DURATION_MS,
    },
    moved: {
        weak: 'var(--info-weak)',
        strong: 'var(--info)',
        keyframes: 'albumHighlightIn',
        colourMs: LEAVE_COLOUR_MS,
        holdMs: LEAVE_DURATION_MS,
    },
} as const;

type HighlightKind = keyof typeof HIGHLIGHT_KINDS;

/**
 * `exitDelayMs` is only set once the server has confirmed the album is gone; until then
 * the album is coloured but stays put, in case the request turns out to have failed.
 */
type AlbumHighlight = { kind: HighlightKind; exitDelayMs?: number };

function isLeaving(highlight?: AlbumHighlight): boolean {
    return highlight?.kind === 'removed' || highlight?.kind === 'moved';
}

/**
 * @return {React.CSSProperties} Custom properties feeding the shared keyframes, plus the animation itself.
 */
function highlightStyle(highlight?: AlbumHighlight): React.CSSProperties {
    if (!highlight) {
        return {};
    }

    const { weak, strong, keyframes, colourMs } = HIGHLIGHT_KINDS[highlight.kind];

    return {
        ['--highlight-weak' as string]: weak,
        ['--highlight-strong' as string]: strong,
        animation: `${keyframes} ${colourMs}ms var(--ease-out) forwards`,
    };
}

/**
 * Fade the album out once its colour has arrived, then fold the space it occupied shut.
 * Table rows collapse their height so the rows underneath slide up; grid cards cannot
 * reclaim their slot that way, so they shrink away instead.
 *
 * @return {React.CSSProperties} Styles for the wrapper that owns the album's place in the layout.
 */
function leaveStyle(highlight: AlbumHighlight | undefined, layout: 'row' | 'card'): React.CSSProperties {
    if (!isLeaving(highlight) || highlight?.exitDelayMs === undefined) {
        return {};
    }

    const keyframes = layout === 'row' ? 'albumLeaveRow' : 'albumLeaveCard';

    return {
        display: layout === 'row' ? 'grid' : undefined,
        gridTemplateRows: layout === 'row' ? '1fr' : undefined,
        overflow: layout === 'row' ? 'hidden' : undefined,
        pointerEvents: 'none',
        animation: `${keyframes} ${LEAVE_ANIMATION_MS}ms var(--ease-out) ${highlight.exitDelayMs}ms forwards`,
    };
}

/**
 * @return {AlbumItem[]} The list with the album put back at `index`, or untouched when it is already there.
 */
function insertLeavingAlbum(albums: AlbumItem[], album: AlbumItem, index: number): AlbumItem[] {
    if (albums.some((a) => a.id === album.id)) {
        return albums;
    }

    const next = [...albums];
    next.splice(Math.min(index, next.length), 0, album);

    return next;
}

const SORT_OPTIONS = [
    { value: 'manual', label: 'Manual' },
    { value: 'added', label: 'Date added' },
    { value: 'release_date', label: 'Year' },
    { value: 'title', label: 'Title' },
    { value: 'artist', label: 'Artist' },
] as const;

const DEFAULT_SORT_DIRECTION: Record<string, 'asc' | 'desc'> = {
    title: 'asc',
    artist: 'asc',
    release_date: 'desc',
    added: 'desc',
};

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

function trackCountLabel(count: number): string {
    return count === 1 ? 'track' : 'tracks';
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

function HeroBadge({ list }: { list: AlbumListDetail }) {
    const isSystem = list.type !== 'custom';
    const Icon = list.type === 'reviewed' ? CheckCircle : list.type === 'system' ? Clock : null;
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            {Icon ? (
                <Icon size={18} strokeWidth={2} style={{ color: 'var(--accent)' }} />
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
            <Label ink>{isSystem ? 'SYSTEM LIST' : 'USER LIST'}</Label>
        </div>
    );
}

function SortControl({ listId, sort, direction }: { listId: number; sort: string; direction: 'asc' | 'desc' }) {
    const [open, setOpen] = useState(false);
    const activeLabel = SORT_OPTIONS.find((option) => option.value === sort)?.label ?? 'Manual';

    function applySort(value: string) {
        setOpen(false);
        const nextDirection =
            value === 'manual'
                ? 'asc'
                : value === sort
                    ? direction === 'asc'
                        ? 'desc'
                        : 'asc'
                    : DEFAULT_SORT_DIRECTION[value];

        router.patch(`/lists/${listId}/sort`, { sort: value, direction: nextDirection }, {
            preserveScroll: false,
        });
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                render={<Button variant="secondary" size="sm" icon={ArrowDownUp} id="sort-control" />}
            >
                Sort: {activeLabel}
                {sort !== 'manual' &&
                    (direction === 'asc' ? (
                        <ArrowUp size={14} strokeWidth={2} style={{ marginLeft: 4 }} />
                    ) : (
                        <ArrowDown size={14} strokeWidth={2} style={{ marginLeft: 4 }} />
                    ))}
            </PopoverTrigger>
            <PopoverContent
                align="end"
                style={{
                    width: 200,
                    padding: 6,
                    background: 'var(--surface)',
                    borderColor: 'var(--line-strong)',
                    borderRadius: 12,
                    boxShadow: 'var(--shadow-md)',
                }}
            >
                {SORT_OPTIONS.map((option) => {
                    const isActive = option.value === sort;
                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => applySort(option.value)}
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                width: '100%',
                                padding: '8px 10px',
                                borderRadius: 8,
                                border: 'none',
                                background: isActive ? 'var(--surface-3)' : 'transparent',
                                cursor: 'pointer',
                                fontFamily: 'var(--font-sans)',
                                fontSize: 14,
                                fontWeight: isActive ? 600 : 500,
                                color: 'var(--fg1)',
                                textAlign: 'left',
                            }}
                            onMouseEnter={(e) => {
                                if (!isActive) e.currentTarget.style.background = 'var(--surface-2)';
                            }}
                            onMouseLeave={(e) => {
                                if (!isActive) e.currentTarget.style.background = 'transparent';
                            }}
                        >
                            {option.label}
                            {isActive && option.value === 'manual' && (
                                <Check size={14} strokeWidth={2} style={{ color: 'var(--accent)' }} />
                            )}
                            {isActive &&
                                option.value !== 'manual' &&
                                (direction === 'asc' ? (
                                    <ArrowUp size={14} strokeWidth={2} style={{ color: 'var(--accent)' }} />
                                ) : (
                                    <ArrowDown size={14} strokeWidth={2} style={{ color: 'var(--accent)' }} />
                                ))}
                        </button>
                    );
                })}
            </PopoverContent>
        </Popover>
    );
}

function AlbumCover({ album, size, radius = 10 }: { album: AlbumItem; size?: number | string; radius?: number }) {
    if (album.coverUrl) {
        return <MiniCover src={album.coverUrl} alt={album.title} size={size ?? '100%'} radius={radius} />;
    }
    return (
        <div
            style={{
                width: size ?? '100%',
                aspectRatio: '1 / 1',
                borderRadius: radius,
                background: 'var(--surface-2)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'var(--fg3)',
            }}
        >
            <Music size={28} strokeWidth={2} />
        </div>
    );
}

function GridAlbumCard({
    album,
    index,
    listType,
    onPlay,
    highlight,
}: {
    album: AlbumItem;
    index: number;
    listType: ListType;
    onPlay: () => void;
    highlight?: AlbumHighlight;
}) {
    const [hover, setHover] = useState(false);
    const isReviewedList = listType === 'reviewed';
    const releaseYear = album.releaseDate ? album.releaseDate.slice(0, 4) : '';

    return (
        <div
            data-highlight={highlight?.kind}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                cursor: 'default',
                display: 'flex',
                flexDirection: 'column',
                gap: 11,
                padding: highlight ? 8 : undefined,
                margin: highlight ? -8 : undefined,
                borderRadius: highlight ? 14 : undefined,
                ...highlightStyle(highlight),
                transition: 'transform var(--dur-fast) var(--ease-out)',
                transform: hover ? 'translateY(-4px)' : 'none',
            }}
        >
            <div
                style={{
                    position: 'relative',
                    borderRadius: 10,
                    overflow: 'hidden',
                    border: '1px solid var(--line)',
                    boxShadow: hover ? 'var(--shadow-md)' : 'var(--shadow-sm)',
                    transition: 'box-shadow var(--dur-fast) var(--ease-out)',
                    aspectRatio: '1 / 1',
                }}
            >
                <AlbumCover album={album} radius={0} />

                <span
                    style={{
                        position: 'absolute',
                        top: 8,
                        left: 8,
                        background: 'rgba(14,12,9,0.62)',
                        color: '#fff',
                        fontFamily: 'var(--font-mono)',
                        fontSize: 10,
                        letterSpacing: '0.1em',
                        padding: '3px 6px',
                        borderRadius: 6,
                        backdropFilter: 'blur(2px)',
                        opacity: hover ? 1 : 0,
                        transition: 'opacity var(--dur-fast) var(--ease-out)',
                        pointerEvents: 'none',
                    }}
                >
                    {String(index + 1).padStart(2, '0')}
                </span>

                {isReviewedList && album.rating != null ? (
                    <span
                        style={{
                            position: 'absolute',
                            top: 8,
                            right: 8,
                            background: 'var(--fg1)',
                            color: 'var(--bg)',
                            fontFamily: 'var(--font-mono)',
                            fontWeight: 700,
                            fontSize: 12,
                            padding: '3px 7px',
                            borderRadius: 7,
                        }}
                    >
                        {album.rating.toFixed(1)}
                    </span>
                ) : album.runtimeMs > 0 ? (
                    <span
                        style={{
                            position: 'absolute',
                            top: 8,
                            right: 8,
                            background: 'var(--fg1)',
                            color: 'var(--bg)',
                            fontFamily: 'var(--font-mono)',
                            fontWeight: 700,
                            fontSize: 11,
                            letterSpacing: '0.02em',
                            padding: '3px 7px',
                            borderRadius: 7,
                            opacity: hover ? 1 : 0,
                            transition: 'opacity var(--dur-fast) var(--ease-out)',
                            pointerEvents: 'none',
                        }}
                    >
                        {formatRuntimeShort(album.runtimeMs)}
                    </span>
                ) : null}

                <span
                    style={{
                        position: 'absolute',
                        inset: 0,
                        background: 'linear-gradient(transparent 45%, rgba(0,0,0,0.5))',
                        opacity: hover ? 1 : 0,
                        transition: 'opacity var(--dur-fast)',
                        display: 'flex',
                        alignItems: 'flex-end',
                        justifyContent: 'flex-end',
                        padding: 11,
                        pointerEvents: 'none',
                    }}
                >
                    <a
                        href={album.spotifyUri}
                        onClick={(event) => {
                            event.stopPropagation();
                            onPlay();
                        }}
                        title="Open in Spotify"
                        style={{
                            width: 38,
                            height: 38,
                            borderRadius: '50%',
                            background: 'var(--accent)',
                            color: 'var(--accent-on)',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            transform: hover ? 'translateY(0)' : 'translateY(6px)',
                            transition: 'transform var(--dur-base) var(--ease-spring)',
                            boxShadow: 'var(--shadow-md)',
                            pointerEvents: 'auto',
                            textDecoration: 'none',
                        }}
                    >
                        <Play size={17} strokeWidth={2} style={{ marginLeft: 1 }} />
                    </a>
                </span>
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
                <div
                    style={{
                        fontSize: 14.5,
                        fontWeight: 600,
                        lineHeight: 1.25,
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        color: 'var(--fg1)',
                    }}
                >
                    {album.title}
                </div>
                <div
                    style={{
                        fontSize: 13,
                        color: 'var(--fg2)',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                    }}
                >
                    {album.artists}
                </div>
                {releaseYear && <Label style={{ fontSize: 10, marginTop: 1 }}>{releaseYear}</Label>}
            </div>
        </div>
    );
}

function GridAlbumItem({
    album,
    index,
    listType,
    onPlay,
    draggable,
    highlight,
}: {
    album: AlbumItem;
    index: number;
    listType: ListType;
    onPlay: () => void;
    draggable: boolean;
    highlight?: AlbumHighlight;
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
                ...leaveStyle(highlight, 'card'),
            }}
            {...(draggable ? { ...attributes, ...listeners } : {})}
        >
            <GridAlbumCard
                album={album}
                index={index}
                listType={listType}
                onPlay={onPlay}
                highlight={highlight}
            />
        </div>
    );
}

function AlbumRowMenu({
    album,
    listType,
    mode,
    onMove,
    onRemove,
    onRate,
    onEditNote,
    boxSize = 32,
}: {
    album: AlbumItem;
    listType: ListType;
    mode: ListMode;
    onMove: (album: MoveTarget) => void;
    onRemove: (album: { id: number; title: string }) => void;
    onRate: () => void;
    onEditNote: () => void;
    boxSize?: number;
}) {
    const [open, setOpen] = useState(false);
    const isReviewedList = listType === 'reviewed';
    const isListening = mode === 'listening';
    const hasNote = !!album.note && album.note.trim().length > 0;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                render={
                    <IconButton
                        icon={MoreHorizontal}
                        label="Album options"
                        size={18}
                        boxSize={boxSize}
                        className="album-actions-button"
                        data-album-id={album.id}
                        data-album-title={album.title}
                    />
                }
            />
            <PopoverContent
                align="end"
                style={{
                    width: 232,
                    padding: 6,
                    background: 'var(--surface)',
                    borderColor: 'var(--line-strong)',
                    borderRadius: 12,
                    boxShadow: 'var(--shadow-md)',
                }}
            >
                <a
                    href={album.spotifyUri}
                    onClick={() => setOpen(false)}
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 12,
                        padding: '9px 12px',
                        borderRadius: 8,
                        fontFamily: 'var(--font-sans)',
                        fontSize: 15,
                        fontWeight: 500,
                        color: 'var(--fg1)',
                        textDecoration: 'none',
                    }}
                    onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--surface-3)')}
                    onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                >
                    <ExternalLink size={17} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                    Open in Spotify
                </a>
                <a
                    href={`https://www.albumoftheyear.org/search/?q=${encodeURIComponent(
                        `${album.artists} ${album.title}`,
                    )}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={() => setOpen(false)}
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 12,
                        padding: '9px 12px',
                        borderRadius: 8,
                        fontFamily: 'var(--font-sans)',
                        fontSize: 15,
                        fontWeight: 500,
                        color: 'var(--fg1)',
                        textDecoration: 'none',
                    }}
                    onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--surface-3)')}
                    onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                >
                    <Star size={17} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                    Open in Album of the Year
                </a>
                {isListening && (
                    <button
                        type="button"
                        onClick={() => {
                            setOpen(false);
                            onRate();
                        }}
                        style={menuItemStyle()}
                        onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--surface-3)')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                    >
                        <Check size={17} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                        Rate / review
                    </button>
                )}
                {isReviewedList && (
                    <button
                        type="button"
                        className="edit-review-button"
                        data-album-id={album.id}
                        onClick={() => {
                            setOpen(false);
                            onRate();
                        }}
                        style={menuItemStyle()}
                        onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--surface-3)')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                    >
                        <Pencil size={17} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                        Edit review
                    </button>
                )}
                {!isReviewedList && (
                    <button
                        type="button"
                        className="edit-note-button"
                        data-album-id={album.id}
                        onClick={() => {
                            setOpen(false);
                            onEditNote();
                        }}
                        style={menuItemStyle()}
                        onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--surface-3)')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                    >
                        <StickyNote size={17} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                        {hasNote ? 'Edit note' : 'Add note'}
                    </button>
                )}
                {!isReviewedList && (
                    <button
                        type="button"
                        className="move-album-button"
                        data-album-id={album.id}
                        data-album-title={album.title}
                        onClick={() =>
                            onMove({
                                id: album.id,
                                title: album.title,
                                artists: album.artists,
                                coverUrl: album.coverUrl,
                                releaseDate: album.releaseDate,
                            })
                        }
                        style={menuItemStyle()}
                        onMouseEnter={(e) => (e.currentTarget.style.background = 'var(--surface-3)')}
                        onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                    >
                        <ArrowRightLeft size={17} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                        Move to list
                    </button>
                )}
                {!isReviewedList && (
                    <>
                        <div style={{ height: 1, background: 'var(--line)', margin: '4px 0' }} />
                        <button
                            type="button"
                            className="remove-album-button"
                            data-album-id={album.id}
                            data-album-title={album.title}
                            onClick={() => onRemove({ id: album.id, title: album.title })}
                            style={menuItemStyle(true)}
                            onMouseEnter={(e) =>
                                (e.currentTarget.style.background = 'rgba(218,59,42,0.08)')
                            }
                            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
                        >
                            <Trash2 size={17} strokeWidth={2} style={{ color: 'var(--critical)' }} />
                            Remove from list
                        </button>
                    </>
                )}
            </PopoverContent>
        </Popover>
    );
}

function menuItemStyle(danger = false): CSSProperties {
    return {
        display: 'flex',
        alignItems: 'center',
        gap: 12,
        width: '100%',
        padding: '9px 12px',
        borderRadius: 8,
        border: 'none',
        background: 'transparent',
        cursor: 'pointer',
        textAlign: 'left',
        fontFamily: 'var(--font-sans)',
        fontSize: 15,
        fontWeight: 500,
        color: danger ? 'var(--critical)' : 'var(--fg1)',
    };
}

function RowActionBar({
    album,
    mode,
    onRate,
    shown,
}: {
    album: AlbumItem;
    mode: ListMode;
    onRate: () => void;
    shown: boolean;
}) {
    const isListening = mode === 'listening';
    const [hoverCheck, setHoverCheck] = useState(false);
    const [hoverPlay, setHoverPlay] = useState(false);

    const checkBg = hoverCheck ? 'var(--accent)' : 'var(--accent-weak)';
    const checkFg = hoverCheck ? 'var(--accent-on)' : 'var(--accent)';

    return (
        <div
            style={{
                display: 'flex',
                gap: 6,
                justifyContent: 'flex-end',
                alignItems: 'center',
                opacity: shown ? 1 : 0,
                transition: 'opacity var(--dur-fast)',
                pointerEvents: shown ? 'auto' : 'none',
            }}
            onClick={(event) => event.stopPropagation()}
        >
            <Tooltip>
                <TooltipTrigger asChild>
                    <a
                        href={album.spotifyUri}
                        aria-label="Play"
                        onClick={(event) => event.stopPropagation()}
                        onMouseEnter={() => setHoverPlay(true)}
                        onMouseLeave={() => setHoverPlay(false)}
                        style={{
                            width: 30,
                            height: 30,
                            borderRadius: 8,
                            flex: 'none',
                            cursor: 'pointer',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            border:
                                '1px solid ' +
                                (hoverPlay ? 'var(--line-ink)' : 'var(--line-strong)'),
                            background: hoverPlay ? 'var(--surface-3)' : 'transparent',
                            color: hoverPlay ? 'var(--fg1)' : 'var(--fg2)',
                            transition: 'all var(--dur-fast) var(--ease-out)',
                            textDecoration: 'none',
                        }}
                    >
                        <Play size={14} strokeWidth={2} style={{ marginLeft: 1 }} />
                    </a>
                </TooltipTrigger>
                <TooltipContent>Play</TooltipContent>
            </Tooltip>
            {isListening && (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <button
                            type="button"
                            className="listened-button"
                            data-album-id={album.id}
                            aria-label="I listened to this"
                            onClick={(event) => {
                                event.stopPropagation();
                                onRate();
                            }}
                            onMouseEnter={() => setHoverCheck(true)}
                            onMouseLeave={() => setHoverCheck(false)}
                            style={{
                                width: 30,
                                height: 30,
                                borderRadius: 8,
                                flex: 'none',
                                cursor: 'pointer',
                                border: 'none',
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                background: checkBg,
                                color: checkFg,
                                transition:
                                    'background var(--dur-fast) var(--ease-out), color var(--dur-fast)',
                            }}
                        >
                            <Check size={16} strokeWidth={2} />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>I listened to this</TooltipContent>
                </Tooltip>
            )}
        </div>
    );
}

function TableAlbumRow({
    album,
    index,
    listType,
    listId,
    mode,
    metric,
    onMove,
    onRemove,
    onRate,
    onEditNote,
    dragHandleProps,
    draggable,
    highlight,
}: {
    album: AlbumItem;
    index: number;
    listType: ListType;
    listId: number;
    mode: ListMode;
    metric: 'runtime' | 'rating';
    onMove: (album: MoveTarget) => void;
    onRemove: (album: { id: number; title: string }) => void;
    onRate: () => void;
    onEditNote: () => void;
    dragHandleProps?: React.HTMLAttributes<HTMLSpanElement>;
    draggable: boolean;
    highlight?: AlbumHighlight;
}) {
    const [hover, setHover] = useState(false);
    const isMobile = useIsMobile();
    const isReviewedList = listType === 'reviewed';
    const releaseYear = album.releaseDate ? album.releaseDate.slice(0, 4) : '';
    const hasNote = !!album.note && album.note.trim().length > 0;

    /* Mobile: one compact row, no hover-only affordances, note indented below. */
    if (isMobile) {
        return (
            <div
                data-album-db-id={album.id}
                data-highlight={highlight?.kind}
                className={'album-card' + (isReviewedList ? ' reviewed-card' : '')}
                style={{
                    borderBottom: '1px solid var(--line)',
                    borderRadius: highlight ? 10 : undefined,
                    minHeight: isLeaving(highlight) ? 0 : undefined,
                    ...highlightStyle(highlight),
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 12,
                        padding: '10px 2px',
                        minHeight: 72,
                    }}
                >
                    <span
                        {...dragHandleProps}
                        className={'drag-handle' + (draggable ? ' cursor-grab' : '')}
                        style={{
                            display: draggable ? 'inline-flex' : 'none',
                            justifyContent: 'center',
                            color: 'var(--fg3)',
                            cursor: 'grab',
                            flex: 'none',
                            touchAction: 'none',
                        }}
                    >
                        <GripVertical size={16} strokeWidth={2} />
                    </span>
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
                        <AlbumCover album={album} size={54} radius={0} />
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
                            {album.totalTracks} TRK ·{' '}
                            {metric === 'rating'
                                ? album.rating != null
                                    ? album.rating.toFixed(1)
                                    : '—'
                                : album.runtimeMs > 0
                                    ? formatRuntimeShort(album.runtimeMs)
                                    : '—'}
                        </Label>
                    </div>
                    <div style={{ flex: 'none' }}>
                        <AlbumRowMenu
                            album={album}
                            listType={listType}
                            mode={mode}
                            onMove={onMove}
                            onRemove={onRemove}
                            onRate={onRate}
                            onEditNote={onEditNote}
                            boxSize={44}
                        />
                    </div>
                </div>

                {hasNote && (
                    <div
                        style={{
                            padding: '0 2px 12px 84px',
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 6,
                        }}
                    >
                        <Label accent style={{ fontSize: 10 }}>
                            Note
                        </Label>
                        <p
                            className="album-review"
                            style={{
                                margin: 0,
                                fontSize: 13,
                                lineHeight: 1.5,
                                color: 'var(--fg2)',
                                whiteSpace: 'pre-wrap',
                            }}
                        >
                            {album.note}
                        </p>
                    </div>
                )}
            </div>
        );
    }

    return (
        <div
            data-album-db-id={album.id}
            data-highlight={highlight?.kind}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            className={
                'album-card' + (isReviewedList ? ' reviewed-card' : '')
            }
            style={{
                borderRadius: 10,
                background: hover ? 'var(--surface-3)' : 'transparent',
                transition: 'background var(--dur-fast)',
                minHeight: isLeaving(highlight) ? 0 : undefined,
                ...highlightStyle(highlight),
            }}
        >
            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: '20px 26px 76px 1fr 72px 200px 50px 34px',
                    alignItems: 'center',
                    gap: 14,
                    padding: '10px 12px',
                    cursor: 'default',
                }}
            >
                <span
                    {...dragHandleProps}
                    className={'drag-handle' + (draggable ? ' cursor-grab' : '')}
                    style={{
                        display: 'inline-flex',
                        justifyContent: 'center',
                        color: 'var(--fg3)',
                        cursor: draggable ? 'grab' : 'default',
                        opacity: hover && draggable ? 1 : 0,
                        transition: 'opacity var(--dur-fast)',
                    }}
                >
                    <GripVertical size={16} strokeWidth={2} />
                </span>
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
                    <AlbumCover album={album} size={76} radius={0} />
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
                <RowActionBar
                    album={album}
                    mode={mode}
                    onRate={onRate}
                    shown={hover}
                />
                <Label
                    className="meta-col"
                    style={{
                        textAlign: 'right',
                        whiteSpace: 'nowrap',
                        fontVariantNumeric: 'tabular-nums',
                    }}
                    aria-label={`${album.totalTracks} ${trackCountLabel(album.totalTracks)}`}
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
                        <Score value={album.rating ?? null} />
                    </span>
                ) : (
                    <Label style={{ textAlign: 'right', whiteSpace: 'nowrap', color: 'var(--fg2)' }}>
                        {album.runtimeMs > 0 ? formatRuntime(album.runtimeMs) : '—'}
                    </Label>
                )}
                <div
                    style={{
                        opacity: hover ? 1 : 0,
                        transition: 'opacity var(--dur-fast)',
                        display: 'flex',
                        justifyContent: 'flex-end',
                    }}
                    onClick={(event) => event.stopPropagation()}
                >
                    <AlbumRowMenu
                        album={album}
                        listType={listType}
                        mode={mode}
                        onMove={onMove}
                        onRemove={onRemove}
                        onRate={onRate}
                        onEditNote={onEditNote}
                    />
                </div>
            </div>

            {hasNote && (
                <div style={{ padding: '0 16px 13px 176px' }}>
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
                            {album.note}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}

function SortableTableRow({
    album,
    index,
    listType,
    listId,
    mode,
    metric,
    onMove,
    onRemove,
    onRate,
    onEditNote,
    draggable,
    highlight,
}: {
    album: AlbumItem;
    index: number;
    listType: ListType;
    listId: number;
    mode: ListMode;
    metric: 'runtime' | 'rating';
    onMove: (album: MoveTarget) => void;
    onRemove: (album: { id: number; title: string }) => void;
    onRate: () => void;
    onEditNote: () => void;
    draggable: boolean;
    highlight?: AlbumHighlight;
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
                ...leaveStyle(highlight, 'row'),
            }}
        >
            <TableAlbumRow
                album={album}
                index={index}
                listType={listType}
                listId={listId}
                mode={mode}
                metric={metric}
                onMove={onMove}
                onRemove={onRemove}
                onRate={onRate}
                onEditNote={onEditNote}
                draggable={draggable}
                highlight={highlight}
                dragHandleProps={draggable ? { ...attributes, ...listeners } : undefined}
            />
        </div>
    );
}

export default function Show({ list, albums, sort, direction }: ShowProps) {
    const isManual = sort === 'manual';
    const isReviewedList = list.type === 'reviewed';
    const isMobile = useIsMobile();
    const [refreshing, setRefreshing] = useState(false);

    const [viewMode, setViewMode] = useState<ViewMode>(() => {
        if (typeof window === 'undefined') return 'table';
        const stored = window.localStorage.getItem(`list-${list.id}-view`);
        return stored === 'grid' ? 'grid' : 'table';
    });

    function changeViewMode(mode: ViewMode) {
        setViewMode(mode);
        try {
            window.localStorage.setItem(`list-${list.id}-view`, mode);
        } catch {
            /* ignore */
        }
    }

    const [orderedAlbums, setOrderedAlbums] = useState<AlbumItem[]>(albums.data);
    const [albumCount, setAlbumCount] = useState(list.albumsCount);
    const [totalTracks, setTotalTracks] = useState(list.totalTracks);
    const [totalRuntimeMs, setTotalRuntimeMs] = useState(list.totalRuntimeMs);
    const [albumToMove, setAlbumToMove] = useState<MoveTarget | null>(null);
    const [moveDialogOpen, setMoveDialogOpen] = useState(false);
    const [albumToRemove, setAlbumToRemove] = useState<{ id: number; title: string } | null>(null);
    const [removeDialogOpen, setRemoveDialogOpen] = useState(false);
    const [addAlbumDialogOpen, setAddAlbumDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [activeAlbum, setActiveAlbum] = useState<AlbumItem | null>(null);
    const [albumToReview, setAlbumToReview] = useState<RatingDialogAlbum | null>(null);
    const [ratingDialogOpen, setRatingDialogOpen] = useState(false);
    const [albumToNote, setAlbumToNote] = useState<NoteDialogAlbum | null>(null);
    const [noteDialogOpen, setNoteDialogOpen] = useState(false);

    const metric: 'runtime' | 'rating' = isReviewedList ? 'rating' : 'runtime';

    const syncRef = useRef({ count: albums.data.length, firstId: albums.data[0]?.id });
    const removedByReviewRef = useRef<Map<number, { album: AlbumItem; index: number }>>(new Map());
    const highlightTimersRef = useRef<Map<number, ReturnType<typeof setTimeout>>>(new Map());
    const leavingAlbumsRef = useRef<Map<number, { album: AlbumItem; index: number }>>(new Map());
    const leaveStartedAtRef = useRef<Map<number, number>>(new Map());

    const [highlightedAlbums, setHighlightedAlbums] = useState<Map<number, AlbumHighlight>>(() => new Map());

    useEffect(() => {
        const timers = highlightTimersRef.current;

        return () => {
            timers.forEach((timer) => clearTimeout(timer));
            timers.clear();
        };
    }, []);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(TouchSensor, { activationConstraint: { delay: 150, tolerance: 5 } }),
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
            setOrderedAlbums(withLeavingAlbums(albums.data));
        }

        syncRef.current = { count: currCount, firstId: currFirstId };
    }, [albums.data]);

    useEffect(() => {
        setOrderedAlbums(albums.data);
        syncRef.current = { count: albums.data.length, firstId: albums.data[0]?.id };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sort, direction]);

    function handleDragStart(event: DragStartEvent) {
        if (!isManual) return;
        const album = orderedAlbums.find((a) => a.id === event.active.id);
        setActiveAlbum(album ?? null);
    }

    function handleDragEnd(event: DragEndEvent) {
        setActiveAlbum(null);
        if (!isManual) return;

        const { active, over } = event;
        if (!over || active.id === over.id) return;

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

    function addToTotals(album: { totalTracks?: number | null; runtimeMs?: number | null }) {
        setTotalTracks((prev) => prev + (album.totalTracks ?? 0));
        setTotalRuntimeMs((prev) => prev + (album.runtimeMs ?? 0));
    }

    function subtractFromTotals(album: { totalTracks?: number | null; runtimeMs?: number | null }) {
        setTotalTracks((prev) => Math.max(0, prev - (album.totalTracks ?? 0)));
        setTotalRuntimeMs((prev) => Math.max(0, prev - (album.runtimeMs ?? 0)));
    }

    function removeAlbumFromList(albumId: number) {
        const removed = orderedAlbums.find((a) => a.id === albumId);
        setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId));
        setAlbumCount((prev) => prev - 1);
        if (removed) {
            subtractFromTotals(removed);
        }
    }

    /**
     * New albums are filed at the end of the list server-side, which sits below the
     * fold (or on a later page) for anything longer than a screen. Surface the album
     * at the top instead so the add is visible straight away, even with the dialog open.
     */
    function handleAlbumAdded(album: AddedAlbum) {
        setOrderedAlbums((prev) => [album, ...prev.filter((entry) => entry.id !== album.id)]);
        setAlbumCount((prev) => prev + 1);
        addToTotals(album);
        highlightAlbum(album.id, 'added');
    }

    function clearHighlightTimer(albumId: number) {
        const existingTimer = highlightTimersRef.current.get(albumId);

        if (existingTimer) {
            clearTimeout(existingTimer);
            highlightTimersRef.current.delete(albumId);
        }
    }

    function forgetHighlight(albumId: number) {
        setHighlightedAlbums((prev) => {
            const next = new Map(prev);
            next.delete(albumId);

            return next;
        });
    }

    function highlightAlbum(albumId: number, kind: HighlightKind) {
        clearHighlightTimer(albumId);
        setHighlightedAlbums((prev) => new Map(prev).set(albumId, { kind }));

        highlightTimersRef.current.set(
            albumId,
            setTimeout(() => {
                highlightTimersRef.current.delete(albumId);
                forgetHighlight(albumId);
            }, HIGHLIGHT_KINDS[kind].holdMs),
        );
    }

    /**
     * Colour the album the moment the request goes out, so the feedback is immediate. The
     * album only fades and folds away once the server confirms, in `startLeaving`.
     */
    function beginLeaving(albumId: number, kind: 'removed' | 'moved') {
        clearHighlightTimer(albumId);
        leaveStartedAtRef.current.set(albumId, performance.now());
        setHighlightedAlbums((prev) => new Map(prev).set(albumId, { kind }));
    }

    function cancelLeaving(albumId: number) {
        clearHighlightTimer(albumId);
        leaveStartedAtRef.current.delete(albumId);
        leavingAlbumsRef.current.delete(albumId);
        forgetHighlight(albumId);
    }

    /**
     * Keep the album on screen until its exit finishes, then drop it. The server has
     * already forgotten it, so reloaded props are patched back up in the meantime. The
     * exit waits out whatever is left of the colour build-up the click already started.
     */
    function startLeaving(albumId: number, kind: 'removed' | 'moved') {
        const index = orderedAlbums.findIndex((a) => a.id === albumId);
        const album = index === -1 ? null : orderedAlbums[index];

        if (!album) {
            removeAlbumFromList(albumId);
            cancelLeaving(albumId);

            return;
        }

        const startedAt = leaveStartedAtRef.current.get(albumId);
        const colourElapsed = startedAt === undefined ? 0 : performance.now() - startedAt;
        const exitDelayMs = Math.max(0, LEAVE_COLOUR_MS - colourElapsed);

        leaveStartedAtRef.current.delete(albumId);
        leavingAlbumsRef.current.set(albumId, { album, index });
        setOrderedAlbums((prev) => insertLeavingAlbum(prev, album, index));

        clearHighlightTimer(albumId);
        setHighlightedAlbums((prev) => new Map(prev).set(albumId, { kind, exitDelayMs }));

        highlightTimersRef.current.set(
            albumId,
            setTimeout(() => {
                highlightTimersRef.current.delete(albumId);
                forgetHighlight(albumId);
                leavingAlbumsRef.current.delete(albumId);
                setOrderedAlbums((prev) => prev.filter((a) => a.id !== albumId));
                setAlbumCount((prev) => Math.max(0, prev - 1));
                subtractFromTotals(album);
            }, exitDelayMs + LEAVE_ANIMATION_MS),
        );
    }

    /**
     * @param {AlbumItem[]} data Freshly reloaded albums, already missing anything that is leaving.
     * @return {AlbumItem[]} The same list with leaving albums slotted back into their old positions.
     */
    function withLeavingAlbums(data: AlbumItem[]): AlbumItem[] {
        if (leavingAlbumsRef.current.size === 0) {
            return data;
        }

        return [...leavingAlbumsRef.current.values()]
            .sort((a, b) => a.index - b.index)
            .reduce((carry, { album, index }) => insertLeavingAlbum(carry, album, index), data);
    }

    function handleMoveAlbum(album: MoveTarget) {
        setAlbumToMove(album);
        setMoveDialogOpen(true);
    }

    function handleAlbumMoved(albumId: number) {
        startLeaving(albumId, 'moved');
    }

    function handleRemoveAlbum(album: { id: number; title: string }) {
        setAlbumToRemove(album);
        setRemoveDialogOpen(true);
    }

    function handleAlbumRemoved(albumId: number) {
        startLeaving(albumId, 'removed');
    }

    function handleRateAlbum(album: AlbumItem) {
        setAlbumToReview({
            id: album.id,
            title: album.title,
            artist: album.artists,
            coverUrl: album.coverUrl,
            releaseDate: album.releaseDate,
            albumType: album.albumType,
            totalTracks: album.totalTracks,
            currentRating: album.rating ?? null,
            currentReview: album.review ?? null,
        });
        setRatingDialogOpen(true);
    }

    function handleEditNote(album: AlbumItem) {
        setAlbumToNote({
            id: album.id,
            title: album.title,
            artist: album.artists,
            coverUrl: album.coverUrl,
            releaseDate: album.releaseDate,
            albumType: album.albumType,
            totalTracks: album.totalTracks,
            currentNote: album.note ?? null,
        });
        setNoteDialogOpen(true);
    }

    function handleNoteSubmitted(albumId: number, note: string | null) {
        setOrderedAlbums((prev) =>
            prev.map((a) => (a.id === albumId ? { ...a, note } : a)),
        );
    }

    function handleReviewSubmitted(albumId: number, rating: number, review: string | null) {
        if (isReviewedList) {
            setOrderedAlbums((prev) =>
                prev.map((a) => (a.id === albumId ? { ...a, rating, review } : a)),
            );
            return;
        }
        const index = orderedAlbums.findIndex((a) => a.id === albumId);
        if (index !== -1) {
            removedByReviewRef.current.set(albumId, {
                album: orderedAlbums[index],
                index,
            });
        }
        removeAlbumFromList(albumId);
    }

    function handleReviewUndone(albumId: number) {
        const entry = removedByReviewRef.current.get(albumId);
        if (!entry) {
            return;
        }
        removedByReviewRef.current.delete(albumId);
        setOrderedAlbums((prev) => {
            if (prev.some((a) => a.id === albumId)) {
                return prev;
            }
            const insertAt = Math.min(entry.index, prev.length);
            return [...prev.slice(0, insertAt), entry.album, ...prev.slice(insertAt)];
        });
        setAlbumCount((prev) => prev + 1);
        addToTotals(entry.album);
    }

    function handleAlbumUnreviewed(albumId: number) {
        removeAlbumFromList(albumId);
    }

    const hasAlbums = orderedAlbums.length > 0;
    const runtimeStat = formatRuntimeStat(totalRuntimeMs);

    return (
        <>
            <Head title={list.title} />

            <style>{HIGHLIGHT_KEYFRAMES}</style>

            <TopBar crumbs={['Library', list.title]}>
                <Button
                    id="refresh-button"
                    variant="ghost"
                    size="sm"
                    icon={refreshing ? undefined : RefreshCw}
                    onClick={handleRefresh}
                    disabled={refreshing}
                >
                    {refreshing && (
                        <Loader2
                            id="refresh-spinner"
                            size={16}
                            strokeWidth={2}
                            className="animate-spin"
                            style={{ marginRight: 4 }}
                        />
                    )}
                    {refreshing ? 'Refreshing...' : 'Refresh'}
                </Button>
                {!isReviewedList && (
                    <Button
                        variant="primary"
                        size="sm"
                        icon={Plus}
                        onClick={() => setAddAlbumDialogOpen(true)}
                        id="add-album-button"
                    >
                        Add album
                    </Button>
                )}
            </TopBar>

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
                        <HeroBadge list={list} />
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

                        <div
                            style={{
                                display: 'flex',
                                gap: 10,
                                alignItems: 'center',
                                marginTop: 2,
                                flexWrap: 'wrap',
                            }}
                        >
                            {!isReviewedList && (
                                <Button
                                    variant="primary"
                                    icon={Plus}
                                    onClick={() => setAddAlbumDialogOpen(true)}
                                    style={isMobile ? { flex: 1, minHeight: 46 } : undefined}
                                >
                                    Add album
                                </Button>
                            )}
                            {list.type === 'custom' && (
                                <Button
                                    variant="secondary"
                                    icon={Pencil}
                                    onClick={() => setEditDialogOpen(true)}
                                    id="edit-list-button"
                                    style={isMobile ? { minHeight: 46 } : undefined}
                                >
                                    Edit
                                </Button>
                            )}
                            {list.type === 'custom' && (
                                <Button
                                    variant="ghost"
                                    icon={Trash2}
                                    onClick={() => setDeleteDialogOpen(true)}
                                    id="delete-list-button"
                                    style={isMobile ? { minHeight: 46 } : undefined}
                                >
                                    Delete
                                </Button>
                            )}
                        </div>
                    </div>

                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: isMobile ? 'flex-start' : 'flex-end',
                            gap: 28,
                        }}
                    >
                        <CoverMosaic
                            covers={orderedAlbums.slice(0, 4).map((a) => a.coverUrl)}
                            size={isMobile ? 132 : 208}
                            gap={3}
                            radius={14}
                            inner={4}
                            hard
                        />
                    </div>
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
                    <StatBlock value={albumCount} caption="Albums filed" size={isMobile ? 22 : 26} />
                    {totalTracks > 0 && (
                        <StatBlock value={totalTracks} caption="Total tracks" size={isMobile ? 22 : 26} />
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

                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        margin: isMobile ? '16px 0' : '20px 0 22px',
                        flexWrap: isMobile ? 'nowrap' : 'wrap',
                        gap: isMobile ? 10 : 14,
                    }}
                >
                    <div
                        className={isMobile ? 'hscroll' : undefined}
                        style={{
                            display: 'flex',
                            gap: 10,
                            alignItems: 'center',
                            flexWrap: isMobile ? 'nowrap' : 'wrap',
                            overflowX: isMobile ? 'auto' : 'visible',
                            minWidth: 0,
                            padding: isMobile ? '2px 0' : 0,
                        }}
                    >
                        {hasAlbums && (
                            <>
                                <Label style={{ marginRight: 4, flex: 'none' }}>Sort</Label>
                                <SortControl listId={list.id} sort={sort} direction={direction} />
                            </>
                        )}
                    </div>
                    {hasAlbums && (
                        <div style={{ display: 'flex', gap: 10, alignItems: 'center', flex: 'none' }}>
                            {!isMobile && (
                                <Label style={{ fontSize: 10 }}>
                                    {viewMode === 'grid' ? 'Grid' : 'Table'}
                                </Label>
                            )}
                            <div
                                style={{
                                    display: 'flex',
                                    gap: 4,
                                    alignItems: 'center',
                                    background: 'var(--surface-2)',
                                    borderRadius: 10,
                                    padding: 3,
                                }}
                            >
                                <IconButton
                                    icon={ListIcon}
                                    label="Table"
                                    size={18}
                                    boxSize={isMobile ? 40 : 34}
                                    active={viewMode === 'table'}
                                    onClick={() => changeViewMode('table')}
                                    id="view-mode-list"
                                />
                                <IconButton
                                    icon={LayoutGrid}
                                    label="Grid"
                                    size={18}
                                    boxSize={isMobile ? 40 : 34}
                                    active={viewMode === 'grid'}
                                    onClick={() => changeViewMode('grid')}
                                    id="view-mode-grid"
                                />
                            </div>
                        </div>
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
                            {isReviewedList ? 'No reviewed albums yet.' : 'Nothing filed here yet.'}
                        </div>
                        <Label style={{ display: 'block', marginBottom: 20 }}>
                            {isReviewedList
                                ? "Rate an album from any list and it'll show up here."
                                : 'No albums yet. Click "Add an Album" to get started.'}
                        </Label>
                        {!isReviewedList && (
                            <Button
                                variant="primary"
                                icon={Plus}
                                onClick={() => setAddAlbumDialogOpen(true)}
                                style={{ margin: '0 auto' }}
                            >
                                Add albums
                            </Button>
                        )}
                    </div>
                ) : viewMode === 'grid' ? (
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragStart={handleDragStart}
                        onDragEnd={handleDragEnd}
                    >
                        <SortableContext
                            items={orderedAlbums.map((album) => album.id)}
                            strategy={rectSortingStrategy}
                        >
                            <InfiniteScroll
                                data="albums"
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: isMobile
                                        ? 'repeat(auto-fill, minmax(min(100%, 140px), 1fr))'
                                        : 'repeat(auto-fill, minmax(168px, 1fr))',
                                    gap: isMobile ? 14 : 26,
                                }}
                                loading={() => (
                                    <div
                                        id="album-scroll-sentinel"
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'center',
                                            padding: 16,
                                            gridColumn: '1 / -1',
                                        }}
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
                                {orderedAlbums.map((album, index) => (
                                    <GridAlbumItem
                                        key={album.id}
                                        album={album}
                                        index={index}
                                        listType={list.type}
                                        onPlay={() => {}}
                                        draggable={isManual && !isReviewedList}
                                        highlight={highlightedAlbums.get(album.id)}
                                    />
                                ))}
                            </InfiniteScroll>
                        </SortableContext>
                        <DragOverlay>
                            {activeAlbum && (
                                <div
                                    style={{
                                        width: isMobile ? 140 : 168,
                                        transform: 'scale(1.05)',
                                        cursor: 'grabbing',
                                    }}
                                >
                                    <GridAlbumCard
                                        album={activeAlbum}
                                        index={0}
                                        listType={list.type}
                                        onPlay={() => {}}
                                    />
                                </div>
                            )}
                        </DragOverlay>
                    </DndContext>
                ) : (
                    <>
                        {!isMobile && (
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: '20px 26px 76px 1fr 72px 200px 50px 34px',
                                    alignItems: 'center',
                                    gap: 14,
                                    padding: '0 12px 10px',
                                    borderBottom: '1px solid var(--line)',
                                    marginBottom: 6,
                                }}
                            >
                                <span />
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
                                <span />
                            </div>
                        )}
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragStart={handleDragStart}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={orderedAlbums.map((album) => album.id)}
                                strategy={verticalListSortingStrategy}
                            >
                                <InfiniteScroll
                                    data="albums"
                                    style={{
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: isMobile ? 0 : 2,
                                    }}
                                    loading={() => (
                                        <div
                                            id="album-scroll-sentinel"
                                            style={{
                                                display: 'flex',
                                                justifyContent: 'center',
                                                padding: 16,
                                            }}
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
                                    {orderedAlbums.map((album, index) => (
                                        <SortableTableRow
                                            key={album.id}
                                            album={album}
                                            index={index}
                                            listType={list.type}
                                            listId={list.id}
                                            mode={list.mode}
                                            metric={metric}
                                            onMove={handleMoveAlbum}
                                            onRemove={handleRemoveAlbum}
                                            onRate={() => handleRateAlbum(album)}
                                            onEditNote={() => handleEditNote(album)}
                                            draggable={isManual && !isReviewedList}
                                            highlight={highlightedAlbums.get(album.id)}
                                        />
                                    ))}
                                </InfiniteScroll>
                            </SortableContext>
                            <DragOverlay>
                                {activeAlbum && (
                                    <div
                                        style={{
                                            background: 'var(--surface)',
                                            borderRadius: 10,
                                            boxShadow: 'var(--shadow-lg)',
                                            transform: 'scale(1.02)',
                                        }}
                                    >
                                        <TableAlbumRow
                                            album={activeAlbum}
                                            index={0}
                                            listType={list.type}
                                            listId={list.id}
                                            mode={list.mode}
                                            metric={metric}
                                            onMove={() => undefined}
                                            onRemove={() => undefined}
                                            onRate={() => {}}
                                            onEditNote={() => {}}
                                            draggable={false}
                                        />
                                    </div>
                                )}
                            </DragOverlay>
                        </DndContext>
                    </>
                )}
            </div>

            <AddAlbumDialog
                listId={list.id}
                listName={list.title}
                existingSpotifyIds={orderedAlbums.map((album) => album.spotifyId)}
                open={addAlbumDialogOpen}
                onOpenChange={setAddAlbumDialogOpen}
                onAlbumAdded={handleAlbumAdded}
                onAlbumRemoved={handleAlbumRemoved}
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
                onMoving={(albumId) => beginLeaving(albumId, 'moved')}
                onMoveFailed={cancelLeaving}
            />

            <RemoveAlbumDialog
                listId={list.id}
                album={albumToRemove}
                open={removeDialogOpen}
                onOpenChange={setRemoveDialogOpen}
                onRemoved={handleAlbumRemoved}
                onRemoving={(albumId) => beginLeaving(albumId, 'removed')}
                onRemoveFailed={cancelLeaving}
            />

            <RatingDialog
                listId={list.id}
                album={albumToReview}
                open={ratingDialogOpen}
                onOpenChange={setRatingDialogOpen}
                onSubmitted={handleReviewSubmitted}
                allowUnreview={isReviewedList}
                onUnreviewed={handleAlbumUnreviewed}
                onMoveUndone={handleReviewUndone}
            />

            <NoteDialog
                listId={list.id}
                album={albumToNote}
                open={noteDialogOpen}
                onOpenChange={setNoteDialogOpen}
                onSubmitted={handleNoteSubmitted}
            />
        </>
    );
}
