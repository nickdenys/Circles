<x-layouts.app title="Lists">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight">My Lists</h1>
        <button
            type="button"
            id="add-list-button"
            class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
            Add List
        </button>
    </div>

    <div class="mt-6 flex flex-col gap-3">
        @forelse ($lists as $list)
            <a
                href="{{ route('lists.show', $list) }}"
                class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-5 py-4 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/70"
            >
                <div>
                    <h2 class="font-medium">{{ $list->title }}</h2>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $list->albums_count ?? 0 }} {{ str('album')->plural($list->albums_count ?? 0) }}
                    </p>
                </div>
                <svg class="h-5 w-5 text-zinc-400 transition group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        @empty
            <p class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                No lists yet. Create one to get started.
            </p>
        @endforelse
    </div>
</x-layouts.app>
