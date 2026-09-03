import { Link, usePage } from '@inertiajs/react';
import { FlaskConical, ShieldCheck } from 'lucide-react';
import { Label } from '@/components/kit/Label';
import { Logomark } from '@/components/kit/Logomark';
import { PlaceholderCover } from '@/components/kit/PlaceholderCover';

interface LoginProps {
    appName: string;
    /** Only set in the local environment. */
    devLoginUrl: string | null;
    flash: {
        error: string | null;
    };
    [key: string]: unknown;
}

const COVER_SEEDS = [
    'Daft Punk Discovery',
    'Frank Ocean Blonde',
    'Radiohead Kid A',
    'Björk Homogenic',
    'Aphex Twin SAW',
    'Burial Untrue',
    'Portishead Dummy',
    'Massive Attack Mezzanine',
    'Boards of Canada',
    'Four Tet Rounds',
    'Caribou Swim',
    'Bonobo Migration',
    'Nils Frahm Spaces',
    'Floating Points',
    'Jamie xx Colour',
    'Tycho Dive',
    'Khruangbin',
    'Tame Impala Currents',
    'Mac DeMarco Salad',
    'Beach House Bloom',
    'Aphex Drukqs',
    'Flying Lotus',
    'Thom Yorke Anima',
    'SBTRKT',
];

function Corner({ position }: { position: 'tl' | 'tr' | 'bl' | 'br' }) {
    const base = {
        position: 'absolute' as const,
        width: 13,
        height: 13,
        pointerEvents: 'none' as const,
    };
    const border = '1.5px solid rgba(255,255,255,0.16)';
    if (position === 'tl') {
        return <span style={{ ...base, top: 30, left: 30, borderTop: border, borderLeft: border }} />;
    }
    if (position === 'tr') {
        return <span style={{ ...base, top: 30, right: 30, borderTop: border, borderRight: border }} />;
    }
    if (position === 'bl') {
        return <span style={{ ...base, bottom: 30, left: 30, borderBottom: border, borderLeft: border }} />;
    }
    return <span style={{ ...base, bottom: 30, right: 30, borderBottom: border, borderRight: border }} />;
}

