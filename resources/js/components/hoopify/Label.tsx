import { CSSProperties, ReactNode } from 'react';

interface LabelProps {
    children: ReactNode;
    accent?: boolean;
    ink?: boolean;
    style?: CSSProperties;
    className?: string;
}

export function Label({ children, accent, ink, style, className }: LabelProps) {
    return (
        <span
            className={className}
            style={{
                fontFamily: 'var(--font-mono)',
                fontSize: 11,
                letterSpacing: '0.12em',
                textTransform: 'uppercase',
                color: accent ? 'var(--accent)' : ink ? 'var(--fg1)' : 'var(--fg3)',
                ...style,
            }}
        >
            {children}
        </span>
    );
}
