/**
 * Shell for pages that anyone can reach without an account. No sidebar and no
 * navigation into the rest of the app: a visitor holding a share link sees the
 * one page they were given and nothing around it.
 */
import { Link } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { PropsWithChildren, useCallback, useEffect, useState } from 'react';
import { IconButton } from '@/components/kit/IconButton';
import { Wordmark } from '@/components/kit/Logomark';
import { applyTheme, readStoredTheme, Theme } from '@/components/kit/theme';
import { useIsMobile } from '@/hooks/use-is-mobile';

export default function PublicLayout({ children }: PropsWithChildren) {
    const [theme, setTheme] = useState<Theme>(() => readStoredTheme());
    const isMobile = useIsMobile();

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    const toggleTheme = useCallback(() => {
        setTheme((current) => (current === 'dark' ? 'light' : 'dark'));
    }, []);

    return (
        <div
            style={{
                minHeight: '100dvh',
                background: 'var(--bg)',
                color: 'var(--fg1)',
                fontFamily: 'var(--font-sans)',
            }}
        >
            <header
                style={{
                    position: 'sticky',
                    top: 0,
                    zIndex: 50,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: 12,
                    padding: isMobile ? '8px 14px' : '12px 40px',
                    minHeight: 56,
                    boxSizing: 'border-box',
                    borderBottom: '1px solid var(--line)',
                    background: 'color-mix(in srgb, var(--bg) 82%, transparent)',
                    backdropFilter: 'blur(10px)',
                }}
            >
                <Wordmark size={isMobile ? 17 : 19} />
                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                    <IconButton
                        icon={theme === 'dark' ? Sun : Moon}
                        label={theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'}
                        onClick={toggleTheme}
                        size={18}
                        boxSize={isMobile ? 40 : 34}
                    />
                    <Link
                        href="/login"
                        style={{
                            fontSize: 13,
                            fontWeight: 600,
                            color: 'var(--fg1)',
                            textDecoration: 'none',
                            padding: '8px 12px',
                            borderRadius: 10,
                            border: '1px solid var(--line-strong)',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        Sign in
                    </Link>
                </div>
            </header>
            <main>{children}</main>
        </div>
    );
}
