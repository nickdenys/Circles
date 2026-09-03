import { usePage } from '@inertiajs/react';

interface LogomarkProps {
    size?: number;
    className?: string;
}

export function Logomark({ size = 28, className }: LogomarkProps) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 120 120"
            fill="none"
            className={className}
            style={{ flex: 'none' }}
        >
            <circle cx="60" cy="60" r="46" stroke="var(--accent)" strokeWidth="13" />
            <circle cx="60" cy="60" r="27" stroke="var(--accent)" strokeWidth="3" opacity="0.45" />
            <circle cx="60" cy="60" r="9" fill="var(--accent)" />
        </svg>
    );
}

interface WordmarkProps {
    size?: number;
    invert?: boolean;
}

export function Wordmark({ size = 22, invert = false }: WordmarkProps) {
    const appName = usePage().props.appName as string;

    return (
        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
            <Logomark size={Math.round(size * 1.25)} />
            <span
                style={{
                    fontFamily: 'var(--font-display)',
                    fontWeight: 800,
                    fontSize: size,
                    letterSpacing: '-0.03em',
                    color: invert ? 'var(--warm-25)' : 'var(--fg1)',
                }}
            >
                {appName}
            </span>
            <span
                style={{
                    fontFamily: 'var(--font-mono)',
                    fontSize: size * 0.4,
                    color: 'var(--accent)',
                    alignSelf: 'flex-start',
                    marginTop: 2,
                }}
            >
                ®
            </span>
        </span>
    );
}
