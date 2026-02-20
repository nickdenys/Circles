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

    {{-- Create List Modal --}}
    <div id="create-list-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div id="modal-backdrop" class="absolute inset-0 bg-black/50"></div>
        <div class="relative mx-4 w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Create New List</h2>

            <form id="create-list-form" method="POST" action="{{ route('lists.store') }}" class="mt-4 flex flex-col gap-4">
                @csrf

                <div>
                    <label for="list-title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Title</label>
                    <input
                        type="text"
                        id="list-title"
                        name="title"
                        value="{{ old('title') }}"
                        required
                        class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
                    />
                    @error('title')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="list-description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea
                        id="list-description"
                        name="description"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        id="cancel-create-list"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
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
            // Modal logic
            const modal = document.getElementById('create-list-modal');
            const openBtn = document.getElementById('add-list-button');
            const cancelBtn = document.getElementById('cancel-create-list');
            const backdrop = document.getElementById('modal-backdrop');
            const titleInput = document.getElementById('list-title');

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                titleInput.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            openBtn.addEventListener('click', openModal);
            cancelBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', closeModal);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Auto-open modal if there are validation errors
            @if ($errors->any())
                openModal();
            @endif

            // Infinite scroll logic
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
