import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import {
    Check,
    CheckCircle,
    Clock,
    Copy,
    CornerUpRight,
    List,
    Loader2,
    LucideIcon,
    Search,
} from 'lucide-react';
import { CSSProperties, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/kit/Button';
import { CardModal } from '@/components/kit/CardModal';
import { MiniCover } from '@/components/kit/CoverArt';
import { Label } from '@/components/kit/Label';
import { listColor } from '@/components/kit/theme';

type MoveMode = 'move' | 'copy';

export interface MoveTarget {
    id: number;
    title: string;
    artists: string;
    coverUrl: string | null;
    releaseDate: string;
}

interface SidebarList {
    id: number;
    title: string;
    type: 'system' | 'reviewed' | 'custom';
    albumsCount: number;
}

interface MoveAlbumDialogProps {
    listId: number;
    album: MoveTarget | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onMoved: (albumId: number) => void;
    onMoving: (albumId: number) => void;
    onMoveFailed: (albumId: number) => void;
}

export default function MoveAlbumDialog({
    listId,
    album,
    open,
    onOpenChange,
    onMoved,
    onMoving,
    onMoveFailed,
}: MoveAlbumDialogProps) {
    const sidebarLists = usePage<{ sidebarLists: SidebarList[] }>().props.sidebarLists ?? [];
    const [mode, setMode] = useState<MoveMode>('move');
    const [selectedListId, setSelectedListId] = useState<number | null>(null);
    const [filter, setFilter] = useState('');
    const [processing, setProcessing] = useState(false);
    const [lockedListIds, setLockedListIds] = useState<number[]>([]);

    const source = sidebarLists.find((list) => list.id === listId) ?? null;
    const destinations = sidebarLists.filter((list) => list.id !== listId && list.type !== 'reviewed');
    const query = filter.trim().toLowerCase();
    const filtered = destinations.filter((list) => list.title.toLowerCase().includes(query));
    const lockedSet = new Set(lockedListIds);
    const selectedList = destinations.find((list) => list.id === selectedListId) ?? null;
    const verb = mode === 'move' ? 'Move' : 'Copy';

    useEffect(() => {
        if (!open || !album) {
            return;
        }

        let active = true;

        axios
            .get(`/albums/${album.id}/list-memberships`)
            .then((response) => {
                if (active) {
                    setLockedListIds(response.data.data);
                }
            })
            .catch(() => {
                if (active) {
                    setLockedListIds([]);
                }
            });

        return () => {
            active = false;
        };
    }, [open, album]);

    function resetState() {
        setMode('move');
        setSelectedListId(null);
        setFilter('');
        setLockedListIds([]);
    }

    function handleOpenChange(value: boolean) {
        if (!value) {
            resetState();
        }
        onOpenChange(value);
    }

    function handleConfirm() {
        if (!album || !selectedList) {
            return;
        }

        const endpoint = mode === 'move'
            ? `/lists/${listId}/albums/${album.id}/move`
            : `/lists/${listId}/albums/${album.id}/copy`;

        router.post(
            endpoint,
            { destination_list_id: selectedList.id },
            {
                preserveScroll: true,
                onStart: () => {
                    setProcessing(true);

                    if (mode === 'move') {
                        onMoving(album.id);
                        handleOpenChange(false);
                    }
                },
                onFinish: () => setProcessing(false),
                onError: () => onMoveFailed(album.id),
                onSuccess: (page) => {
                    const error = (page.props as { flash?: { error?: string } }).flash?.error;

                    if (error) {
                        onMoveFailed(album.id);
                        toast.error(error);

                        return;
                    }

                    if (mode === 'move') {
                        onMoved(album.id);
                    } else {
                        toast.success(`Copied "${album.title}" to ${selectedList.title}`);
                    }

                    handleOpenChange(false);
                },
            },
        );
    }

    if (!open || !album) {
        return null;
    }

    return (
        <CardModal
            label="MOVE ALBUM"
            ariaLabel="Move album to another list"
            width={560}
            contentPad="0"
            onClose={() => {
                if (processing) {
                    return;
                }
                handleOpenChange(false);
            }}
            footer={
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 10 }}>
                    <Button variant="ghost" type="button" onClick={() => handleOpenChange(false)} disabled={processing}>
                        Cancel
                    </Button>
                    <Button
                        variant="primary"
                        type="button"
                        icon={processing ? Loader2 : mode === 'move' ? CornerUpRight : Copy}
                        onClick={handleConfirm}
                        disabled={!selectedList || processing}
                    >
                        {processing
                            ? mode === 'move'
                                ? 'Moving…'
                                : 'Copying…'
                            : selectedList
                              ? `${verb} to ${selectedList.title}`
                              : `${verb} to list`}
                    </Button>
                </div>
            }
        >
            <div style={{ position: 'sticky', top: 0, zIndex: 2, background: 'var(--surface)' }}>
                <div
                    style={{
                        padding: '16px 18px 14px',
                        borderBottom: '1px solid var(--line)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: 16,
                    }}
                >
                    <MovingAlbum album={album} source={source} />
                    <ModeToggle mode={mode} setMode={setMode} />
                </div>

                <div style={{ padding: '14px 18px 10px' }}>
                    <FilterField value={filter} onChange={setFilter} />
                    <Label style={{ display: 'block', padding: '12px 4px 2px' }}>
                        {verb} to · {filtered.length} lists
                    </Label>
                </div>
            </div>

            <div id="destination-lists" style={{ padding: '0 12px 14px' }}>
                {filtered.length ? (
                    filtered.map((list) => (
                        <DestRow
                            key={list.id}
                            list={list}
                            selected={selectedListId === list.id}
                            disabled={lockedSet.has(list.id)}
                            onSelect={() => setSelectedListId(list.id)}
                        />
                    ))
                ) : (
                    <EmptyHint icon={List} title={`No lists match "${filter.trim()}"`} sub="Try another name" />
                )}
            </div>
        </CardModal>
    );
}

