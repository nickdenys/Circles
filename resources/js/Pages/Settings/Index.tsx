import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    CheckCircle,
    Code2,
    Copy,
    ExternalLink,
    LucideIcon,
    Trash2,
    User as UserIcon,
    X,
} from 'lucide-react';
import { CSSProperties, FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/circles/Button';
import { Label } from '@/components/circles/Label';
import { TopBar } from '@/components/circles/TopBar';

interface Token {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
}

interface AuthUser {
    id: number;
    name: string;
    avatar: string | null;
}

interface SettingsProps {
    tokens: Token[];
    auth: { user: AuthUser };
    flash: {
        token: string | null;
    };
    [key: string]: unknown;
}

type SectionId = 'account' | 'developer';

const SECTIONS: { id: SectionId; label: string; icon: LucideIcon; index: number }[] = [
    { id: 'account', label: 'Account', icon: UserIcon, index: 1 },
    { id: 'developer', label: 'Developer', icon: Code2, index: 2 },
];

function NavItem({
    label,
    icon: Icon,
    active,
    onClick,
}: {
    label: string;
    icon: LucideIcon;
    active: boolean;
    onClick: () => void;
}) {
    const [hover, setHover] = useState(false);
    return (
        <button
            type="button"
            onClick={onClick}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                display: 'flex',
                alignItems: 'center',
                gap: 10,
                width: '100%',
                padding: '9px 12px',
                borderRadius: 10,
                border: 'none',
                cursor: 'pointer',
                background: active ? 'var(--accent-weak)' : hover ? 'var(--surface-3)' : 'transparent',
                color: active ? 'var(--accent)' : 'var(--fg1)',
                fontFamily: 'var(--font-sans)',
                fontSize: 14,
                fontWeight: active ? 600 : 500,
                textAlign: 'left',
                transition: 'all var(--dur-fast) var(--ease-out)',
                position: 'relative',
                boxSizing: 'border-box',
            }}
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
            <Icon
                size={16}
                strokeWidth={2}
                style={{ color: active ? 'var(--accent)' : 'var(--fg2)', flex: 'none' }}
            />
            <span style={{ flex: 1 }}>{label}</span>
        </button>
    );
}

function SectionHeader({ index, title }: { index: number; title: string }) {
    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                gap: 14,
                paddingBottom: 20,
                marginBottom: 24,
                borderBottom: '1px solid var(--line)',
            }}
        >
            <Label>
                {String(index).padStart(2, '0')} / {title.toUpperCase()}
            </Label>
        </div>
    );
}

function SettingsCard({
    title,
    description,
    children,
    footer,
}: {
    title: string;
    description?: string;
    children?: React.ReactNode;
    footer?: React.ReactNode;
}) {
    return (
        <div
            style={{
                border: '1px solid var(--line)',
                borderRadius: 'var(--radius-md)',
                background: 'var(--surface)',
                overflow: 'hidden',
            }}
        >
            <div style={{ padding: '20px 24px' }}>
                <div style={{ fontSize: 15, fontWeight: 600, color: 'var(--fg1)' }}>{title}</div>
                {description && (
                    <div
                        style={{
                            fontSize: 13.5,
                            color: 'var(--fg2)',
                            lineHeight: 1.6,
                            marginTop: 5,
                        }}
                    >
                        {description}
                    </div>
                )}
                {children && <div style={{ marginTop: 16 }}>{children}</div>}
            </div>
            {footer && (
                <div
                    style={{
                        padding: '14px 24px',
                        background: 'var(--surface-2)',
                        borderTop: '1px solid var(--line)',
                    }}
                >
                    {footer}
                </div>
            )}
        </div>
    );
}

const inputStyle: CSSProperties = {
    padding: '9px 13px',
    borderRadius: 'var(--radius-sm)',
    border: '1.5px solid var(--line-strong)',
    background: 'var(--bg)',
    color: 'var(--fg1)',
    fontFamily: 'var(--font-sans)',
    fontSize: 14,
    outline: 'none',
    transition: 'border-color var(--dur-fast)',
    width: '100%',
    boxSizing: 'border-box',
};

function TokenRow({ token, onDelete, isLast }: { token: Token; onDelete: (id: number) => void; isLast: boolean }) {
    const [hover, setHover] = useState(false);
    return (
        <div
            style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '13px 0',
                gap: 16,
                borderBottom: isLast ? 'none' : '1px solid var(--line)',
            }}
        >
            <div>
                <div style={{ fontSize: 14, fontWeight: 600, marginBottom: 3 }}>{token.name}</div>
                <Label style={{ fontSize: 10 }}>
                    Created {token.created_at}
                    {token.last_used_at && ` · Last used ${token.last_used_at}`}
                </Label>
            </div>
            <button
                type="button"
                onClick={() => onDelete(token.id)}
                onMouseEnter={() => setHover(true)}
                onMouseLeave={() => setHover(false)}
                title="Revoke token"
                aria-label={`Revoke ${token.name}`}
                style={{
                    width: 36,
                    height: 36,
                    borderRadius: 8,
                    border: 'none',
                    cursor: 'pointer',
                    flex: 'none',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: hover ? 'rgba(218,59,42,0.10)' : 'transparent',
                    color: hover ? 'var(--critical)' : 'var(--fg3)',
                    transition: 'all var(--dur-fast) var(--ease-out)',
                }}
            >
                <Trash2 size={15} strokeWidth={2} />
            </button>
        </div>
    );
}