export default function Login() {
    const { appName, devLoginUrl, flash } = usePage<LoginProps>().props;

    return (
        <div
            data-theme="dark"
            style={{
                position: 'relative',
                height: '100vh',
                width: '100vw',
                overflow: 'hidden',
                background: 'var(--warm-950)',
                color: 'var(--warm-25)',
                fontFamily: 'var(--font-sans)',
            }}
        >
            {/* Album cover wall — bleeds past the right edge. */}
            <div
                aria-hidden="true"
                style={{
                    position: 'absolute',
                    top: -24,
                    bottom: -24,
                    right: -12,
                    width: '58%',
                    display: 'grid',
                    gridTemplateColumns: 'repeat(4, 1fr)',
                    gridAutoRows: '1fr',
                    gap: 12,
                }}
            >
                {COVER_SEEDS.map((seed) => (
                    <div key={seed} style={{ overflow: 'hidden', borderRadius: 8 }}>
                        <PlaceholderCover seed={seed} />
                    </div>
                ))}
            </div>

            {/* Scrim + vignette blend the seam between content and the wall. */}
            <div
                aria-hidden="true"
                style={{
                    position: 'absolute',
                    inset: 0,
                    pointerEvents: 'none',
                    background:
                        'linear-gradient(90deg, var(--warm-950) 0%, var(--warm-950) 38%, rgba(14,12,9,0.88) 48%, rgba(14,12,9,0.32) 62%, rgba(14,12,9,0) 78%)',
                }}
            />
            <div
                aria-hidden="true"
                style={{
                    position: 'absolute',
                    inset: 0,
                    pointerEvents: 'none',
                    boxShadow: 'inset 0 0 200px 50px rgba(14,12,9,0.55)',
                }}
            />

            <Corner position="tl" />
            <Corner position="tr" />
            <Corner position="bl" />
            <Corner position="br" />

            {/* Brand row */}
            <div
                style={{
                    position: 'absolute',
                    top: 'clamp(36px, 4.4vw, 60px)',
                    left: 'clamp(36px, 4.4vw, 64px)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 9,
                    zIndex: 4,
                }}
            >
                <Logomark size={32} />
                <span
                    style={{
                        fontFamily: 'var(--font-display)',
                        fontWeight: 800,
                        fontSize: 22,
                        letterSpacing: '-0.03em',
                        color: 'var(--warm-25)',
                    }}
                >
                    {appName}
                </span>
                <span
                    style={{
                        fontFamily: 'var(--font-mono)',
                        fontSize: 9,
                        color: 'var(--accent)',
                        alignSelf: 'flex-start',
                        marginTop: 3,
                        letterSpacing: '0.14em',
                    }}
                >
                    ®
                </span>
            </div>

            {/* Content column */}
            <div
                style={{
                    position: 'relative',
                    zIndex: 3,
                    height: '100%',
                    display: 'flex',
                    flexDirection: 'column',
                    justifyContent: 'center',
                    padding: 'clamp(36px, 4.4vw, 60px) clamp(36px, 4.4vw, 64px)',
                    width: 'min(56%, 660px)',
                    boxSizing: 'border-box',
                }}
            >
                <Label
                    accent
                    style={{
                        display: 'block',
                        marginBottom: 20,
                        fontSize: 11,
                    }}
                >
                    EST. 2026 · THE ALBUM ARCHIVE
                </Label>

                <h1
                    style={{
                        fontFamily: 'var(--font-display)',
                        fontWeight: 800,
                        fontSize: 'clamp(46px, 5.6vw, 78px)',
                        lineHeight: 0.97,
                        letterSpacing: '-0.035em',
                        margin: 0,
                        textWrap: 'balance',
                        maxWidth: '11ch',
                        color: 'var(--warm-25)',
                    }}
                >
                    Your music, finally filed.
                </h1>

                <p
                    style={{
                        fontSize: 'clamp(15px, 1.2vw, 17px)',
                        lineHeight: 1.6,
                        color: 'var(--warm-300)',
                        margin: '24px 0 34px',
                        maxWidth: '42ch',
                    }}
                >
                    Group the albums you love into lists that actually make sense. Log in with Spotify, that's the only way in.
                </p>

                {flash?.error && (
                    <div
                        role="alert"
                        style={{
                            marginBottom: 22,
                            padding: '12px 16px',
                            borderRadius: 10,
                            background: 'rgba(218,59,42,0.12)',
                            border: '1px solid rgba(218,59,42,0.45)',
                            color: '#ffd6d1',
                            fontSize: 14,
                            maxWidth: 480,
                        }}
                    >
                        {flash.error}
                    </div>
                )}

                <a
                    href="/auth/spotify/redirect"
                    style={{
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: 12,
                        padding: '17px 28px',
                        background: '#1DB954',
                        color: '#06250f',
                        border: 'none',
                        borderRadius: 999,
                        fontFamily: 'var(--font-sans)',
                        fontWeight: 700,
                        fontSize: 17,
                        cursor: 'pointer',
                        textDecoration: 'none',
                        whiteSpace: 'nowrap',
                        width: 'fit-content',
                        boxShadow: 'var(--shadow-sm)',
                        transition:
                            'transform var(--dur-fast) var(--ease-out), background var(--dur-fast) var(--ease-out), box-shadow var(--dur-fast) var(--ease-out)',
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.background = '#1ed760';
                        e.currentTarget.style.transform = 'translateY(-1px)';
                        e.currentTarget.style.boxShadow = 'var(--shadow-md)';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.background = '#1DB954';
                        e.currentTarget.style.transform = 'none';
                        e.currentTarget.style.boxShadow = 'var(--shadow-sm)';
                    }}
                >
                    <span
                        style={{
                            width: 24,
                            height: 24,
                            borderRadius: '50%',
                            background: '#06250f',
                            display: 'inline-flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            flex: 'none',
                        }}
                    >
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#1DB954"
                            strokeWidth={2.4}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        >
                            <path d="M9 18V5l12-2v13" />
                            <circle cx="6" cy="18" r="3" />
                            <circle cx="18" cy="16" r="3" />
                        </svg>
                    </span>
                    Continue with Spotify
                </a>

                {devLoginUrl && (
                    <Link
                        href={devLoginUrl}
                        method="post"
                        as="button"
                        type="button"
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: 9,
                            marginTop: 14,
                            padding: '11px 20px',
                            background: 'transparent',
                            color: 'var(--warm-300)',
                            border: '1px dashed rgba(255,255,255,0.22)',
                            borderRadius: 999,
                            fontFamily: 'var(--font-mono)',
                            fontSize: 12,
                            letterSpacing: '0.08em',
                            textTransform: 'uppercase',
                            cursor: 'pointer',
                            width: 'fit-content',
                            transition: 'color var(--dur-fast) var(--ease-out), border-color var(--dur-fast) var(--ease-out)',
                        }}
                        onMouseEnter={(e) => {
                            e.currentTarget.style.color = 'var(--warm-25)';
                            e.currentTarget.style.borderColor = 'rgba(255,255,255,0.45)';
                        }}
                        onMouseLeave={(e) => {
                            e.currentTarget.style.color = 'var(--warm-300)';
                            e.currentTarget.style.borderColor = 'rgba(255,255,255,0.22)';
                        }}
                    >
                        <FlaskConical size={14} strokeWidth={2} />
                        Skip login (dev only)
                    </Link>
                )}

                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 8,
                        marginTop: 22,
                        color: 'var(--warm-400)',
                        fontSize: 12.5,
                    }}
                >
                    <ShieldCheck size={15} strokeWidth={2} />
                    <span>We never post to your account. Read-only by default.</span>
                </div>
            </div>
        </div>
    );
}
