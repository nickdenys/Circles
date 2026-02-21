import { Link, router, usePage } from '@inertiajs/react';
import { Moon, Sun } from 'lucide-react';
import { PropsWithChildren, useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';

interface AuthUser {
    id: number;
    name: string;
    avatar: string | null;
}

interface SharedProps {
    auth: {
        user: AuthUser;
    };
    currentRouteName: string;
    [key: string]: unknown;
}

function isDarkMode(): boolean {
    return document.documentElement.classList.contains('dark');
}

export default function AuthenticatedLayout({ children }: PropsWithChildren) {
    const { auth, currentRouteName } = usePage<SharedProps>().props;
    const [dark, setDark] = useState(() => isDarkMode());

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setDark(isDarkMode());
        });
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
        return () => observer.disconnect();
    }, []);

    const toggleTheme = useCallback(() => {
        const newDark = !isDarkMode();
        document.documentElement.classList.toggle('dark', newDark);
        localStorage.theme = newDark ? 'dark' : 'light';
        setDark(newDark);
    }, []);

    const handleLogout = useCallback(() => {
        router.post('/logout');
    }, []);

    const isActive = (pattern: string): boolean => {
        if (pattern === 'home') {
            return currentRouteName === 'home';
        }
        return currentRouteName?.startsWith(pattern) ?? false;
    };

    const navLinkClasses = (pattern: string): string => {
        const base = 'rounded-md px-3 py-1.5 text-sm font-medium transition';
        if (isActive(pattern)) {
            return `${base} bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100`;
        }
        return `${base} text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100`;
    };

    return (
        <div className="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
            <header className="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                    <div className="flex items-center gap-6 sm:gap-8">
                        <Link href="/" className="text-xl font-bold tracking-tight">
                            Hoopify
                        </Link>
                        <nav className="flex items-center gap-1">
                            <Link href="/" className={navLinkClasses('home')}>
                                Home
                            </Link>
                            <Link href="/lists" className={navLinkClasses('lists')}>
                                Lists
                            </Link>
                        </nav>
                    </div>

                    <div className="flex items-center gap-3 sm:gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={toggleTheme}
                            aria-label="Toggle dark mode"
                            className="h-8 w-8 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                        >
                            {dark ? (
                                <Sun className="h-5 w-5" />
                            ) : (
                                <Moon className="h-5 w-5" />
                            )}
                        </Button>

                        <div className="flex items-center gap-2">
                            {auth.user.avatar && (
                                <img
                                    src={auth.user.avatar}
                                    alt={auth.user.name}
                                    className="h-7 w-7 rounded-full object-cover"
                                />
                            )}
                            <span className="hidden text-sm font-medium sm:inline">
                                {auth.user.name}
                            </span>
                        </div>

                        <Button
                            variant="ghost"
                            onClick={handleLogout}
                            className="px-3 py-1.5 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                        >
                            Logout
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                {children}
            </main>
        </div>
    );
}
