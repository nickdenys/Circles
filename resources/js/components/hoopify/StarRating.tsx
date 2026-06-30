import { CSSProperties, useId, useState } from 'react';

const STAR_PATH =
    'M12 2.6l2.82 5.72 6.31.92-4.57 4.45 1.08 6.29L12 17.02 6.36 19.99l1.08-6.29L2.87 9.24l6.31-.92z';

interface StarGlyphProps {
    frac: number;
    size: number;
    color: string;
    emptyColor: string;
}

function StarGlyph({ frac, size, color, emptyColor }: StarGlyphProps) {
    const gradientId = useId();
    const pct = Math.max(0, Math.min(1, frac)) * 100;

    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            style={{ display: 'block', flex: 'none' }}
            aria-hidden="true"
        >
            <defs>
                <linearGradient id={gradientId} x1="0" y1="0" x2="1" y2="0">
                    <stop offset={`${pct}%`} stopColor={color} />
                    <stop offset={`${pct}%`} stopColor={emptyColor} />
                </linearGradient>
            </defs>
            <path
                d={STAR_PATH}
                fill={`url(#${gradientId})`}
                stroke={frac > 0 ? color : emptyColor}
                strokeWidth="1.1"
                strokeLinejoin="round"
                style={{ transition: 'stroke var(--dur-fast) var(--ease-out)' }}
            />
        </svg>
    );
}

function halfButtonStyle(side: 'left' | 'right'): CSSProperties {
    const base: CSSProperties = {
        position: 'absolute',
        inset: 0,
        width: '50%',
        padding: 0,
        margin: 0,
        border: 'none',
        background: 'transparent',
        cursor: 'pointer',
    };
    return side === 'left' ? { ...base, right: '50%' } : { ...base, left: '50%' };
}

interface StarRatingProps {
    value?: number;
    onChange?: (value: number) => void;
    size?: number;
    gap?: number;
    readOnly?: boolean;
    color?: string;
}

/**
 * Half-step (0.5 to 5) star rating with hover preview and keyboard control.
 * Renders teal gradient-filled stars per the Hoopify review-card design.
 */
export function StarRating({
    value = 0,
    onChange,
    size = 30,
    gap = 6,
    readOnly = false,
    color = 'var(--accent)',
}: StarRatingProps) {
    const [hover, setHover] = useState<number | null>(null);
    const shown = hover ?? value;
    const emptyColor = 'var(--line-strong)';

    function fracFor(index: number): number {
        const remaining = shown - index;
        return remaining >= 1 ? 1 : remaining >= 0.5 ? 0.5 : 0;
    }

    function commit(next: number): void {
        if (!readOnly && onChange) {
            onChange(next);
        }
    }

    return (
        <span
            role={readOnly ? 'img' : 'slider'}
            aria-label="Rating"
            aria-valuenow={readOnly ? undefined : value}
            aria-valuemin={0.5}
            aria-valuemax={5}
            tabIndex={readOnly ? -1 : 0}
            onKeyDown={(event) => {
                if (readOnly) {
                    return;
                }
                if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    commit(Math.min(5, (value || 0) + 0.5));
                }
                if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
                    event.preventDefault();
                    commit(Math.max(0.5, (value || 0.5) - 0.5));
                }
            }}
            onMouseLeave={() => setHover(null)}
            style={{
                display: 'inline-flex',
                gap,
                outline: 'none',
                borderRadius: 8,
                lineHeight: 0,
                cursor: readOnly ? 'default' : 'pointer',
            }}
        >
            {Array.from({ length: 5 }).map((_, index) => (
                <span
                    key={index}
                    style={{
                        position: 'relative',
                        width: size,
                        height: size,
                        transition: 'transform var(--dur-fast) var(--ease-spring)',
                        transform:
                            !readOnly && hover != null && hover >= index + 0.5 ? 'scale(1.12)' : 'none',
                    }}
                >
                    <StarGlyph frac={fracFor(index)} size={size} color={color} emptyColor={emptyColor} />
                    {!readOnly && (
                        <>
                            <button
                                type="button"
                                aria-label={`Rate ${index + 0.5}`}
                                onClick={() => commit(index + 0.5)}
                                onMouseEnter={() => setHover(index + 0.5)}
                                style={halfButtonStyle('left')}
                            />
                            <button
                                type="button"
                                aria-label={`Rate ${index + 1}`}
                                onClick={() => commit(index + 1)}
                                onMouseEnter={() => setHover(index + 1)}
                                style={halfButtonStyle('right')}
                            />
                        </>
                    )}
                </span>
            ))}
        </span>
    );
}

interface ScoreReadoutProps {
    value?: number | null;
    size?: number;
    suffix?: boolean;
}

/**
 * Mono numeric echo of a star rating on the clean 0.5 to 5.0 scale.
 */
export function ScoreReadout({ value, size = 34, suffix = true }: ScoreReadoutProps) {
    const numeric = value ?? 0;
    const hasValue = numeric > 0;

    return (
        <span
            style={{
                fontFamily: 'var(--font-mono)',
                fontWeight: 700,
                fontSize: size,
                lineHeight: 1,
                letterSpacing: '-0.01em',
                color: hasValue ? 'var(--accent)' : 'var(--fg3)',
            }}
        >
            {hasValue ? numeric.toFixed(1) : '—'}
            {suffix && (
                <span style={{ fontSize: Math.round(size * 0.42), color: 'var(--fg3)', marginLeft: 2 }}>
                    / 5
                </span>
            )}
        </span>
    );
}
