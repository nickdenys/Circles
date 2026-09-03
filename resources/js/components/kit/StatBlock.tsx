import { useCountUp } from '@/hooks/use-count-up';
import { Label } from './Label';

interface StatBlockProps {
    value: string | number;
    unit?: string;
    caption: string;
    size?: number;
    align?: 'flex-start' | 'center' | 'flex-end';
    animate?: boolean;
    decimals?: number;
}

export function StatBlock({
    value,
    unit = '',
    caption,
    size = 26,
    align = 'flex-start',
    animate = true,
    decimals,
}: StatBlockProps) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4, alignItems: align }}>
            <span
                style={{
                    fontFamily: 'var(--font-mono)',
                    fontWeight: 700,
                    fontSize: size,
                    color: 'var(--fg1)',
                    lineHeight: 1,
                    letterSpacing: '-0.01em',
                }}
            >
                <StatValue value={value} animate={animate} decimals={decimals} />
                {unit && (
                    <span style={{ fontSize: Math.round(size * 0.5), color: 'var(--fg3)', marginLeft: 6 }}>
                        {unit}
                    </span>
                )}
            </span>
            <Label style={{ fontSize: 10 }}>{caption}</Label>
        </div>
    );
}

function StatValue({
    value,
    animate,
    decimals,
}: {
    value: string | number;
    animate: boolean;
    decimals?: number;
}) {
    if (!animate) {
        return <>{value}</>;
    }

    const numeric = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(numeric)) {
        return <>{value}</>;
    }

    const inferredDecimals =
        decimals ?? (typeof value === 'string' && value.includes('.') ? value.split('.')[1].length : 0);

    return <AnimatedNumber target={numeric} decimals={inferredDecimals} />;
}

function AnimatedNumber({ target, decimals }: { target: number; decimals: number }) {
    const display = useCountUp(target, { decimals, dur: 350 });
    return <>{display}</>;
}
