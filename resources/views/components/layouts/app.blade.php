@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name', 'Hoopify') }}</title>
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
    </body>
</html>
