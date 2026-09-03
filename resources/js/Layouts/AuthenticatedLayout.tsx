import { usePage } from '@inertiajs/react';
import { PropsWithChildren, useCallback, useEffect, useMemo, useState } from 'react';
import { MobileMenuProvider } from '@/components/kit/MobileMenuContext';
import { Sidebar } from '@/components/kit/Sidebar';
import { applyTheme, readStoredTheme, Theme } from '@/components/kit/theme';
import { Toaster } from '@/components/ui/sonner';
import { useIsMobile } from '@/hooks/use-is-mobile';

export default function AuthenticatedLayout({ children }: PropsWithChildren) {
    const [theme, setTheme] = useState<Theme>(() => readStoredTheme());
    const [menuOpen, setMenuOpen] = useState(false);
    const isMobile = useIsMobile();
    const { url } = usePage();

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    /* The drawer only exists on mobile, and never survives a navigation. */
    useEffect(() => {
        if (!isMobile) setMenuOpen(false);
    }, [isMobile]);

    useEffect(() => {
        setMenuOpen(false);
    }, [url]);

    const toggleTheme = useCallback(() => {
        setTheme((current) => (current === 'dark' ? 'light' : 'dark'));
    }, []);

    const closeMenu = useCallback(() => setMenuOpen(false), []);
    const openMenu = useCallback(() => setMenuOpen(true), []);

    const mobileMenu = useMemo(
        () => ({ open: menuOpen, openMenu, closeMenu }),
        [menuOpen, openMenu, closeMenu],
    );

    return (
        <MobileMenuProvider value={mobileMenu}>
            <div
                style={{
                    display: 'flex',
                    height: '100dvh',
                    width: '100%',
                    overflow: 'hidden',
                    background: 'var(--bg)',
                    color: 'var(--fg1)',
                    fontFamily: 'var(--font-sans)',
                }}
            >
                <Sidebar
                    theme={theme}
                    onToggleTheme={toggleTheme}
                    open={menuOpen}
                    onClose={closeMenu}
                />
                <main
                    style={{
                        flex: 1,
                        minWidth: 0,
                        height: '100%',
                        overflowY: 'auto',
                        WebkitOverflowScrolling: 'touch',
                        position: 'relative',
                        background: 'var(--bg)',
                    }}
                >
                    {children}
                </main>
                <Toaster />
            </div>
        </MobileMenuProvider>
    );
}
