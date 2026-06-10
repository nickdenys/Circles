export type Theme = 'light' | 'dark';

export const THEME_STORAGE_KEY = 'hoopify-theme';

export function readStoredTheme(): Theme {
    if (typeof document === 'undefined') {
        return 'light';
    }
    const attr = document.documentElement.getAttribute('data-theme');
    return attr === 'dark' ? 'dark' : 'light';
}

export function applyTheme(theme: Theme): void {
    if (typeof document === 'undefined') {
        return;
    }
    document.documentElement.setAttribute('data-theme', theme);
    try {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
        /* ignore storage errors */
    }
}

/** Stable, deterministic mapping from a list id to one of the tag colors. */
const TAG_COLORS = [
    'var(--tag-grape)',
    'var(--tag-teal)',
    'var(--tag-gold)',
    'var(--tag-rose)',
    'var(--tag-sky)',
    'var(--tag-moss)',
] as const;

export function listColor(id: number | string): string {
    const key = String(id);
    let hash = 0;
    for (let i = 0; i < key.length; i++) {
        hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
    }
    return TAG_COLORS[hash % TAG_COLORS.length];
}

export function listCode(id: number | string): string {
    const key = String(id);
    let hash = 2166136261;
    for (let i = 0; i < key.length; i++) {
        hash ^= key.charCodeAt(i);
        hash = Math.imul(hash, 16777619);
    }
    const number = ((hash >>> 0) % 998) + 1;
    return `LST_${String(number).padStart(3, '0')}`;
}
