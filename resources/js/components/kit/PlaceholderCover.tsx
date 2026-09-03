import { CSSProperties, ReactNode } from 'react';

/**
 * Deterministic geometric album-cover placeholder, ported from design/assets/cover-art.js.
 * Uses real React SVG nodes (never dangerouslySetInnerHTML) so the seed never enters the
 * markup as text, only as a hash that drives a fixed palette and motif.
 */

const FIELDS: ReadonlyArray<[string, string]> = [
    ['#0EA585', '#FFFFFF'],
    ['#16140F', '#18B98E'],
    ['#7C5CFF', '#FFE3D7'],
    ['#18A999', '#0E0C09'],
    ['#E0A124', '#16140F'],
    ['#E5557E', '#FBFAF7'],
    ['#3B8EEA', '#FFFFFF'],
    ['#6E9B3E', '#FBFAF7'],
    ['#FBFAF7', '#0EA585'],
    ['#24201A', '#18A999'],
];

type Motif = 'rings' | 'stripes' | 'grid' | 'arcs' | 'dots' | 'halftone';
const MOTIFS: readonly Motif[] = ['rings', 'stripes', 'grid', 'arcs', 'dots', 'halftone'];

function hash(value: string): number {
    let h = 2166136261;
    const str = String(value || 'untitled');
    for (let i = 0; i < str.length; i++) {
        h ^= str.charCodeAt(i);
        h = Math.imul(h, 16777619);
    }
    return h >>> 0;
}

function buildShapes(motif: Motif, s: number, fg: string, rot: number): ReactNode[] {
    const nodes: ReactNode[] = [];
    if (motif === 'rings') {
        let key = 0;
        for (let r = s * 0.62; r > s * 0.06; r -= s * 0.11) {
            nodes.push(
                <circle
                    key={`ring-${key++}`}
                    cx={s * 0.62}
                    cy={s * 0.38}
                    r={r}
                    fill="none"
                    stroke={fg}
                    strokeWidth={s * 0.035}
                    opacity={0.9}
                />,
            );
        }
        nodes.push(
            <circle key="ring-dot" cx={s * 0.62} cy={s * 0.38} r={s * 0.045} fill={fg} />,
        );
    } else if (motif === 'stripes') {
        let key = 0;
        for (let x = -s; x < s * 2; x += s * 0.16) {
            nodes.push(
                <rect
                    key={`stripe-${key++}`}
                    x={x}
                    y={-s}
                    width={s * 0.08}
                    height={s * 3}
                    fill={fg}
                    opacity={0.92}
                    transform={`rotate(${20 + rot * 8} ${s / 2} ${s / 2})`}
                />,
            );
        }
    } else if (motif === 'grid') {
        let key = 0;
        for (let gx = s * 0.12; gx < s; gx += s * 0.19) {
            for (let gy = s * 0.12; gy < s; gy += s * 0.19) {
                nodes.push(
                    <circle key={`grid-${key++}`} cx={gx} cy={gy} r={s * 0.045} fill={fg} />,
                );
            }
        }
    } else if (motif === 'arcs') {
        nodes.push(
            <path
                key="arc-1"
                d={`M0 ${s} A ${s} ${s} 0 0 1 ${s} 0`}
                fill="none"
                stroke={fg}
                strokeWidth={s * 0.05}
            />,
            <path
                key="arc-2"
                d={`M0 ${s} A ${s * 0.66} ${s * 0.66} 0 0 1 ${s * 0.66} ${s * 0.34}`}
                fill="none"
                stroke={fg}
                strokeWidth={s * 0.05}
                opacity={0.7}
            />,
            <path
                key="arc-3"
                d={`M0 ${s} A ${s * 0.33} ${s * 0.33} 0 0 1 ${s * 0.33} ${s * 0.66}`}
                fill="none"
                stroke={fg}
                strokeWidth={s * 0.05}
                opacity={0.5}
            />,
        );
    } else if (motif === 'dots') {
        nodes.push(
            <circle key="dot-1" cx={s * 0.66} cy={s * 0.34} r={s * 0.26} fill={fg} />,
            <circle key="dot-2" cx={s * 0.3} cy={s * 0.7} r={s * 0.14} fill={fg} opacity={0.6} />,
        );
    } else {
        let key = 0;
        for (let hx = s * 0.1; hx < s; hx += s * 0.16) {
            for (let hy = s * 0.1; hy < s; hy += s * 0.16) {
                const rr = s * 0.07 * ((hx + hy) / (s * 1.6));
                nodes.push(<circle key={`ht-${key++}`} cx={hx} cy={hy} r={rr} fill={fg} />);
            }
        }
    }
    return nodes;
}

interface PlaceholderCoverProps {
    seed: string;
    size?: number;
    style?: CSSProperties;
    className?: string;
}

export function PlaceholderCover({
    seed,
    size = 300,
    style,
    className,
}: PlaceholderCoverProps) {
    const h = hash(seed);
    const [bg, fg] = FIELDS[h % FIELDS.length];
    const motif = MOTIFS[(h >> 4) % MOTIFS.length];
    const rot = (h >> 8) % 4;

    return (
        <svg
            aria-hidden="true"
            className={className}
            xmlns="http://www.w3.org/2000/svg"
            viewBox={`0 0 ${size} ${size}`}
            width="100%"
            height="100%"
            preserveAspectRatio="xMidYMid slice"
            style={{ display: 'block', ...style }}
        >
            <rect width={size} height={size} fill={bg} />
            {buildShapes(motif, size, fg, rot)}
        </svg>
    );
}
