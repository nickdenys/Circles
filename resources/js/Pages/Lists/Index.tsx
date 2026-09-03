import { Head, InfiniteScroll, Link, usePage } from '@inertiajs/react';
import {
    ChevronRight,
    CheckCircle,
    Clock,
    LayoutGrid,
    List as ListIcon,
    Loader2,
    LucideIcon,
    Plus,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/circles/Button';
import { Chip } from '@/components/circles/Chip';
import { CoverRow, CoverSpine } from '@/components/circles/CoverArt';
import { IconButton } from '@/components/circles/IconButton';
import { Label } from '@/components/circles/Label';
import { Score } from '@/components/circles/Score';
import { StatBlock } from '@/components/circles/StatBlock';
import { listCode, listColor } from '@/components/circles/theme';
import { TopBar } from '@/components/circles/TopBar';
import CreateListDialog from '@/Pages/Lists/CreateListDialog';

type ListType = 'system' | 'reviewed' | 'custom';
type ViewMode = 'list' | 'grid';

const VIEW_MODE_STORAGE_KEY = 'circles-lists-view';

interface AlbumListItem {
    id: number;
    title: string;
    description?: string | null;
    type: ListType;
    albumsCount: number;
    previewCovers: (string | null)[];
    averageRating?: number | null;
    updatedLabel?: string | null;
    url: string;
}

interface PaginatedLists {
    data: AlbumListItem[];
    next_page_url: string | null;
}

interface ListsIndexProps {
    lists: PaginatedLists;
    [key: string]: unknown;
}

const SYSTEM_ICON: Record<ListType, LucideIcon | null> = {
    system: Clock,
    reviewed: CheckCircle,
    custom: null,
};

function listCodeFor(list: AlbumListItem): string {
    if (list.type !== 'custom') {
        return `SYS_${list.id.toString().padStart(2, '0')}`;
    }
    return listCode(list.id);
}

function ListGlyph({ list, size = 12 }: { list: AlbumListItem; size?: number }) {
    const Icon = SYSTEM_ICON[list.type];
    if (Icon) {
        return (
            <Icon
                size={size + 2}
                strokeWidth={2}
                style={{ color: 'var(--accent)', flex: 'none' }}
            />
        );
    }
    return (
        <span
            style={{
                width: size,
                height: size,
                borderRadius: '50%',
                background: listColor(list.id),
                flex: 'none',
                boxShadow: `0 0 0 4px ${listColor(list.id)}22`,
            }}
        />
    );
}

function GridCard({ list }: { list: AlbumListItem }) {
    const [hover, setHover] = useState(false);
    const isSystem = list.type !== 'custom';
    const code = listCodeFor(list);

    return (
        <Link
            href={list.url}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'flex',
                flexDirection: 'column',
                background: 'var(--surface)',
                cursor: 'pointer',
                border: '1px solid ' + (hover ? 'var(--line-ink)' : 'var(--line-strong)'),
                borderRadius: 14,
                overflow: 'hidden',
                clipPath: 'inset(0 round 14px)',
                boxShadow: hover ? 'var(--shadow-hard)' : 'none',
                transform: hover ? 'translate(-2px, -2px)' : 'none',
                textDecoration: 'none',
                color: 'inherit',
                transition:
                    'transform var(--dur-fast) var(--ease-out), box-shadow var(--dur-fast) var(--ease-out), border-color var(--dur-fast)',
            }}
        >
            <CoverSpine covers={list.previewCovers} />

            <div
                style={{
                    padding: '16px 16px 14px',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 11,
                    flex: 1,
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'flex-start',
                        justifyContent: 'space-between',
                        gap: 10,
                    }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10, minWidth: 0 }}>
                        <ListGlyph list={list} />
                        <span
                            style={{
                                fontFamily: 'var(--font-display)',
                                fontWeight: 700,
                                fontSize: 20,
                                letterSpacing: '-0.02em',
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                            }}
                        >
                            {list.title}
                        </span>
                    </div>
                    {isSystem && (
                        <span
                            style={{
                                fontFamily: 'var(--font-mono)',
                                fontSize: 9,
                                letterSpacing: '0.1em',
                                color: 'var(--accent)',
                                border: '1px solid var(--accent)',
                                borderRadius: 5,
                                padding: '2px 5px',
                                flex: 'none',
                            }}
                        >
                            SYS
                        </span>
                    )}
                </div>

                {list.description && (
                    <p style={{ margin: 0, fontSize: 13.5, lineHeight: 1.5, color: 'var(--fg2)', whiteSpace: 'pre-line' }}>
                        {list.description}
                    </p>
                )}

                <div style={{ flex: 1 }} />

                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        paddingTop: 12,
                        borderTop: '1px solid var(--line)',
                    }}
                >
                    <span
                        style={{
                            fontFamily: 'var(--font-mono)',
                            fontSize: 10.5,
                            letterSpacing: '0.08em',
                            textTransform: 'uppercase',
                            color: 'var(--fg3)',
                            display: 'flex',
                            gap: 8,
                            alignItems: 'center',
                        }}
                    >
                        <span>{code}</span>
                        <span style={{ color: 'var(--line-strong)' }}>·</span>
                        <span>
                            {list.albumsCount} {list.albumsCount === 1 ? 'alb' : 'albs'}
                        </span>
                    </span>
                    {list.averageRating != null ? (
                        <span style={{ display: 'inline-flex', alignItems: 'baseline', gap: 3 }}>
                            <Score value={list.averageRating} size={14} />
                            <Label style={{ fontSize: 9 }}>avg</Label>
                        </span>
                    ) : (
                        <Label style={{ fontSize: 9 }}>unrated</Label>
                    )}
                </div>
            </div>
        </Link>
    );
}