function ListMark({ list, size = 13 }: { list: SidebarList; size?: number }) {
    if (list.type === 'custom') {
        return (
            <span
                style={{
                    width: size * 0.7,
                    height: size * 0.7,
                    borderRadius: '50%',
                    background: listColor(list.id),
                    flex: 'none',
                }}
            />
        );
    }

    const Icon = list.type === 'reviewed' ? CheckCircle : Clock;

    return <Icon size={size} strokeWidth={2} style={{ color: 'var(--fg2)' }} />;
}

function ListGlyph({ list, size = 36 }: { list: SidebarList; size?: number }) {
    return (
        <span
            style={{
                width: size,
                height: size,
                borderRadius: 9,
                flex: 'none',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'var(--surface-2)',
                border: '1px solid var(--line)',
            }}
        >
            {list.type === 'custom' ? (
                <span
                    style={{
                        width: size * 0.34,
                        height: size * 0.34,
                        borderRadius: '50%',
                        background: listColor(list.id),
                    }}
                />
            ) : (
                <ListMark list={list} size={Math.round(size * 0.5)} />
            )}
        </span>
    );
}

function MovingAlbum({ album, source }: { album: MoveTarget; source: SidebarList | null }) {
    const year = album.releaseDate ? album.releaseDate.slice(0, 4) : '';

    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 14, minWidth: 0 }}>
            <MiniCover
                src={album.coverUrl}
                alt={album.title}
                size={54}
                radius={8}
                style={{ border: '1px solid var(--line)', boxShadow: 'var(--shadow-sm)' }}
            />
            <div style={{ minWidth: 0 }}>
                <div
                    style={{
                        fontSize: 16.5,
                        fontWeight: 700,
                        letterSpacing: '-0.01em',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                    }}
                >
                    {album.title}
                </div>
                <div style={{ fontSize: 13.5, color: 'var(--fg2)', marginTop: 1 }}>
                    {album.artists}
                    {year && ` · ${year}`}
                </div>
                {source && (
                    <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, marginTop: 7 }}>
                        <Label>FROM</Label>
                        <ListMark list={source} size={13} />
                        <span style={{ fontSize: 12.5, fontWeight: 600 }}>{source.title}</span>
                    </div>
                )}
            </div>
        </div>
    );
}

function ModeToggle({ mode, setMode }: { mode: MoveMode; setMode: (mode: MoveMode) => void }) {
    const options: { id: MoveMode; label: string; icon: LucideIcon }[] = [
        { id: 'move', label: 'Move', icon: CornerUpRight },
        { id: 'copy', label: 'Copy', icon: Copy },
    ];

    return (
        <div
            style={{
                display: 'inline-flex',
                padding: 3,
                borderRadius: 10,
                background: 'var(--surface-2)',
                border: '1px solid var(--line)',
                gap: 2,
                flex: 'none',
            }}
        >
            {options.map((option) => {
                const active = mode === option.id;
                const OptionIcon = option.icon;

                return (
                    <button
                        key={option.id}
                        type="button"
                        className={`mode-toggle-${option.id}`}
                        onClick={() => setMode(option.id)}
                        style={{
                            padding: '6px 13px',
                            borderRadius: 8,
                            border: 'none',
                            cursor: 'pointer',
                            fontFamily: 'var(--font-sans)',
                            fontSize: 13,
                            fontWeight: 600,
                            background: active ? 'var(--surface)' : 'transparent',
                            boxShadow: active ? 'var(--shadow-sm)' : 'none',
                            color: active ? 'var(--fg1)' : 'var(--fg3)',
                            display: 'inline-flex',
                            gap: 6,
                            alignItems: 'center',
                            transition: 'all var(--dur-fast) var(--ease-out)',
                        }}
                    >
                        <OptionIcon size={14} strokeWidth={2} /> {option.label}
                    </button>
                );
            })}
        </div>
    );
}

