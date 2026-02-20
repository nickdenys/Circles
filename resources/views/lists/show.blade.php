<x-layouts.app :title="$list->title">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $list->title }}</h1>
            @if ($list->description)
                <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $list->description }}</p>
            @endif
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $list->albums_count ?? 0 }} {{ str('album')->plural($list->albums_count ?? 0) }}
            </p>
        </div>

        @unless ($list->isSystem())
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="edit-list-button"
                    class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-100"
                >
                    Edit
                </button>
                <button
                    type="button"
                    id="delete-list-button"
                    class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:border-red-700 dark:hover:bg-red-950"
                >
                    Delete
                </button>
            </div>
        @endunless
    </div>

    <div id="albums-container" class="mt-8 flex flex-col gap-3">
        <p class="py-8 text-center text-zinc-500 dark:text-zinc-400">
            No albums yet. Search for albums above to add them to this list.
        </p>
    </div>
</x-layouts.app>
