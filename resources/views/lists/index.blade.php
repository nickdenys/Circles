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

    <div id="lists-container" class="mt-6 flex flex-col gap-3">
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

    @if ($lists->hasMorePages())
        <div id="scroll-sentinel" class="flex justify-center py-6">
            <div id="scroll-loading" class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading more lists...</span>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const container = document.getElementById('lists-container');
            const sentinel = document.getElementById('scroll-sentinel');
            if (!sentinel) return;

            let nextPageUrl = @json($lists->nextPageUrl());
            let isLoading = false;

            function createListCard(list) {
                const a = document.createElement('a');
                a.href = list.url;
                a.className = 'group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-5 py-4 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/70';

                const count = list.albums_count;
                const label = count === 1 ? 'album' : 'albums';

                a.innerHTML = `<div>
                    <h2 class="font-medium">${list.title.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</h2>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">${count} ${label}</p>
                </div>
                <svg class="h-5 w-5 text-zinc-400 transition group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>`;

                return a;
            }

            async function loadMore() {
                if (isLoading || !nextPageUrl) return;
                isLoading = true;

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    data.data.forEach(function (list) {
                        container.appendChild(createListCard(list));
                    });

                    nextPageUrl = data.next_page_url;

                    if (!nextPageUrl) {
                        sentinel.remove();
                    }
                } finally {
                    isLoading = false;
                }
            }

            const observer = new IntersectionObserver(function (entries) {
                if (entries[0].isIntersecting) {
                    loadMore();
                }
            }, { rootMargin: '200px' });

            observer.observe(sentinel);
        })();
    </script>
</x-layouts.app>
