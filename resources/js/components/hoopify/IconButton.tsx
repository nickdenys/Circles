import { ButtonHTMLAttributes, CSSProperties, forwardRef, useState } from 'react';
import { LucideIcon } from 'lucide-react';

interface IconButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
    icon: LucideIcon;
    label: string;
    size?: number;
    active?: boolean;
    danger?: boolean;
    boxSize?: number;
}

export const IconButton = forwardRef<HTMLButtonElement, IconButtonProps>(function IconButton(
    { icon: Icon, label, size = 20, active, danger, boxSize = 40, style, ...rest },
    ref,
) {
    const [hover, setHover] = useState(false);

    const computedStyle: CSSProperties = {
        width: boxSize,
        height: boxSize,
        borderRadius: 10,
        border: 'none',
        cursor: 'pointer',
        flex: 'none',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        transition: 'all var(--dur-fast) var(--ease-out)',
        background: active
            ? 'var(--accent-weak)'
            : danger && hover
                ? 'rgba(218,59,42,0.10)'
                : hover
                    ? 'var(--surface-3)'
                    : 'transparent',
        color: active
            ? 'var(--accent)'
            : danger
                ? hover
                    ? 'var(--critical)'
                    : 'var(--fg3)'
                : hover
                    ? 'var(--fg1)'
                    : 'var(--fg2)',
        ...style,
    };

    return (
        <button
            ref={ref}
            aria-label={label}
            title={label}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={computedStyle}
            {...rest}
        >
            <Icon size={size} strokeWidth={2} />
        </button>
    );
});
