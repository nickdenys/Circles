@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name', 'Hoopify') }}</title>
        <script>
            document.documentElement.classList.toggle(
                'dark',
                localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
            );
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
                <div class="flex items-center gap-6 sm:gap-8">
                    <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight">
                        Hoopify
                    </a>
                    <nav class="flex items-center gap-1">
                        <a
                            href="{{ route('home') }}"
                            @class([
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' => request()->routeIs('home'),
                                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! request()->routeIs('home'),
                            ])
                        >
                            Home
                        </a>
                        <a
                            href="{{ route('lists.index') }}"
                            @class([
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' => request()->routeIs('lists.*'),
                                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! request()->routeIs('lists.*'),
                            ])
                        >
                            Lists
                        </a>
                    </nav>
                </div>

                <div class="flex items-center gap-3 sm:gap-4">
                    <button
                        type="button"
                        id="theme-toggle"
                        class="rounded-md p-1.5 text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                        aria-label="Toggle dark mode"
                    >
                        <svg id="theme-icon-sun" class="hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        <svg id="theme-icon-moon" class="hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-2">
                        @if (auth()->user()->avatar)
                            <img
                                src="{{ auth()->user()->avatar }}"
                                alt="{{ auth()->user()->name }}"
                                class="h-7 w-7 rounded-full object-cover"
                            >
                        @endif
                        <span class="hidden text-sm font-medium sm:inline">
                            {{ auth()->user()->name }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="rounded-md px-3 py-1.5 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                        >
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            {{ $slot }}
        </main>

        <script>
            (function () {
                const toggle = document.getElementById('theme-toggle');
                const iconSun = document.getElementById('theme-icon-sun');
                const iconMoon = document.getElementById('theme-icon-moon');

                function updateIcons() {
                    const isDark = document.documentElement.classList.contains('dark');
                    iconSun.classList.toggle('hidden', !isDark);
                    iconMoon.classList.toggle('hidden', isDark);
                }

                updateIcons();

                toggle.addEventListener('click', function () {
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.theme = isDark ? 'dark' : 'light';
                    updateIcons();
                });
            })();
        </script>
    </body>
</html>
