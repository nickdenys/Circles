/**
 * Sidebar rail. Rendered on every authenticated page.
 *
 * Brand wordmark + nav: "Home" links to "/" and "All lists" links to "/lists".
 * Below 760px the rail becomes an off-canvas drawer opened from the TopBar.
 */
import { Link, router, usePage } from '@inertiajs/react';
import {
    CheckCircle,
    ChevronDown,
    ChevronUp,
    Clock,
    Library,
    LayoutDashboard,
    LogOut,
    LucideIcon,
    Moon,
    Plus,
    Settings as SettingsIcon,
    Sun,
    User,
    X,
} from 'lucide-react';
import { CSSProperties, MouseEvent, useCallback, useEffect, useRef, useState } from 'react';
import { useIsMobile } from '@/hooks/use-is-mobile';
import { applyTheme, listColor, Theme } from './theme';
import { IconButton } from './IconButton';
import { Wordmark } from './Logomark';
import { Label } from './Label';

interface SidebarList {
    id: number;
    title: string;
    type: 'system' | 'reviewed' | 'custom';
    albumsCount: number;
    url: string;
}

interface AuthUser {
    id: number;
    name: string;
    avatar: string | null;
}

interface SidebarSharedProps {
    auth: { user: AuthUser };
    currentRouteName?: string;
    sidebarLists: SidebarList[];
    [key: string]: unknown;
}

interface SidebarProps {
    onNewList?: () => void;
    theme: Theme;
    onToggleTheme: () => void;
    /** Drawer state — only meaningful below the mobile breakpoint. */
    open?: boolean;
    onClose?: () => void;
}

const SYSTEM_ICONS: Record<string, LucideIcon> = {
    system: Clock,
    reviewed: CheckCircle,
};

function NavItem({
    icon: Icon,
    label,
    href,
    active,
    onClick,
}: {
    icon: LucideIcon;
    label: string;
    href?: string;
    active?: boolean;
    onClick?: () => void;
}) {
    const [hover, setHover] = useState(false);
    const style: CSSProperties = {
        display: 'flex',
        alignItems: 'center',
        gap: 11,
        width: '100%',
        textAlign: 'left',
        padding: '10px 12px',
        borderRadius: 10,
        border: 'none',
        cursor: 'pointer',
        fontFamily: 'var(--font-sans)',
        fontSize: 14,
        fontWeight: 600,
        textDecoration: 'none',
        boxSizing: 'border-box',
        position: 'relative',
        background: active ? 'var(--accent-weak)' : hover ? 'var(--surface-3)' : 'transparent',
        color: active ? 'var(--accent)' : 'var(--fg1)',
        transition: 'all var(--dur-fast) var(--ease-out)',
        whiteSpace: 'nowrap',
    };
    const inner = (
        <>
            {active && (
                <span
                    style={{
                        position: 'absolute',
                        left: 0,
                        top: 9,
                        bottom: 9,
                        width: 3,
                        borderRadius: 3,
                        background: 'var(--accent)',
                    }}
                />
            )}
            <Icon size={18} strokeWidth={2} />
            {label}
        </>
    );
    const handlers = {
        onMouseEnter: () => setHover(true),
        onMouseLeave: () => setHover(false),
    };
    if (href) {
        return (
            <Link href={href} style={style} {...handlers}>
                {inner}
            </Link>
        );
    }
    return (
        <button onClick={onClick} style={style} {...handlers}>
            {inner}
        </button>
    );
}