function TokenReveal({ token, onDismiss }: { token: string; onDismiss: () => void }) {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        navigator.clipboard.writeText(token);
        setCopied(true);
        toast.success('Token copied.');
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <div
            style={{
                marginTop: 14,
                padding: '14px 16px',
                background: 'var(--accent-weak)',
                border: '1px solid var(--accent)',
                borderRadius: 'var(--radius-sm)',
                display: 'flex',
                flexDirection: 'column',
                gap: 10,
                animation: 'fadeSlideUp var(--dur-base) var(--ease-out) both',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                }}
            >
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <CheckCircle
                        size={14}
                        strokeWidth={2}
                        style={{ color: 'var(--accent)', flex: 'none' }}
                    />
                    <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--accent)' }}>
                        Copy now, it won't be shown again.
                    </span>
                </div>
                <button
                    type="button"
                    onClick={onDismiss}
                    style={{
                        border: 'none',
                        background: 'transparent',
                        cursor: 'pointer',
                        color: 'var(--fg3)',
                        padding: 2,
                        display: 'inline-flex',
                        borderRadius: 4,
                    }}
                >
                    <X size={14} strokeWidth={2} />
                </button>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                <code
                    style={{
                        flex: 1,
                        fontFamily: 'var(--font-mono)',
                        fontSize: 12,
                        letterSpacing: '0.04em',
                        padding: '8px 12px',
                        background: 'var(--surface)',
                        borderRadius: 6,
                        border: '1px solid var(--line-strong)',
                        wordBreak: 'break-all',
                        color: 'var(--fg1)',
                        display: 'block',
                    }}
                >
                    {token}
                </code>
                <button
                    type="button"
                    onClick={handleCopy}
                    title="Copy to clipboard"
                    style={{
                        border: 'none',
                        background: 'transparent',
                        cursor: 'pointer',
                        color: 'var(--fg2)',
                        display: 'inline-flex',
                        padding: 6,
                        borderRadius: 6,
                        flex: 'none',
                    }}
                >
                    <Copy size={16} strokeWidth={2} />
                </button>
            </div>
            {copied && <Label style={{ fontSize: 10 }}>Copied.</Label>}
        </div>
    );
}