function FilterField({ value, onChange }: { value: string; onChange: (value: string) => void }) {
    const [focused, setFocused] = useState(false);

    const inputStyle: CSSProperties = {
        width: '100%',
        boxSizing: 'border-box',
        padding: '11px 14px 11px 40px',
        borderRadius: 10,
        border: `1.5px solid ${focused ? 'var(--accent)' : 'var(--line-strong)'}`,
        boxShadow: focused ? '0 0 0 3px var(--accent-weak)' : 'none',
        background: 'var(--surface-2)',
        fontFamily: 'var(--font-sans)',
        fontSize: 15,
        fontWeight: 500,
        color: 'var(--fg1)',
        outline: 'none',
        transition: 'border-color var(--dur-fast), box-shadow var(--dur-fast)',
    };

    return (
        <div style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
            <span
                style={{
                    position: 'absolute',
                    left: 14,
                    display: 'inline-flex',
                    color: focused ? 'var(--accent)' : 'var(--fg3)',
                    transition: 'color var(--dur-fast)',
                    pointerEvents: 'none',
                }}
            >
                <Search size={18} strokeWidth={2} />
            </span>
            <input
                id="list-filter-input"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onFocus={() => setFocused(true)}
                onBlur={() => setFocused(false)}
                placeholder="Filter your lists"
                style={inputStyle}
            />
        </div>
    );
}

function DestRow({
    list,
    selected,
    disabled,
    onSelect,
}: {
    list: SidebarList;
    selected: boolean;
    disabled: boolean;
    onSelect: () => void;
}) {
    const [hover, setHover] = useState(false);

    return (
        <button
            type="button"
            className="dest-list-row"
            data-list-id={list.id}
            disabled={disabled}
            onClick={disabled ? undefined : onSelect}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'grid',
                gridTemplateColumns: 'auto 1fr auto',
                alignItems: 'center',
                gap: 13,
                width: '100%',
                textAlign: 'left',
                padding: '10px 12px',
                borderRadius: 11,
                border: `1.5px solid ${selected ? 'var(--accent)' : 'transparent'}`,
                background: selected ? 'var(--accent-weak)' : hover && !disabled ? 'var(--surface-3)' : 'transparent',
                cursor: disabled ? 'default' : 'pointer',
                opacity: disabled ? 0.5 : 1,
                font: 'inherit',
                transition: 'background var(--dur-fast), border-color var(--dur-fast)',
            }}
        >
            <ListGlyph list={list} size={36} />
            <span style={{ minWidth: 0 }}>
                <span style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <span
                        style={{
                            fontSize: 15.5,
                            fontWeight: 600,
                            color: 'var(--fg1)',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                        }}
                    >
                        {list.title}
                    </span>
                    {list.type === 'system' && <Label style={{ fontSize: 9 }}>SYSTEM</Label>}
                </span>
                <span style={{ display: 'block', fontSize: 12.5, color: 'var(--fg2)', marginTop: 2 }}>
                    {list.albumsCount} albums
                </span>
            </span>
            {disabled ? (
                <Label
                    style={{
                        fontSize: 9.5,
                        display: 'inline-flex',
                        gap: 5,
                        alignItems: 'center',
                        whiteSpace: 'nowrap',
                    }}
                >
                    <Check size={12} strokeWidth={2} /> Already here
                </Label>
            ) : (
                <span
                    style={{
                        width: 22,
                        height: 22,
                        borderRadius: '50%',
                        flex: 'none',
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: selected ? 'var(--accent)' : 'transparent',
                        border: `1.5px solid ${selected ? 'var(--accent)' : 'var(--line-strong)'}`,
                        color: 'var(--accent-on)',
                        transition: 'all var(--dur-fast) var(--ease-spring)',
                    }}
                >
                    {selected && <Check size={14} strokeWidth={2} />}
                </span>
            )}
        </button>
    );
}

function EmptyHint({ icon: Icon, title, sub }: { icon: LucideIcon; title: string; sub?: string }) {
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
                <Icon size={24} strokeWidth={2} />
            </span>
            <div style={{ fontSize: 15, fontWeight: 600, color: 'var(--fg2)' }}>{title}</div>
            {sub && <Label style={{ fontSize: 10.5 }}>{sub}</Label>}
        </div>
    );
}