function ListItem({
    list,
    active,
}: {
    list: SidebarList;
    active: boolean;
}) {
    const [hover, setHover] = useState(false);
    const SystemIcon = SYSTEM_ICONS[list.type];
    const style: CSSProperties = {
        display: 'flex',
        alignItems: 'center',
        gap: 11,
        width: '100%',
        textAlign: 'left',
        padding: '9px 12px',
        borderRadius: 10,
        border: 'none',
        cursor: 'pointer',
        textDecoration: 'none',
        boxSizing: 'border-box',
        background: active ? 'var(--accent-weak)' : hover ? 'var(--surface-3)' : 'transparent',
        color: active ? 'var(--accent)' : 'var(--fg1)',
        transition: 'all var(--dur-fast) var(--ease-out)',
        position: 'relative',
        fontFamily: 'var(--font-sans)',
    };
    return (
        <Link
            href={list.url}
            style={style}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
        >
            {active && (
                <span
                    style={{
                        position: 'absolute',
                        left: 0,
                        top: 8,
                        bottom: 8,
                        width: 3,
                        borderRadius: 3,
                        background: 'var(--accent)',
                    }}
                />
            )}
            {SystemIcon ? (
                <SystemIcon
                    size={18}
                    strokeWidth={2}
                    style={{ color: active ? 'var(--accent)' : 'var(--fg2)' }}
                />
            ) : (
                <span
                    style={{
                        width: 10,
                        height: 10,
                        borderRadius: '50%',
                        background: listColor(list.id),
                        flex: 'none',
                    }}
                />
            )}
            <span
                style={{
                    fontSize: 14,
                    fontWeight: active ? 600 : 500,
                    flex: 1,
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                }}
            >
                {list.title}
            </span>
            <span
                style={{
                    fontFamily: 'var(--font-mono)',
                    fontSize: 11,
                    color: active ? 'var(--accent)' : 'var(--fg3)',
                }}
            >
                {list.albumsCount}
            </span>
        </Link>
    );
}

