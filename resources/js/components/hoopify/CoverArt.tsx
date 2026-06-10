import { Music } from 'lucide-react';
import { CSSProperties } from 'react';

interface MiniCoverProps {
    src?: string | null;
    alt?: string;
    size: number | string;
    radius?: number;
    style?: CSSProperties;
}

export function MiniCover({ src, alt = '', size, radius = 6, style }: MiniCoverProps) {
    const dims = typeof size === 'number' ? `${size}px` : size;
    if (!src) {
        return (
            <span
                aria-hidden="true"
                style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    width: dims,
                    height: dims,
                    borderRadius: radius,
                    background: 'var(--surface-2)',
                    color: 'var(--fg3)',
                    flex: 'none',
                    ...style,
                }}
            >
                <Music size={Math.max(12, Number.parseInt(String(size), 10) * 0.32) || 14} />
            </span>
        );
    }
    return (
        <img
            src={src}
            alt={alt}
            style={{
                display: 'block',
                width: dims,
                height: dims,
                borderRadius: radius,
                objectFit: 'cover',
                background: 'var(--surface-2)',
                flex: 'none',
                ...style,
            }}
        />
    );
}

interface CoverMosaicProps {
    covers: (string | null | undefined)[];
    size?: number;
    gap?: number;
    radius?: number;
    inner?: number;
    hard?: boolean;
    style?: CSSProperties;
}

export function CoverMosaic({
    covers,
    size = 208,
    gap = 3,
    radius = 14,
    inner = 0,
    hard = false,
    style,
}: CoverMosaicProps) {
    const four = [...covers].slice(0, 4);
    while (four.length < 4) {
        four.push(four[four.length - 1] ?? null);
    }
    return (
        <div
            style={{
                display: 'grid',
                gridTemplateColumns: '1fr 1fr',
                gap,
                width: size,
                height: size,
                padding: gap,
                background: 'var(--line-ink)',
                borderRadius: radius,
                overflow: 'hidden',
                flex: 'none',
                boxShadow: hard ? 'var(--shadow-hard)' : 'none',
                ...style,
            }}
        >
            {four.map((src, i) => (
                <div key={i} style={{ overflow: 'hidden', borderRadius: inner }}>
                    <MiniCover src={src ?? undefined} size="100%" radius={0} style={{ width: '100%', height: '100%' }} />
                </div>
            ))}
        </div>
    );
}

interface CoverStackProps {
    covers: (string | null | undefined)[];
    size?: number;
    overlap?: number;
    radius?: number;
}

/**
 * Overlapping row of square covers used for compact list-card previews.
 */
export function CoverStack({ covers, size = 56, overlap = 0.42, radius = 7 }: CoverStackProps) {
    const trimmed = covers.slice(0, 4);
    const step = size * (1 - overlap);
    const width = trimmed.length === 0 ? size : step * (trimmed.length - 1) + size;
    return (
        <div style={{ position: 'relative', height: size, width, flex: 'none' }}>
            {trimmed.map((src, i) => (
                <span
                    key={i}
                    style={{
                        position: 'absolute',
                        left: i * step,
                        top: 0,
                        zIndex: i,
                        boxShadow: '0 0 0 2px var(--surface)',
                        borderRadius: radius,
                    }}
                >
                    <MiniCover src={src ?? undefined} size={size} radius={radius} />
                </span>
            ))}
        </div>
    );
}

interface CoverRowProps {
    covers: (string | null | undefined)[];
    count?: number;
    size?: number;
    gap?: number;
    radius?: number;
}

/**
 * Horizontal row of non-overlapping square covers. Used in the list-mode "contents" cell.
 */
export function CoverRow({ covers, count = 4, size = 52, gap = 6, radius = 10 }: CoverRowProps) {
    const slots = Array.from({ length: count }, (_, i) => covers[i] ?? null);
    return (
        <div style={{ display: 'flex', gap, flex: 'none' }}>
            {slots.map((src, i) => (
                <MiniCover
                    key={i}
                    src={src ?? undefined}
                    size={size}
                    radius={radius}
                    style={{ border: '1px solid var(--line)' }}
                />
            ))}
        </div>
    );
}

interface CoverSpineProps {
    covers: (string | null | undefined)[];
}

/**
 * Row of four flush square covers (no rounding) used on the lists overview cards.
 */
export function CoverSpine({ covers }: CoverSpineProps) {
    const four = [...covers].slice(0, 4);
    while (four.length < 4) {
        four.push(four[four.length - 1] ?? null);
    }
    return (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', background: 'var(--line)', gap: 1 }}>
            {four.map((src, i) => (
                <div key={i} style={{ aspectRatio: '1/1', overflow: 'hidden', background: 'var(--surface-2)' }}>
                    <MiniCover src={src ?? undefined} size="100%" radius={0} style={{ width: '100%', height: '100%' }} />
                </div>
            ))}
        </div>
    );
}
