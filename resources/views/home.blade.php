<x-layouts.app title="Home">
    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
        Welcome back, {{ auth()->user()->name }}
    </h1>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Lists</p>
            <p class="mt-1 text-2xl font-semibold">{{ $totalLists }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Albums</p>
            <p class="mt-1 text-2xl font-semibold">{{ $totalAlbums }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white px-5 py-4 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Most Populated List</p>
            @if ($mostPopulatedList && $mostPopulatedList->albums_count > 0)
                <p class="mt-1 text-2xl font-semibold">{{ $mostPopulatedList->title }}</p>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $mostPopulatedList->albums_count }} {{ str('album')->plural($mostPopulatedList->albums_count) }}
                </p>
            @else
                <p class="mt-1 text-lg text-zinc-400 dark:text-zinc-500">&mdash;</p>
            @endif
        </div>
    </div>
</x-layouts.app>
