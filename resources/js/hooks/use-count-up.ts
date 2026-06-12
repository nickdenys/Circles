import { useEffect, useState } from 'react';

interface CountUpOptions {
    decimals?: number;
    dur?: number;
    from?: number;
}

export function useCountUp(target: number, options: CountUpOptions = {}): string {
    const { decimals = 0, dur = 900, from = 0 } = options;

    const [value, setValue] = useState(() => {
        if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return target;
        }
        return from;
    });

    useEffect(() => {
        if (typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setValue(target);
            return;
        }
        let raf = 0;
        let start: number | null = null;
        const ease = (t: number) => 1 - Math.pow(1 - t, 3);
        const tick = (now: number) => {
            if (start === null) start = now;
            const progress = Math.min(1, (now - start) / dur);
            setValue(from + (target - from) * ease(progress));
            if (progress < 1) {
                raf = requestAnimationFrame(tick);
            } else {
                setValue(target);
            }
        };
        raf = requestAnimationFrame(tick);
        return () => cancelAnimationFrame(raf);
    }, [target, dur, from]);

    return decimals ? value.toFixed(decimals) : Math.round(value).toLocaleString();
}
