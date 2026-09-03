import { ButtonHTMLAttributes, CSSProperties, forwardRef, ReactNode, useState } from 'react';
import { LucideIcon } from 'lucide-react';

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'ink' | 'accentOutline' | 'danger';
export type ButtonSize = 'sm' | 'md' | 'lg';

interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children'> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    icon?: LucideIcon;
    iconRight?: LucideIcon;
    children?: ReactNode;
    fullWidth?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
    {
        variant = 'primary',
        size = 'md',
        icon: Icon,
        iconRight: IconRight,
        children,
        style,
        disabled,
        fullWidth,
        ...rest
    },
    ref,
) {
    const [hover, setHover] = useState(false);
    const [press, setPress] = useState(false);

    const pads = size === 'sm' ? '8px 12px' : size === 'lg' ? '13px 22px' : '10px 16px';
    const fs = size === 'sm' ? 13 : size === 'lg' ? 16 : 14;
    const iconSize = fs + 3;

    const variants: Record<ButtonVariant, CSSProperties> = {
        primary: {
            background: hover ? 'var(--accent-hover)' : 'var(--accent)',
            color: 'var(--accent-on)',
            borderColor: 'transparent',
        },
        secondary: {
            background: 'var(--surface)',
            color: 'var(--fg1)',
            borderColor: hover ? 'var(--line-ink)' : 'var(--line-strong)',
        },
        ghost: {
            background: hover ? 'var(--surface-3)' : 'transparent',
            color: 'var(--fg1)',
            borderColor: 'transparent',
        },
        ink: {
            background: hover ? 'var(--warm-700)' : 'var(--fg1)',
            color: 'var(--bg)',
            borderColor: 'transparent',
        },
        accentOutline: {
            background: hover ? 'var(--accent-weak)' : 'transparent',
            color: 'var(--accent)',
            borderColor: 'var(--accent)',
        },
        danger: {
            background: hover ? 'rgba(218,59,42,0.10)' : 'transparent',
            color: 'var(--critical)',
            borderColor: hover ? 'var(--critical)' : 'var(--line-strong)',
        },
    };

    return (
        <button
            ref={ref}
            disabled={disabled}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => {
                setHover(false);
                setPress(false);
            }}
            onMouseDown={() => setPress(true)}
            onMouseUp={() => setPress(false)}
            style={{
                fontFamily: 'var(--font-sans)',
                fontWeight: 600,
                fontSize: fs,
                padding: pads,
                borderRadius: 10,
                border: '1.5px solid transparent',
                cursor: disabled ? 'not-allowed' : 'pointer',
                display: fullWidth ? 'flex' : 'inline-flex',
                width: fullWidth ? '100%' : undefined,
                gap: 8,
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all var(--dur-fast) var(--ease-out)',
                whiteSpace: 'nowrap',
                transform: press ? 'scale(0.97)' : 'none',
                lineHeight: 1.1,
                opacity: disabled ? 0.5 : 1,
                ...variants[variant],
                ...style,
            }}
            {...rest}
        >
            {Icon && <Icon size={iconSize} strokeWidth={2} />}
            {children}
            {IconRight && <IconRight size={iconSize} strokeWidth={2} />}
        </button>
    );
});
