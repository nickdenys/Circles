import { Label } from './Label';

/**
 * Colour a score on the 0.5 to 5 scale: green from 3.5 up, amber for the
 * middling 2 to 3, red at 1.5 and below.
 */
export function scoreColor(value: number): string {
    if (value >= 3.5) {
        return 'var(--accent)';
    }
    if (value >= 2) {
        return 'var(--warning)';
    }
    return 'var(--critical)';
}

interface ScoreProps {
    value?: number | null;
    size?: number;
}

export function Score({ value, size = 15 }: ScoreProps) {
    if (value == null) {
        return <Label>—</Label>;
    }
    return (
        <span
            style={{
                fontFamily: 'var(--font-mono)',
                fontWeight: 700,
                fontSize: size,
                color: scoreColor(value),
                transition: 'color var(--dur-fast) var(--ease-out)',
            }}
        >
            {value.toFixed(1)}
        </span>
    );
}

interface DotRatingProps {
    value?: number;
    max?: number;
    onChange?: (next: number) => void;
    size?: number;
}

export function DotRating({ value = 0, max = 5, onChange, size = 16 }: DotRatingProps) {
    return (
        <span style={{ display: 'inline-flex', gap: 6 }}>
            {Array.from({ length: max }).map((_, i) => {
                const filled = i < value;
                return (
                    <button
                        type="button"
                        key={i}
                        aria-label={`Rate ${i + 1}`}
                        onClick={() => onChange?.(i + 1)}
                        style={{
                            width: size,
                            height: size,
                            borderRadius: '50%',
                            padding: 0,
                            cursor: onChange ? 'pointer' : 'default',
                            background: filled ? 'var(--accent)' : 'transparent',
                            border:
                                '1.5px solid ' + (filled ? 'var(--accent)' : 'var(--line-strong)'),
                            transition: 'all var(--dur-fast) var(--ease-out)',
                        }}
                    />
                );
            })}
        </span>
    );
}