const ROW_GRID = '44px minmax(220px, 1.3fr) minmax(240px, 1.4fr) 150px 90px 20px';

function ListRow({ list, index }: { list: AlbumListItem; index: number }) {
    const [hover, setHover] = useState(false);
    const isSystem = list.type !== 'custom';

    return (
        <Link
            href={list.url}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'grid',
                gridTemplateColumns: ROW_GRID,
                alignItems: 'center',
                gap: 24,
                padding: '22px 4px',
                borderBottom: '1px solid var(--line)',
                textDecoration: 'none',
                color: 'inherit',
                cursor: 'pointer',
                transition: 'background var(--dur-fast) var(--ease-out)',
                background: hover ? 'var(--surface-2)' : 'transparent',
            }}
        >
            <Label
                style={{
                    fontFamily: 'var(--font-mono)',
                    fontSize: 13,
                    textAlign: 'center',
                    color: 'var(--fg2)',
                }}
            >
                {String(index + 1).padStart(2, '0')}
            </Label>

            <div style={{ minWidth: 0, display: 'flex', flexDirection: 'column', gap: 6 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 }}>
                    <ListGlyph list={list} size={12} />
                    <span
                        style={{
                            fontFamily: 'var(--font-display)',
                            fontWeight: 800,
                            fontSize: 24,
                            lineHeight: 1.1,
                            letterSpacing: '-0.02em',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            color: 'var(--fg1)',
                        }}
                    >
                        {list.title}
                    </span>
                    {isSystem && (
                        <span
                            style={{
                                fontFamily: 'var(--font-mono)',
                                fontSize: 9,
                                letterSpacing: '0.1em',
                                color: 'var(--accent)',
                                border: '1px solid var(--accent)',
                                borderRadius: 5,
                                padding: '2px 5px',
                                flex: 'none',
                            }}
                        >
                            SYS
                        </span>
                    )}
                </div>
                <p
                    style={{
                        margin: 0,
                        marginLeft: 24,
                        fontSize: 14,
                        lineHeight: 1.5,
                        color: 'var(--fg2)',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                    }}
                >
                    {list.description || ' '}
                </p>
            </div>

            <div style={{ minWidth: 0 }}>
                <CoverRow covers={list.previewCovers} count={4} size={52} gap={6} radius={10} />
            </div>

            <div
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 4,
                    textAlign: 'right',
                }}
            >
                <div style={{ display: 'inline-flex', alignItems: 'baseline', gap: 4, justifyContent: 'flex-end' }}>
                    <span
                        style={{
                            fontFamily: 'var(--font-display)',
                            fontWeight: 800,
                            fontSize: 22,
                            lineHeight: 1,
                            color: 'var(--fg1)',
                            letterSpacing: '-0.02em',
                        }}
                    >
                        {list.albumsCount}
                    </span>
                    <Label>alb</Label>
                </div>
                {list.updatedLabel && <Label>UPD {list.updatedLabel}</Label>}
            </div>

            <span
                style={{
                    textAlign: 'right',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'flex-end',
                }}
            >
                {list.averageRating != null ? (
                    <Score value={list.averageRating} size={22} />
                ) : (
                    <Label>—</Label>
                )}
            </span>

            <ChevronRight
                size={18}
                strokeWidth={2}
                style={{
                    color: hover ? 'var(--accent)' : 'var(--fg3)',
                    flex: 'none',
                    justifySelf: 'end',
                    transition: 'color var(--dur-fast)',
                }}
            />
        </Link>
    );
}