function ProfileMenu({
    user,
    theme,
    onToggleTheme,
}: {
    user: AuthUser;
    theme: Theme;
    onToggleTheme: () => void;
}) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        function handler(event: globalThis.MouseEvent) {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [open]);

    const handleLogout = () => {
        setOpen(false);
        router.post('/logout');
    };

    const initial = user.name?.charAt(0)?.toUpperCase() || '?';

    const renderItem = (
        Icon: LucideIcon,
        label: string,
        onClick?: () => void,
        danger?: boolean,
        renderAs?: 'button' | 'link',
        href?: string,
    ) => {
        const baseStyle: CSSProperties = {
            display: 'flex',
            alignItems: 'center',
            gap: 10,
            width: '100%',
            padding: '8px 12px',
            background: 'transparent',
            border: 'none',
            cursor: 'pointer',
            borderRadius: 8,
            textAlign: 'left',
            textDecoration: 'none',
            fontFamily: 'var(--font-sans)',
            fontSize: 13.5,
            fontWeight: 500,
            color: danger ? 'var(--critical)' : 'var(--fg1)',
        };
        const onEnter = (e: MouseEvent<HTMLElement>) => {
            e.currentTarget.style.background = danger
                ? 'rgba(218,59,42,0.08)'
                : 'var(--surface-3)';
        };
        const onLeave = (e: MouseEvent<HTMLElement>) => {
            e.currentTarget.style.background = 'transparent';
        };
        const inner = (
            <>
                <Icon
                    size={15}
                    strokeWidth={2}
                    style={{ color: danger ? 'var(--critical)' : 'var(--fg2)', flex: 'none' }}
                />
                {label}
            </>
        );
        if (renderAs === 'link' && href) {
            return (
                <Link
                    key={label}
                    href={href}
                    onClick={() => setOpen(false)}
                    style={baseStyle}
                    onMouseEnter={onEnter}
                    onMouseLeave={onLeave}
                >
                    {inner}
                </Link>
            );
        }
        return (
            <button
                key={label}
                type="button"
                onClick={() => {
                    setOpen(false);
                    onClick?.();
                }}
                style={baseStyle}
                onMouseEnter={onEnter}
                onMouseLeave={onLeave}
            >
                {inner}
            </button>
        );
    };

    return (
        <div
            ref={ref}
            style={{
                borderTop: '1px solid var(--line)',
                paddingTop: 12,
                marginTop: 12,
                position: 'relative',
            }}
        >
            {open && (
                <div
                    style={{
                        position: 'absolute',
                        bottom: 'calc(100% + 8px)',
                        left: 0,
                        right: 0,
                        zIndex: 300,
                        background: 'var(--surface)',
                        border: '1px solid var(--line-strong)',
                        borderRadius: 'var(--radius-md)',
                        boxShadow: 'var(--shadow-md)',
                        padding: 6,
                        animation: 'fadeSlideUp var(--dur-fast) var(--ease-out) both',
                    }}
                >
                    <div
                        style={{
                            padding: '8px 12px 6px',
                            borderBottom: '1px solid var(--line)',
                            marginBottom: 4,
                        }}
                    >
                        <div style={{ fontSize: 13, fontWeight: 600 }}>{user.name}</div>
                        <Label style={{ fontSize: 10 }}>Spotify connected</Label>
                    </div>
                    {renderItem(SettingsIcon, 'Settings', undefined, false, 'link', '/settings')}
                    {renderItem(
                        theme === 'dark' ? Sun : Moon,
                        theme === 'dark' ? 'Switch to light' : 'Switch to dark',
                        onToggleTheme,
                    )}
                    <div style={{ height: 1, background: 'var(--line)', margin: '4px 0' }} />
                    {renderItem(LogOut, 'Log out', handleLogout, true)}
                </div>
            )}

            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 10,
                    width: '100%',
                    background: open ? 'var(--surface-2)' : 'transparent',
                    border: 'none',
                    cursor: 'pointer',
                    borderRadius: 10,
                    padding: '6px 8px',
                    transition: 'background var(--dur-fast)',
                }}
                onMouseEnter={(e) => {
                    if (!open) e.currentTarget.style.background = 'var(--surface-2)';
                }}
                onMouseLeave={(e) => {
                    if (!open) e.currentTarget.style.background = 'transparent';
                }}
            >
                {user.avatar ? (
                    <img
                        src={user.avatar}
                        alt={user.name}
                        style={{ width: 32, height: 32, borderRadius: '50%', flex: 'none', objectFit: 'cover' }}
                    />
                ) : (
                    <span
                        style={{
                            width: 32,
                            height: 32,
                            borderRadius: '50%',
                            background: 'var(--tag-grape)',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: '#fff',
                            fontFamily: 'var(--font-display)',
                            fontWeight: 700,
                            fontSize: 14,
                            flex: 'none',
                        }}
                    >
                        {initial}
                    </span>
                )}
                <div style={{ flex: 1, minWidth: 0, textAlign: 'left' }}>
                    <div
                        style={{
                            fontSize: 13,
                            fontWeight: 600,
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                        }}
                    >
                        {user.name}
                    </div>
                    <Label style={{ fontSize: 10 }}>Spotify connected</Label>
                </div>
                {open ? (
                    <ChevronDown size={14} strokeWidth={2} style={{ color: 'var(--fg3)', flex: 'none' }} />
                ) : (
                    <ChevronUp size={14} strokeWidth={2} style={{ color: 'var(--fg3)', flex: 'none' }} />
                )}
            </button>
        </div>
    );
}

