import { Label } from './Label';

interface StatBlockProps {
    value: string | number;
    unit?: string;
    caption: string;
    size?: number;
    align?: 'flex-start' | 'center' | 'flex-end';
}

export function StatBlock({ value, unit = '', caption, size = 26, align = 'flex-start' }: StatBlockProps) {
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
                {value}
                {unit && (
                    <span style={{ fontSize: Math.round(size * 0.5), color: 'var(--fg3)', marginLeft: 3 }}>
                        {unit}
                    </span>
                )}
            </span>
            <Label style={{ fontSize: 10 }}>{caption}</Label>
        </div>
    );
}
