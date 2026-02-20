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

    {{-- Edit List Modal --}}
    @unless ($list->isSystem())
        <div id="edit-list-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
            <div id="edit-modal-backdrop" class="absolute inset-0 bg-black/50"></div>
            <div class="relative mx-4 w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Edit List</h2>

                <form id="edit-list-form" method="POST" action="{{ route('lists.update', $list) }}" class="mt-4 flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="edit-list-title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Title</label>
                        <input
                            type="text"
                            id="edit-list-title"
                            name="title"
                            value="{{ old('title', $list->title) }}"
                            required
                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
                        />
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="edit-list-description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                        <textarea
                            id="edit-list-description"
                            name="description"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
                        >{{ old('description', $list->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            type="button"
                            id="cancel-edit-list"
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
    @endunless

    {{-- Delete List Modal --}}
    @unless ($list->isSystem())
        <div id="delete-list-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
            <div id="delete-modal-backdrop" class="absolute inset-0 bg-black/50"></div>
            <div class="relative mx-4 w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Delete List</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Are you sure? This will permanently delete this list and all its album associations.</p>

                <form method="POST" action="{{ route('lists.destroy', $list) }}" class="mt-6 flex items-center justify-end gap-3">
                    @csrf
                    @method('DELETE')

                    <button
                        type="button"
                        id="cancel-delete-list"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700"
                    >
                        Confirm
                    </button>
                </form>
            </div>
        </div>
    @endunless

    {{-- Album Search --}}
    <div id="album-search-container" class="relative mt-6">
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input
                type="text"
                id="album-search-input"
                placeholder="Search for albums on Spotify..."
                autocomplete="off"
                class="block w-full rounded-lg border border-zinc-300 bg-white py-2 pl-10 pr-3 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
            />
        </div>

        <div id="album-search-dropdown" class="absolute z-40 mt-1 hidden w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <div id="album-search-loading" class="hidden px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                Searching...
            </div>
            <div id="album-search-results" class="flex flex-col"></div>
            <div id="album-search-empty" class="hidden px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                No albums found.
            </div>
        </div>
    </div>

    <div id="albums-container" class="mt-8 flex flex-col gap-3">
        <p class="py-8 text-center text-zinc-500 dark:text-zinc-400">
            No albums yet. Search for albums above to add them to this list.
        </p>
    </div>

    @unless ($list->isSystem())
        <script>
            (function () {
                const editModal = document.getElementById('edit-list-modal');
                const editOpenBtn = document.getElementById('edit-list-button');
                const editCancelBtn = document.getElementById('cancel-edit-list');
                const editBackdrop = document.getElementById('edit-modal-backdrop');
                const titleInput = document.getElementById('edit-list-title');

                function openEditModal() {
                    editModal.classList.remove('hidden');
                    editModal.classList.add('flex');
                    titleInput.focus();
                }

                function closeEditModal() {
                    editModal.classList.add('hidden');
                    editModal.classList.remove('flex');
                }

                editOpenBtn.addEventListener('click', openEditModal);
                editCancelBtn.addEventListener('click', closeEditModal);
                editBackdrop.addEventListener('click', closeEditModal);

                const deleteModal = document.getElementById('delete-list-modal');
                const deleteOpenBtn = document.getElementById('delete-list-button');
                const deleteCancelBtn = document.getElementById('cancel-delete-list');
                const deleteBackdrop = document.getElementById('delete-modal-backdrop');

                function openDeleteModal() {
                    deleteModal.classList.remove('hidden');
                    deleteModal.classList.add('flex');
                }

                function closeDeleteModal() {
                    deleteModal.classList.add('hidden');
                    deleteModal.classList.remove('flex');
                }

                deleteOpenBtn.addEventListener('click', openDeleteModal);
                deleteCancelBtn.addEventListener('click', closeDeleteModal);
                deleteBackdrop.addEventListener('click', closeDeleteModal);

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        if (!editModal.classList.contains('hidden')) {
                            closeEditModal();
                        }
                        if (!deleteModal.classList.contains('hidden')) {
                            closeDeleteModal();
                        }
                    }
                });

                @if ($errors->any())
                    openEditModal();
                @endif
            })();
        </script>
    @endunless

    <script>
        (function () {
            const searchInput = document.getElementById('album-search-input');
            const dropdown = document.getElementById('album-search-dropdown');
            const resultsContainer = document.getElementById('album-search-results');
            const loadingEl = document.getElementById('album-search-loading');
            const emptyEl = document.getElementById('album-search-empty');
            const searchUrl = @json(route('spotify.search.albums'));

            let debounceTimer = null;
            let abortController = null;

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function showDropdown() {
                dropdown.classList.remove('hidden');
            }

            function hideDropdown() {
                dropdown.classList.add('hidden');
                loadingEl.classList.add('hidden');
                emptyEl.classList.add('hidden');
                resultsContainer.innerHTML = '';
            }

            function showLoading() {
                showDropdown();
                loadingEl.classList.remove('hidden');
                emptyEl.classList.add('hidden');
                resultsContainer.innerHTML = '';
            }

            function showEmpty() {
                loadingEl.classList.add('hidden');
                emptyEl.classList.remove('hidden');
                resultsContainer.innerHTML = '';
            }

            function showResults(albums) {
                loadingEl.classList.add('hidden');
                emptyEl.classList.add('hidden');
                resultsContainer.innerHTML = '';

                if (albums.length === 0) {
                    showEmpty();
                    return;
                }

                albums.forEach(function (album) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'flex items-center gap-3 px-4 py-2.5 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800 first:rounded-t-lg last:rounded-b-lg';
                    button.dataset.albumId = album.id;

                    const imgHtml = album.image
                        ? '<img src="' + escapeHtml(album.image) + '" alt="" class="h-10 w-10 shrink-0 rounded object-cover" />'
                        : '<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-zinc-200 dark:bg-zinc-700"><svg class="h-5 w-5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" /></svg></div>';

                    button.innerHTML = imgHtml +
                        '<div class="min-w-0">' +
                            '<p class="truncate text-sm font-medium">' + escapeHtml(album.name) + '</p>' +
                            '<p class="truncate text-xs text-zinc-500 dark:text-zinc-400">' + escapeHtml(album.artists) + '</p>' +
                        '</div>';

                    resultsContainer.appendChild(button);
                });
            }

            function performSearch(query) {
                if (abortController) {
                    abortController.abort();
                }

                abortController = new AbortController();
                showLoading();

                fetch(searchUrl + '?q=' + encodeURIComponent(query), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: abortController.signal,
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    showResults(data.data || []);
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') {
                        hideDropdown();
                    }
                });
            }

            searchInput.addEventListener('input', function () {
                const query = searchInput.value.trim();

                clearTimeout(debounceTimer);

                if (query.length < 2) {
                    hideDropdown();
                    return;
                }

                debounceTimer = setTimeout(function () {
                    performSearch(query);
                }, 300);
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#album-search-container')) {
                    hideDropdown();
                }
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    hideDropdown();
                    searchInput.blur();
                }
            });
        })();
    </script>
</x-layouts.app>
