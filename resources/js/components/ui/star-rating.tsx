import * as React from 'react';
import { Star } from 'lucide-react';

import { cn } from '@/lib/utils';

type Size = 'sm' | 'md' | 'lg';

const SIZE_CLASS: Record<Size, string> = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-8 w-8',
};

interface StarRatingProps {
    value?: number;
    max?: number;
    size?: Size;
    interactive?: boolean;
    onChange?: (value: number) => void;
    className?: string;
}

function StarRating({
    value = 0,
    max = 5,
    size = 'md',
    interactive = false,
    onChange,
    className,
}: StarRatingProps) {
    const [hover, setHover] = React.useState<number | null>(null);

    const display = hover ?? value;
    const sizeClass = SIZE_CLASS[size];

    function selectionFromPointer(
        starIndex: number,
        event: React.PointerEvent<HTMLButtonElement> | React.MouseEvent<HTMLButtonElement>,
    ): number {
        const rect = event.currentTarget.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const isHalf = x < rect.width / 2;

        return starIndex + (isHalf ? 0.5 : 1);
    }

    return (
        <div
            className={cn('inline-flex items-center gap-0.5', className)}
            role={interactive ? 'slider' : 'img'}
            aria-label={`Rating ${display} out of ${max}`}
            aria-valuenow={interactive ? display : undefined}
            aria-valuemin={interactive ? 0 : undefined}
            aria-valuemax={interactive ? max : undefined}
            onPointerLeave={() => setHover(null)}
        >
            {Array.from({ length: max }, (_, index) => {
                const starValue = index + 1;
                let fillPct = 0;

                if (display >= starValue) {
                    fillPct = 100;
                } else if (display >= starValue - 0.5) {
                    fillPct = 50;
                }

                const star = (
                    <span className={cn('relative inline-block', sizeClass)}>
                        <Star className={cn('text-zinc-300 dark:text-zinc-600', sizeClass)} />
                        <span
                            className="pointer-events-none absolute inset-y-0 left-0 overflow-hidden"
                            style={{ width: `${fillPct}%` }}
                        >
                            <Star className={cn('fill-yellow-400 text-yellow-400', sizeClass)} />
                        </span>
                    </span>
                );

                if (!interactive) {
                    return <span key={index}>{star}</span>;
                }

                return (
                    <button
                        key={index}
                        type="button"
                        className="cursor-pointer rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label={`Rate ${starValue} star${starValue === 1 ? '' : 's'}`}
                        onPointerMove={(event) => setHover(selectionFromPointer(index, event))}
                        onClick={(event) => onChange?.(selectionFromPointer(index, event))}
                    >
                        {star}
                    </button>
                );
            })}
        </div>
    );
}

export { StarRating };