function ListRowHeader() {
    return (
        <div
            style={{
                display: 'grid',
                gridTemplateColumns: ROW_GRID,
                alignItems: 'center',
                gap: 24,
                padding: '0 4px 14px',
                borderBottom: '1px solid var(--line)',
            }}
        >
            <Label style={{ textAlign: 'center' }}>IDX</Label>
            <Label>List</Label>
            <Label>Contents</Label>
            <Label style={{ textAlign: 'right' }}>Filed · Updated</Label>
            <Label style={{ textAlign: 'right' }}>Avg</Label>
            <span />
        </div>
    );
}

function NewListCardGrid({ onClick }: { onClick: () => void }) {
    const [hover, setHover] = useState(false);
    return (
        <button
            type="button"
            onClick={onClick}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: 12,
                minHeight: 240,
                background: 'transparent',
                cursor: 'pointer',
                fontFamily: 'var(--font-sans)',
                border: '1.5px dashed ' + (hover ? 'var(--accent)' : 'var(--line-strong)'),
                borderRadius: 14,
                color: hover ? 'var(--accent)' : 'var(--fg3)',
                transition: 'all var(--dur-fast) var(--ease-out)',
            }}
        >
            <span
                style={{
                    width: 46,
                    height: 46,
                    borderRadius: '50%',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: hover ? 'var(--accent-weak)' : 'var(--surface-2)',
                    color: hover ? 'var(--accent)' : 'var(--fg2)',
                    transition: 'all var(--dur-fast)',
                }}
            >
                <Plus size={22} strokeWidth={2} />
            </span>
            <span style={{ fontSize: 14, fontWeight: 600 }}>New list</span>
            <Label style={{ fontSize: 10 }}>Start a fresh shelf</Label>
        </button>
    );
}

function NewListRow({ onClick }: { onClick: () => void }) {
    const [hover, setHover] = useState(false);
    return (
        <button
            type="button"
            onClick={onClick}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 14,
                padding: '20px 4px',
                background: 'transparent',
                border: 'none',
                cursor: 'pointer',
                fontFamily: 'var(--font-sans)',
                color: hover ? 'var(--accent)' : 'var(--fg3)',
                transition: 'color var(--dur-fast) var(--ease-out)',
                width: 'fit-content',
            }}
        >
            <span
                style={{
                    width: 40,
                    height: 40,
                    borderRadius: '50%',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    border: '1.5px dashed ' + (hover ? 'var(--accent)' : 'var(--line-strong)'),
                    background: hover ? 'var(--accent-weak)' : 'transparent',
                    color: 'inherit',
                    flex: 'none',
                    transition: 'all var(--dur-fast)',
                }}
            >
                <Plus size={18} strokeWidth={2} />
            </span>
            <span style={{ fontSize: 17, fontWeight: 500 }}>New list</span>
        </button>
    );
}

function readStoredViewMode(): ViewMode {
    if (typeof window === 'undefined') return 'list';
    try {
        const stored = window.localStorage.getItem(VIEW_MODE_STORAGE_KEY);
        return stored === 'grid' ? 'grid' : 'list';
    } catch {
        return 'list';
    }
}

type Chipset = 'all' | 'system' | 'user';