export function Sidebar({ onNewList, theme, onToggleTheme, open = false, onClose }: SidebarProps) {
    const { auth, currentRouteName, sidebarLists } = usePage<SidebarSharedProps>().props;

    const isHome = currentRouteName === 'home';
    const isAllLists = currentRouteName === 'lists.index';
    const activeListId = useActiveListIdFromUrl();
    const isMobile = useIsMobile();

    const systemLists = (sidebarLists ?? []).filter((l) => l.type !== 'custom');
    const userLists = (sidebarLists ?? []).filter((l) => l.type === 'custom');

    const handleNewList = useCallback(() => {
        if (onNewList) {
            onNewList();
            return;
        }
        router.visit('/lists');
    }, [onNewList]);

    /* Lock the page behind the drawer while it is open, and close it on Escape. */
    useEffect(() => {
        if (!isMobile || !open) return;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose?.();
        };
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [isMobile, open, onClose]);

    const mobileShell: CSSProperties | null = isMobile
        ? {
              position: 'fixed',
              top: 0,
              left: 0,
              bottom: 0,
              zIndex: 400,
              width: 'min(300px, 84vw)',
              transform: open ? 'none' : 'translateX(-101%)',
              boxShadow: open ? 'var(--shadow-lg)' : 'none',
              transition: 'transform var(--dur-base) var(--ease-out)',
              overflowY: 'auto',
              paddingBottom: 'calc(18px + env(safe-area-inset-bottom))',
          }
        : null;

    const aside = (
        <aside
            aria-hidden={isMobile && !open ? true : undefined}
            style={{
                width: 'var(--sidebar-w)',
                flex: 'none',
                height: '100%',
                boxSizing: 'border-box',
                borderRight: '1px solid var(--line)',
                background: 'var(--surface)',
                display: 'flex',
                flexDirection: 'column',
                padding: '18px 14px',
                ...mobileShell,
            }}
        >
            <div
                style={{
                    padding: '4px 8px 18px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: 8,
                }}
            >
                <Wordmark size={19} />
                {isMobile && onClose && (
                    <IconButton
                        icon={X}
                        label="Close menu"
                        onClick={onClose}
                        size={20}
                        boxSize={44}
                        style={{ marginRight: -8 }}
                    />
                )}
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                <NavItem icon={LayoutDashboard} label="Home" href="/" active={isHome} />
                <NavItem icon={Library} label="All lists" href="/lists" active={isAllLists} />
            </div>

            {systemLists.length > 0 && (
                <div style={{ marginTop: 20 }}>
                    <div style={{ padding: '0 8px 8px' }}>
                        <Label>System</Label>
                    </div>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
                        {systemLists.map((list) => (
                            <ListItem
                                key={list.id}
                                list={list}
                                active={activeListId === list.id}
                            />
                        ))}
                    </div>
                </div>
            )}

            <div
                style={{
                    marginTop: 20,
                    flex: 1,
                    minHeight: 0,
                    display: 'flex',
                    flexDirection: 'column',
                }}
            >
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '0 8px 8px',
                    }}
                >
                    <Label>Your lists</Label>
                    <button
                        type="button"
                        aria-label="New list"
                        onClick={handleNewList}
                        title="New list"
                        style={{
                            border: 'none',
                            background: 'transparent',
                            color: 'var(--fg2)',
                            cursor: 'pointer',
                            display: 'inline-flex',
                            padding: 2,
                            borderRadius: 6,
                        }}
                    >
                        <Plus size={16} strokeWidth={2} />
                    </button>
                </div>
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 2,
                        overflowY: 'auto',
                    }}
                >
                    {userLists.map((list) => (
                        <ListItem key={list.id} list={list} active={activeListId === list.id} />
                    ))}
                    {userLists.length === 0 && (
                        <div style={{ padding: '8px 12px', fontSize: 13, color: 'var(--fg3)' }}>
                            No custom lists yet.
                        </div>
                    )}
                </div>
            </div>

            <ProfileMenu user={auth.user} theme={theme} onToggleTheme={onToggleTheme} />
        </aside>
    );

    if (!isMobile) return aside;

    return (
        <>
            <div
                onClick={onClose}
                style={{
                    position: 'fixed',
                    inset: 0,
                    zIndex: 399,
                    background: 'var(--overlay)',
                    opacity: open ? 1 : 0,
                    pointerEvents: open ? 'auto' : 'none',
                    transition: 'opacity var(--dur-base) var(--ease-out)',
                }}
            />
            {aside}
        </>
    );
}

function useActiveListIdFromUrl(): number | null {
    if (typeof window === 'undefined') return null;
    const match = window.location.pathname.match(/^\/lists\/(\d+)/);
    return match ? Number.parseInt(match[1], 10) : null;
}
