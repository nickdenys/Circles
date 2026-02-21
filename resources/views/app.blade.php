<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Hoopify') }}</title>
        <script>
            document.documentElement.classList.toggle(
                'dark',
                localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
            );
        </script>
        @inertiaHead
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body class="bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-white">
        @inertia
    </body>
</html>
