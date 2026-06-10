import { ButtonHTMLAttributes, ReactNode, useState } from 'react';
import { LucideIcon } from 'lucide-react';

interface ChipProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
    children: ReactNode;
    dot?: boolean;
    dotColor?: string;
    icon?: LucideIcon;
    on?: boolean;
}

export function Chip({ children, dot, dotColor, icon: Icon, on, style, onClick, ...rest }: ChipProps) {
    const [hover, setHover] = useState(false);
    const interactive = !!onClick;
    return (
        <button
            type="button"
            onClick={onClick}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                fontFamily: 'var(--font-mono)',
                fontSize: 11,
                letterSpacing: '0.06em',
                textTransform: 'uppercase',
                padding: '6px 11px',
                borderRadius: 999,
                cursor: interactive ? 'pointer' : 'default',
                border:
                    '1px solid ' +
                    (on
                        ? 'var(--accent)'
                        : hover && interactive
                            ? 'var(--line-ink)'
                            : 'var(--line-strong)'),
                background: on ? 'var(--accent-weak)' : 'transparent',
                color: on ? 'var(--accent)' : 'var(--fg2)',
                display: 'inline-flex',
                gap: 7,
                alignItems: 'center',
                transition: 'all var(--dur-fast) var(--ease-out)',
                ...style,
            }}
            {...rest}
        >
            {dot && (
                <span
                    style={{
                        width: 8,
                        height: 8,
                        borderRadius: '50%',
                        background: dotColor || 'var(--accent)',
                        flex: 'none',
                    }}
                />
            )}
            {Icon && <Icon size={13} strokeWidth={2} />}
            {children}
        </button>
    );
}
