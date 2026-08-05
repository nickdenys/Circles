import { useEffect, useState } from 'react';

/**
 * The single breakpoint the whole app agrees on for its mobile layout.
 * Inline-styled components branch on `useIsMobile()` rather than duplicating markup.
 */
export const MOBILE_QUERY = '(max-width: 760px)';

export function useMedia(query: string): boolean {
    const [matches, setMatches] = useState(() =>
        typeof window === 'undefined' ? false : window.matchMedia(query).matches,
    );

    useEffect(() => {
        const mediaQuery = window.matchMedia(query);
        const onChange = (event: MediaQueryListEvent) => setMatches(event.matches);

        setMatches(mediaQuery.matches);
        mediaQuery.addEventListener('change', onChange);

        return () => mediaQuery.removeEventListener('change', onChange);
    }, [query]);

    return matches;
}

export function useIsMobile(): boolean {
    return useMedia(MOBILE_QUERY);
}
