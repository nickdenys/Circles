import { PropsWithChildren, useCallback, useEffect, useState } from 'react';
import { Sidebar } from '@/components/hoopify/Sidebar';
import { applyTheme, readStoredTheme, Theme } from '@/components/hoopify/theme';
import { Toaster } from '@/components/ui/sonner';

export default function AuthenticatedLayout({ children }: PropsWithChildren) {
    const [theme, setTheme] = useState<Theme>(() => readStoredTheme());

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    const toggleTheme = useCallback(() => {
        setTheme((current) => (current === 'dark' ? 'light' : 'dark'));
    }, []);

    return (
        <div
            style={{
                display: 'flex',
                height: '100vh',
                width: '100vw',
                overflow: 'hidden',
                background: 'var(--bg)',
                color: 'var(--fg1)',
                fontFamily: 'var(--font-sans)',
            }}
        >
            <Sidebar theme={theme} onToggleTheme={toggleTheme} />
            <main
                style={{
                    flex: 1,
                    minWidth: 0,
                    height: '100%',
                    overflowY: 'auto',
                    position: 'relative',
                    background: 'var(--bg)',
                }}
            >
                {children}
            </main>
            <Toaster />
        </div>
    );
}
