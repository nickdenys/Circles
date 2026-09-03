import { Link, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    CheckCircle,
    Clock,
    Disc3,
    Library,
    ListMusic,
    LucideIcon,
    Search,
    Star,
} from 'lucide-react';
import { CSSProperties, useState } from 'react';
import { Button } from '@/components/kit/Button';
import { CoverStack } from '@/components/kit/CoverArt';
import { Label } from '@/components/kit/Label';
import { listColor } from '@/components/kit/theme';
import { TopBar } from '@/components/kit/TopBar';
import { useCountUp } from '@/hooks/use-count-up';
import { useIsMobile } from '@/hooks/use-is-mobile';

interface AuthUser {
    id: number;
    name: string;
    avatar: string | null;
}

interface HomeList {
    id: number;
    title: string;
    type: 'system' | 'reviewed' | 'custom';
    albumsCount: number;
    previewCovers: (string | null)[];
    url: string;
}

interface HomeStats {
    totalAlbums: number;
    totalLists: number;
    userListCount: number;
    reviewedCount: number;
    averageRating: number;
}

interface HomeProps {
    stats: HomeStats;
    lists: HomeList[];
    auth: { user: AuthUser };
    [key: string]: unknown;
}

const SYSTEM_ICON: Record<HomeList['type'], LucideIcon | null> = {
    system: Clock,
    reviewed: CheckCircle,
    custom: null,
};

function StatTile({
    label,
    value,
    icon: Icon,
    accent,
    delay,
    decimals,
}: {
    label: string;
    value: number;
    icon: LucideIcon;
    accent?: boolean;
    delay: number;
    decimals?: number;
}) {
    const display = useCountUp(value, { decimals });
    const [hover, setHover] = useState(false);
    const isMobile = useIsMobile();
    return (
        <div className="rise" style={{ animationDelay: `${delay}ms` }}>
            <div
                onMouseEnter={() => setHover(true)}
                onMouseLeave={() => setHover(false)}
                style={{
                    background: 'var(--surface)',
                    border: '1px solid',
                    borderColor: hover ? 'var(--line-strong)' : 'var(--line)',
                    borderRadius: 'var(--radius-lg)',
                    padding: isMobile ? '14px 15px 13px' : '20px 22px 18px',
                    height: '100%',
                    boxSizing: 'border-box',
                    transform: hover ? 'translateY(-2px)' : 'none',
                    transition:
                        'border-color var(--dur-fast) var(--ease-out), transform var(--dur-fast) var(--ease-out)',
                }}
            >
                <div style={{ display: 'flex', marginBottom: isMobile ? 12 : 18 }}>
                    <Icon
                        size={isMobile ? 16 : 17}
                        strokeWidth={2}
                        style={{ color: accent ? 'var(--accent)' : 'var(--fg3)' }}
                    />
                </div>
                <div
                    style={{
                        fontFamily: 'var(--font-display)',
                        fontWeight: 800,
                        fontSize: isMobile ? 30 : 42,
                        lineHeight: 1,
                        letterSpacing: '-0.03em',
                        color: accent ? 'var(--accent)' : 'var(--fg1)',
                        fontVariantNumeric: 'tabular-nums',
                    }}
                >
                    {display}
                </div>
                <div
                    style={{
                        fontSize: isMobile ? 12.5 : 13.5,
                        color: 'var(--fg2)',
                        marginTop: isMobile ? 5 : 8,
                    }}
                >
                    {label}
                </div>
            </div>
        </div>
    );
}

function ListCard({ list, delay }: { list: HomeList; delay: number }) {
    const [hover, setHover] = useState(false);
    const isMobile = useIsMobile();
    const isSystem = list.type !== 'custom';
    const Icon = SYSTEM_ICON[list.type];

    const style: CSSProperties = {
        animationDelay: `${delay}ms`,
        textAlign: 'left',
        cursor: 'pointer',
        font: 'inherit',
        background: 'var(--surface)',
        border: '1px solid',
        borderColor: hover ? 'var(--line-ink)' : 'var(--line)',
        borderRadius: 'var(--radius-lg)',
        padding: isMobile ? '15px 16px' : '20px 22px',
        display: 'flex',
        flexDirection: 'column',
        gap: isMobile ? 14 : 18,
        boxShadow: hover ? 'var(--shadow-hard)' : 'none',
        transform: hover ? 'translate(-2px, -2px)' : 'none',
        textDecoration: 'none',
        color: 'inherit',
        transition:
            'transform var(--dur-fast) var(--ease-out), box-shadow var(--dur-fast) var(--ease-out), border-color var(--dur-fast) var(--ease-out)',
    };

    return (
        <Link
            href={list.url}
            className="rise"
            style={style}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
        >
            <div
                style={{
                    display: 'flex',
                    alignItems: 'flex-start',
                    justifyContent: 'space-between',
                }}
            >
                <CoverStack
                    covers={list.previewCovers}
                    size={isMobile ? 44 : 52}
                    overlap={isMobile ? 0.42 : 0.34}
                    radius={8}
                />
                <ArrowUpRight
                    size={18}
                    strokeWidth={2}
                    style={{
                        color: hover ? 'var(--accent)' : 'var(--fg3)',
                        transition: 'color var(--dur-fast)',
                    }}
                />
            </div>
            <div>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 9,
                        marginBottom: 6,
                    }}
                >
                    {Icon ? (
                        <Icon size={16} strokeWidth={2} style={{ color: 'var(--fg2)' }} />
                    ) : (
                        <span
                            style={{
                                width: 11,
                                height: 11,
                                borderRadius: '50%',
                                background: listColor(list.id),
                                flex: 'none',
                            }}
                        />
                    )}
                    <span
                        style={{
                            fontFamily: 'var(--font-display)',
                            fontWeight: 700,
                            fontSize: isMobile ? 17 : 19,
                            letterSpacing: '-0.01em',
                            color: 'var(--fg1)',
                        }}
                    >
                        {list.title}
                    </span>
                </div>
                <Label>
                    {list.albumsCount} {list.albumsCount === 1 ? 'ALBUM' : 'ALBUMS'}
                    {isSystem ? ' · SYSTEM' : ''}
                </Label>
            </div>
        </Link>
    );
}