export default function Index() {
    const { lists } = usePage<ListsIndexProps>().props;
    const [createDialogOpen, setCreateDialogOpen] = useState(false);
    const [chip, setChip] = useState<Chipset>('all');
    const [viewMode, setViewMode] = useState<ViewMode>(() => readStoredViewMode());

    useEffect(() => {
        try {
            window.localStorage.setItem(VIEW_MODE_STORAGE_KEY, viewMode);
        } catch {
            /* ignore storage errors */
        }
    }, [viewMode]);

    const filtered = useMemo(() => {
        if (chip === 'all') return lists.data;
        if (chip === 'system') return lists.data.filter((list) => list.type !== 'custom');
        return lists.data.filter((list) => list.type === 'custom');
    }, [lists.data, chip]);

    const totalAlbums = useMemo(
        () => lists.data.reduce((sum, list) => sum + list.albumsCount, 0),
        [lists.data],
    );

    return (
        <>
            <Head title="Your lists" />

            <TopBar crumbs={['Library', 'All lists']}>
                <Button variant="primary" size="sm" icon={Plus} onClick={() => setCreateDialogOpen(true)}>
                    New list
                </Button>
            </TopBar>

            <div style={{ borderBottom: '1px solid var(--line)' }}>
                <div
                    style={{
                        maxWidth: 1140,
                        margin: '0 auto',
                        padding: '30px 40px 22px',
                    }}
                >
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'flex-end',
                            gap: 40,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                            <Label>
                                Library / {String(lists.data.length).padStart(2, '0')} lists
                            </Label>
                            <h1
                                style={{
                                    fontFamily: 'var(--font-display)',
                                    fontWeight: 800,
                                    fontSize: 52,
                                    lineHeight: 0.96,
                                    letterSpacing: '-0.03em',
                                    margin: 0,
                                }}
                            >
                                Your lists
                            </h1>
                            <p
                                style={{
                                    fontSize: 16,
                                    margin: 0,
                                    color: 'var(--fg2)',
                                    maxWidth: 460,
                                    lineHeight: 1.55,
                                }}
                            >
                                Every collection you've filed, system shelves and your own.
                            </p>
                        </div>
                        <div style={{ display: 'flex', gap: 34, alignItems: 'flex-end' }}>
                            <StatBlock value={lists.data.length} caption="Lists" size={30} />
                            <StatBlock value={totalAlbums} caption="Albums filed" size={30} />
                        </div>
                    </div>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 16,
                            marginTop: 22,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                            <Chip on={chip === 'all'} onClick={() => setChip('all')}>
                                All
                            </Chip>
                            <Chip on={chip === 'system'} onClick={() => setChip('system')}>
                                System
                            </Chip>
                            <Chip on={chip === 'user'} onClick={() => setChip('user')}>
                                Your lists
                            </Chip>
                        </div>
                        <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                            <Label style={{ fontSize: 10 }}>{viewMode === 'grid' ? 'Grid' : 'List'}</Label>
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
                                    label="List view"
                                    size={18}
                                    boxSize={34}
                                    active={viewMode === 'list'}
                                    onClick={() => setViewMode('list')}
                                    id="lists-view-mode-list"
                                />
                                <IconButton
                                    icon={LayoutGrid}
                                    label="Grid view"
                                    size={18}
                                    boxSize={34}
                                    active={viewMode === 'grid'}
                                    onClick={() => setViewMode('grid')}
                                    id="lists-view-mode-grid"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                style={{
                    maxWidth: 1140,
                    margin: '0 auto',
                    padding: '26px 40px 80px',
                }}
            >
                {filtered.length === 0 ? (
                    <div
                        style={{
                            border: '1.5px dashed var(--line-strong)',
                            borderRadius: 14,
                            padding: '64px 24px',
                            textAlign: 'center',
                            color: 'var(--fg3)',
                        }}
                    >
                        <div
                            style={{
                                fontFamily: 'var(--font-display)',
                                fontWeight: 700,
                                fontSize: 22,
                                color: 'var(--fg1)',
                                marginBottom: 6,
                            }}
                        >
                            No lists to show.
                        </div>
                        <Label style={{ display: 'block', marginBottom: 18 }}>
                            Try a different filter.
                        </Label>
                    </div>
                ) : viewMode === 'grid' ? (
                    <InfiniteScroll
                        data="lists"
                        buffer={300}
                        style={{
                            display: 'grid',
                            gridTemplateColumns: 'repeat(auto-fill, minmax(286px, 1fr))',
                            gap: 20,
                        }}
                        loading={() => (
                            <div
                                id="scroll-sentinel"
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
                        {filtered.map((list) => (
                            <GridCard key={list.id} list={list} />
                        ))}
                        <NewListCardGrid onClick={() => setCreateDialogOpen(true)} />
                    </InfiniteScroll>
                ) : (
                    <>
                        <ListRowHeader />
                        <InfiniteScroll
                            data="lists"
                            buffer={300}
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                            }}
                            loading={() => (
                                <div
                                    id="scroll-sentinel"
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
                            {filtered.map((list, index) => (
                                <ListRow key={list.id} list={list} index={index} />
                            ))}
                            <NewListRow onClick={() => setCreateDialogOpen(true)} />
                        </InfiniteScroll>
                    </>
                )}
            </div>

            <CreateListDialog open={createDialogOpen} onOpenChange={setCreateDialogOpen} />
        </>
    );
}