export default function Index() {
    const { tokens, flash, auth } = usePage<SettingsProps>().props;
    const [activeSection, setActiveSection] = useState<SectionId>('account');
    const [revealedToken, setRevealedToken] = useState<string | null>(null);
    const [displayName, setDisplayName] = useState(auth.user.name);

    useEffect(() => {
        if (flash?.token) {
            setRevealedToken(flash.token);
        }
    }, [flash?.token]);

    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    function handleCreateToken(e: FormEvent) {
        e.preventDefault();
        post('/settings/tokens', {
            onSuccess: () => {
                reset();
                toast.success(`Token "${data.name}" created.`);
            },
        });
    }

    const handleDelete = useCallback(
        (tokenId: number) => {
            const target = tokens.find((token) => token.id === tokenId);
            router.delete(`/settings/tokens/${tokenId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    if (target) toast.success(`Token "${target.name}" revoked.`);
                },
            });
        },
        [tokens],
    );

    const mcpConfig = useMemo(
        () =>
            JSON.stringify(
                {
                    mcpServers: {
                        circles: {
                            command: 'npx',
                            args: [
                                '-y',
                                'mcp-remote',
                                `${typeof window !== 'undefined' ? window.location.origin : ''}/mcp`,
                                '--header',
                                `Authorization: Bearer ${revealedToken ?? '<token>'}`,
                            ],
                            env: {
                                NODE_TLS_REJECT_UNAUTHORIZED: '0',
                            },
                        },
                    },
                },
                null,
                2,
            ),
        [revealedToken],
    );

    return (
        <>
            <Head title="Settings" />

            <TopBar crumbs={['Account', 'Settings']} />

            <div style={{ display: 'flex', flex: 1, minHeight: 0, overflow: 'hidden' }}>
                <nav
                    style={{
                        width: 216,
                        flex: 'none',
                        display: 'flex',
                        flexDirection: 'column',
                        borderRight: '1px solid var(--line)',
                        padding: '28px 10px',
                        gap: 2,
                        boxSizing: 'border-box',
                        background: 'var(--bg)',
                    }}
                >
                    <div style={{ padding: '0 10px 14px' }}>
                        <Label style={{ fontSize: 10 }}>Settings</Label>
                    </div>
                    {SECTIONS.map((section) => (
                        <NavItem
                            key={section.id}
                            label={section.label}
                            icon={section.icon}
                            active={activeSection === section.id}
                            onClick={() => setActiveSection(section.id)}
                        />
                    ))}
                </nav>

                <div style={{ flex: 1, overflowY: 'auto', padding: '40px 52px 80px' }}>
                    <div
                        key={activeSection}
                        style={{
                            maxWidth: 660,
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 24,
                            animation: 'fadeSlideUp var(--dur-base) var(--ease-out) both',
                        }}
                    >
                        {activeSection === 'account' && (
                            <section>
                                <SectionHeader index={1} title="Account" />
                                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                    <SettingsCard
                                        title="Display name"
                                        description="This is how your name appears across Circles. It comes from your Spotify account."
                                    >
                                        <div
                                            style={{
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: 7,
                                            }}
                                        >
                                            <label
                                                style={{
                                                    fontSize: 12.5,
                                                    fontWeight: 500,
                                                    color: 'var(--fg2)',
                                                }}
                                            >
                                                Name
                                            </label>
                                            <input
                                                value={displayName}
                                                onChange={(event) => setDisplayName(event.target.value)}
                                                placeholder="Your display name"
                                                readOnly
                                                style={inputStyle}
                                            />
                                        </div>
                                    </SettingsCard>

                                    <SettingsCard
                                        title="Spotify connection"
                                        description="Reconnect your Spotify account to grant updated permissions (for example, playlist creation)."
                                        footer={
                                            <a
                                                href="/auth/spotify/reconnect"
                                                style={{ textDecoration: 'none', display: 'inline-block' }}
                                            >
                                                <Button variant="secondary" icon={ExternalLink} size="sm">
                                                    Reconnect Spotify
                                                </Button>
                                            </a>
                                        }
                                    />
                                </div>
                            </section>
                        )}

                        {activeSection === 'developer' && (
                            <section>
                                <SectionHeader index={2} title="Developer" />
                                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                                    <SettingsCard
                                        title="Create API token"
                                        description="Generate a token to access Circles via the MCP API or external integrations."
                                    >
                                        <form
                                            onSubmit={handleCreateToken}
                                            style={{
                                                display: 'flex',
                                                gap: 10,
                                                alignItems: 'flex-end',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    flex: 1,
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: 7,
                                                }}
                                            >
                                                <label
                                                    htmlFor="token-name"
                                                    style={{
                                                        fontSize: 12.5,
                                                        fontWeight: 500,
                                                        color: 'var(--fg2)',
                                                    }}
                                                >
                                                    Token name
                                                </label>
                                                <input
                                                    id="token-name"
                                                    value={data.name}
                                                    onChange={(event) =>
                                                        setData('name', event.target.value)
                                                    }
                                                    placeholder="e.g. Claude Desktop"
                                                    aria-invalid={!!errors.name}
                                                    style={inputStyle}
                                                />
                                                {errors.name && (
                                                    <span
                                                        style={{
                                                            fontSize: 12,
                                                            color: 'var(--critical)',
                                                        }}
                                                    >
                                                        {errors.name}
                                                    </span>
                                                )}
                                            </div>
                                            <Button type="submit" variant="primary" disabled={processing}>
                                                Create token
                                            </Button>
                                        </form>

                                        {revealedToken && (
                                            <TokenReveal
                                                token={revealedToken}
                                                onDismiss={() => setRevealedToken(null)}
                                            />
                                        )}

                                        {revealedToken && (
                                            <div style={{ marginTop: 18 }}>
                                                <Label style={{ display: 'block', marginBottom: 8 }}>
                                                    Claude Desktop config
                                                </Label>
                                                <pre
                                                    style={{
                                                        fontFamily: 'var(--font-mono)',
                                                        fontSize: 12,
                                                        padding: '12px 14px',
                                                        background: 'var(--surface-2)',
                                                        border: '1px solid var(--line)',
                                                        borderRadius: 8,
                                                        overflow: 'auto',
                                                        margin: 0,
                                                        color: 'var(--fg1)',
                                                    }}
                                                >
                                                    {mcpConfig}
                                                </pre>
                                            </div>
                                        )}
                                    </SettingsCard>

                                    <div
                                        style={{
                                            border: '1px solid var(--line)',
                                            borderRadius: 'var(--radius-md)',
                                            background: 'var(--surface)',
                                            overflow: 'hidden',
                                        }}
                                    >
                                        <div
                                            style={{
                                                padding: '16px 24px',
                                                borderBottom: '1px solid var(--line)',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'space-between',
                                            }}
                                        >
                                            <span style={{ fontSize: 15, fontWeight: 600 }}>
                                                API tokens
                                            </span>
                                            {tokens.length > 0 && (
                                                <Label style={{ fontSize: 10 }}>
                                                    {tokens.length} active
                                                </Label>
                                            )}
                                        </div>
                                        {tokens.length === 0 ? (
                                            <div
                                                style={{
                                                    padding: '20px 24px',
                                                    color: 'var(--fg3)',
                                                    fontSize: 14,
                                                }}
                                            >
                                                No tokens yet. Create one above.
                                            </div>
                                        ) : (
                                            <div style={{ padding: '0 24px' }}>
                                                {tokens.map((token, index) => (
                                                    <TokenRow
                                                        key={token.id}
                                                        token={token}
                                                        onDelete={handleDelete}
                                                        isLast={index === tokens.length - 1}
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </section>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