export default function Home() {
    const { auth, stats, lists } = usePage<HomeProps>().props;
    const isMobile = useIsMobile();

    const today = new Date().toLocaleDateString(
        'en-US',
        isMobile
            ? { month: 'short', day: 'numeric' }
            : { weekday: 'long', month: 'short', day: 'numeric' },
    );

    const userLists = lists.filter((list) => list.type === 'custom');
    const systemLists = lists.filter((list) => list.type !== 'custom');

    const cardGrid: CSSProperties = {
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 248px), 1fr))',
        gap: isMobile ? 12 : 16,
    };

    return (
        <>
            <TopBar crumbs={['Library', 'Home']} />
            <div
                style={{
                    maxWidth: 1080,
                    margin: '0 auto',
                    padding: isMobile
                        ? '26px 18px calc(56px + env(safe-area-inset-bottom))'
                        : '56px 56px 80px',
                }}
            >
                <header className="rise" style={{ marginBottom: isMobile ? 28 : 40 }}>
                    <Label accent>{today.toUpperCase()} · HOME</Label>
                    <h1
                        style={{
                            fontFamily: 'var(--font-display)',
                            fontWeight: 800,
                            fontSize: isMobile ? 'clamp(30px, 9vw, 38px)' : 46,
                            letterSpacing: '-0.03em',
                            lineHeight: isMobile ? 1.05 : 1.02,
                            margin: isMobile ? '10px 0 0' : '12px 0 0',
                            textWrap: 'balance',
                        }}
                    >
                        Welcome back, {auth.user.name.split(' ')[0]}.
                    </h1>
                    <p
                        style={{
                            fontSize: isMobile ? 15 : 16,
                            color: 'var(--fg2)',
                            marginTop: isMobile ? 10 : 12,
                            maxWidth: 520,
                            lineHeight: 1.55,
                            textWrap: 'pretty',
                        }}
                    >
                        {stats.totalAlbums} albums filed across {stats.totalLists} lists. Pick up where you left off, or go find something good.
                    </p>
                </header>

                <section
                    style={{
                        display: 'grid',
                        gridTemplateColumns: isMobile ? '1fr 1fr' : 'repeat(4, 1fr)',
                        gap: isMobile ? 10 : 16,
                        marginBottom: isMobile ? 34 : 48,
                    }}
                >
                    <StatTile label="Albums filed" value={stats.totalAlbums} icon={Disc3} delay={60} />
                    <StatTile label="Lists kept" value={stats.totalLists} icon={ListMusic} delay={120} />
                    <StatTile label="Reviewed" value={stats.reviewedCount} icon={CheckCircle} delay={180} />
                    <StatTile
                        label="Average rating"
                        value={stats.averageRating}
                        icon={Star}
                        accent
                        decimals={1}
                        delay={240}
                    />
                </section>

                {systemLists.length > 0 && (
                    <section style={{ marginBottom: isMobile ? 32 : 44 }}>
                        <div style={{ marginBottom: isMobile ? 14 : 18 }}>
                            <Label ink>SYSTEM</Label>
                        </div>
                        <div style={cardGrid}>
                            {systemLists.map((list, i) => (
                                <ListCard key={list.id} list={list} delay={300 + i * 60} />
                            ))}
                        </div>
                    </section>
                )}

                <section>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'baseline',
                            justifyContent: 'space-between',
                            gap: 12,
                            marginBottom: isMobile ? 14 : 18,
                        }}
                    >
                        <div style={{ display: 'flex', alignItems: 'baseline', gap: 12 }}>
                            <Label ink>YOUR LISTS</Label>
                            <span style={{ fontSize: 13, color: 'var(--fg3)' }}>
                                {userLists.length} {userLists.length === 1 ? 'list' : 'lists'}
                            </span>
                        </div>
                        <Link href="/lists" style={{ textDecoration: 'none' }}>
                            <Button variant="ghost" size="sm" icon={Library}>
                                All lists
                            </Button>
                        </Link>
                    </div>
                    {userLists.length === 0 ? (
                        <div
                            style={{
                                border: '1.5px dashed var(--line-strong)',
                                borderRadius: 'var(--radius-lg)',
                                padding: isMobile ? '36px 20px' : '48px 24px',
                                textAlign: 'center',
                                color: 'var(--fg3)',
                            }}
                        >
                            <Search
                                size={28}
                                strokeWidth={2}
                                style={{ color: 'var(--line-strong)', margin: '0 auto 12px' }}
                            />
                            <div
                                style={{
                                    fontFamily: 'var(--font-display)',
                                    fontWeight: 700,
                                    fontSize: 19,
                                    color: 'var(--fg1)',
                                    marginBottom: 6,
                                }}
                            >
                                No custom lists yet.
                            </div>
                            <Label style={{ display: 'block', marginBottom: 16 }}>
                                Start a fresh shelf.
                            </Label>
                            <Link href="/lists" style={{ display: 'inline-block', textDecoration: 'none' }}>
                                <Button variant="primary" size="sm">
                                    Go to lists
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div style={cardGrid}>
                            {userLists.map((list, i) => (
                                <ListCard key={list.id} list={list} delay={560 + i * 60} />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
