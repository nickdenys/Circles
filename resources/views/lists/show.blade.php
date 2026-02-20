<x-layouts.app :title="$list->title">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $list->title }}</h1>
            @if ($list->description)
                <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $list->description }}</p>
            @endif
            <p id="album-count" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $list->albums_count }} {{ str('album')->plural($list->albums_count) }}
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
        @forelse ($list->albums as $album)
            @php
                $totalSeconds = intdiv($album->runtime_ms, 1000);
                $hours = intdiv($totalSeconds, 3600);
                $minutes = intdiv($totalSeconds % 3600, 60);
                $runtimeFormatted = $hours > 0 ? $hours . 'h ' . $minutes . 'm' : $minutes . ' min';
            @endphp
            <div
                class="album-card group flex gap-4 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                data-album-id="{{ $album->spotify_id }}"
                data-album-db-id="{{ $album->id }}"
            >
                <div class="drag-handle flex shrink-0 cursor-grab items-center text-zinc-300 active:cursor-grabbing dark:text-zinc-600">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="9" cy="5" r="1.5" /><circle cx="15" cy="5" r="1.5" />
                        <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                        <circle cx="9" cy="19" r="1.5" /><circle cx="15" cy="19" r="1.5" />
                    </svg>
                </div>
                <a href="{{ $album->spotify_uri }}" class="shrink-0">
                    @if ($album->cover_url)
                        <img src="{{ $album->cover_url }}" alt="" class="h-20 w-20 rounded-lg object-cover" />
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                            <svg class="h-8 w-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" />
                            </svg>
                        </div>
                    @endif
                </a>
                <a href="{{ $album->spotify_uri }}" class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ $album->title }}</p>
                    <p class="truncate text-sm text-zinc-600 dark:text-zinc-400">{{ $album->artists }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                        {{ ucfirst($album->album_type) }} · {{ $album->total_tracks }} {{ str('track')->plural($album->total_tracks) }} · {{ $runtimeFormatted }}
                    </p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $album->release_date }}</p>
                </a>
                <div class="flex shrink-0 items-center gap-1 self-center opacity-0 transition group-hover:opacity-100">
                    <button
                        type="button"
                        class="move-album-button rounded-lg p-1.5 text-zinc-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-950 dark:hover:text-blue-400"
                        data-album-id="{{ $album->id }}"
                        data-album-title="{{ $album->title }}"
                        title="Move to list"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="remove-album-button rounded-lg p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400"
                        data-album-id="{{ $album->id }}"
                        data-album-title="{{ $album->title }}"
                        title="Remove from list"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <p id="albums-empty-state" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                No albums yet. Search for albums above to add them to this list.
            </p>
        @endforelse
    </div>

    {{-- Remove Album Modal --}}
    <div id="remove-album-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div id="remove-album-backdrop" class="absolute inset-0 bg-black/50"></div>
        <div class="relative mx-4 w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Remove Album</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Are you sure you want to remove <span id="remove-album-title" class="font-medium"></span> from this list?
            </p>

            <form id="remove-album-form" method="POST" class="mt-6 flex items-center justify-end gap-3">
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    id="cancel-remove-album"
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

    {{-- Move Album Modal --}}
    <div id="move-album-modal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div id="move-album-backdrop" class="absolute inset-0 bg-black/50"></div>
        <div class="relative mx-4 w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Move Album</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Move <span id="move-album-title" class="font-medium"></span> to another list.
            </p>

            <div id="move-list-search-container" class="relative mt-4">
                <input
                    type="text"
                    id="move-list-search-input"
                    placeholder="Search your lists..."
                    autocomplete="off"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400"
                />
                <div id="move-list-dropdown" class="absolute z-50 mt-1 hidden w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <div id="move-list-loading" class="hidden px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        Searching...
                    </div>
                    <div id="move-list-results" class="flex flex-col"></div>
                    <div id="move-list-empty" class="hidden px-4 py-3 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        No lists found.
                    </div>
                </div>
            </div>

            <div id="move-selected-list" class="mt-3 hidden rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm dark:border-blue-800 dark:bg-blue-950">
                <span class="text-zinc-700 dark:text-zinc-300">Moving to: </span>
                <span id="move-selected-list-name" class="font-medium"></span>
            </div>

            <form id="move-album-form" method="POST" class="mt-4 flex items-center justify-end gap-3">
                @csrf
                <input type="hidden" id="move-destination-list-id" name="destination_list_id" value="" />

                <button
                    type="button"
                    id="cancel-move-album"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    id="confirm-move-album"
                    disabled
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    Confirm
                </button>
            </form>
        </div>
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
            const albumsContainer = document.getElementById('albums-container');
            const albumCountEl = document.getElementById('album-count');
            const searchUrl = @json(route('spotify.search.albums'));
            const addAlbumUrl = @json(route('lists.albums.store', $list));
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            let debounceTimer = null;
            let abortController = null;
            let albumCount = {{ $list->albums_count }};

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

            function updateAlbumCount() {
                albumCount++;
                albumCountEl.textContent = albumCount + ' ' + (albumCount === 1 ? 'album' : 'albums');
            }

            function removeEmptyState() {
                const emptyState = document.getElementById('albums-empty-state');
                if (emptyState) {
                    emptyState.remove();
                }
            }

            function formatRuntime(ms) {
                var totalSeconds = Math.floor(ms / 1000);
                var hours = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds % 3600) / 60);
                return hours > 0 ? hours + 'h ' + minutes + 'm' : minutes + ' min';
            }

            function createAlbumCard(album) {
                const card = document.createElement('div');
                card.className = 'album-card group flex gap-4 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600';
                card.dataset.albumId = album.spotify_id;
                card.dataset.albumDbId = album.id;

                var trackCount = album.total_tracks || 0;
                var trackLabel = trackCount === 1 ? 'track' : 'tracks';
                var albumType = album.album_type ? album.album_type.charAt(0).toUpperCase() + album.album_type.slice(1) : '';

                const imgHtml = album.cover_url
                    ? '<img src="' + escapeHtml(album.cover_url) + '" alt="" class="h-20 w-20 rounded-lg object-cover" />'
                    : '<div class="flex h-20 w-20 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700"><svg class="h-8 w-8 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" /></svg></div>';

                card.innerHTML =
                    '<div class="drag-handle flex shrink-0 cursor-grab items-center text-zinc-300 active:cursor-grabbing dark:text-zinc-600">' +
                        '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="5" r="1.5" /><circle cx="15" cy="5" r="1.5" /><circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" /><circle cx="9" cy="19" r="1.5" /><circle cx="15" cy="19" r="1.5" /></svg>' +
                    '</div>' +
                    '<a href="' + escapeHtml(album.spotify_uri) + '" class="shrink-0">' + imgHtml + '</a>' +
                    '<a href="' + escapeHtml(album.spotify_uri) + '" class="min-w-0 flex-1">' +
                        '<p class="truncate text-sm font-semibold">' + escapeHtml(album.title) + '</p>' +
                        '<p class="truncate text-sm text-zinc-600 dark:text-zinc-400">' + escapeHtml(album.artists) + '</p>' +
                        '<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">' + escapeHtml(albumType) + ' &middot; ' + trackCount + ' ' + trackLabel + ' &middot; ' + formatRuntime(album.runtime_ms) + '</p>' +
                        '<p class="text-xs text-zinc-400 dark:text-zinc-500">' + escapeHtml(album.release_date || '') + '</p>' +
                    '</a>' +
                    '<div class="flex shrink-0 items-center gap-1 self-center opacity-0 transition group-hover:opacity-100">' +
                        '<button type="button" class="move-album-button rounded-lg p-1.5 text-zinc-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-950 dark:hover:text-blue-400" data-album-id="' + album.id + '" data-album-title="' + escapeHtml(album.title) + '" title="Move to list">' +
                            '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>' +
                        '</button>' +
                        '<button type="button" class="remove-album-button rounded-lg p-1.5 text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400" data-album-id="' + album.id + '" data-album-title="' + escapeHtml(album.title) + '" title="Remove from list">' +
                            '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>' +
                        '</button>' +
                    '</div>';

                return card;
            }

            function addAlbum(spotifyId) {
                fetch(addAlbumUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ spotify_id: spotifyId }),
                })
                .then(function (response) {
                    if (response.status === 409) {
                        return response.json().then(function (data) {
                            throw new Error(data.message || 'Album already in this list.');
                        });
                    }
                    if (!response.ok) {
                        throw new Error('Failed to add album.');
                    }
                    return response.json();
                })
                .then(function (data) {
                    removeEmptyState();
                    albumsContainer.appendChild(createAlbumCard(data.data));
                    updateAlbumCount();
                    hideDropdown();
                    searchInput.value = '';
                })
                .catch(function () {
                    hideDropdown();
                });
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

                    button.addEventListener('click', function () {
                        addAlbum(album.id);
                    });

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

    <script>
        (function () {
            const removeModal = document.getElementById('remove-album-modal');
            const removeBackdrop = document.getElementById('remove-album-backdrop');
            const removeForm = document.getElementById('remove-album-form');
            const removeTitleEl = document.getElementById('remove-album-title');
            const cancelRemoveBtn = document.getElementById('cancel-remove-album');
            const albumsContainer = document.getElementById('albums-container');
            const baseRemoveUrl = @json(url('/lists/' . $list->id . '/albums'));

            function openRemoveModal(albumId, albumTitle) {
                removeTitleEl.textContent = albumTitle;
                removeForm.action = baseRemoveUrl + '/' + albumId;
                removeModal.classList.remove('hidden');
                removeModal.classList.add('flex');
            }

            function closeRemoveModal() {
                removeModal.classList.add('hidden');
                removeModal.classList.remove('flex');
            }

            cancelRemoveBtn.addEventListener('click', closeRemoveModal);
            removeBackdrop.addEventListener('click', closeRemoveModal);

            albumsContainer.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-album-button');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    openRemoveModal(btn.dataset.albumId, btn.dataset.albumTitle);
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !removeModal.classList.contains('hidden')) {
                    closeRemoveModal();
                }
            });
        })();
    </script>

    <script>
        (function () {
            const moveModal = document.getElementById('move-album-modal');
            const moveBackdrop = document.getElementById('move-album-backdrop');
            const moveForm = document.getElementById('move-album-form');
            const moveTitleEl = document.getElementById('move-album-title');
            const cancelMoveBtn = document.getElementById('cancel-move-album');
            const confirmMoveBtn = document.getElementById('confirm-move-album');
            const moveSearchInput = document.getElementById('move-list-search-input');
            const moveDropdown = document.getElementById('move-list-dropdown');
            const moveResults = document.getElementById('move-list-results');
            const moveLoading = document.getElementById('move-list-loading');
            const moveEmpty = document.getElementById('move-list-empty');
            const moveSelectedEl = document.getElementById('move-selected-list');
            const moveSelectedName = document.getElementById('move-selected-list-name');
            const destinationInput = document.getElementById('move-destination-list-id');
            const albumsContainer = document.getElementById('albums-container');
            const searchUrl = @json(route('lists.search'));
            const currentListId = {{ $list->id }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            let moveDebounceTimer = null;
            let moveAbortController = null;

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function openMoveModal(albumId, albumTitle) {
                moveTitleEl.textContent = albumTitle;
                moveForm.action = '/lists/' + currentListId + '/albums/' + albumId + '/move';
                moveModal.classList.remove('hidden');
                moveModal.classList.add('flex');
                resetMoveSearch();
                moveSearchInput.focus();
            }

            function closeMoveModal() {
                moveModal.classList.add('hidden');
                moveModal.classList.remove('flex');
                resetMoveSearch();
            }

            function resetMoveSearch() {
                moveSearchInput.value = '';
                moveDropdown.classList.add('hidden');
                moveLoading.classList.add('hidden');
                moveEmpty.classList.add('hidden');
                moveResults.innerHTML = '';
                moveSelectedEl.classList.add('hidden');
                moveSelectedName.textContent = '';
                destinationInput.value = '';
                confirmMoveBtn.disabled = true;
            }

            function selectList(listId, listTitle) {
                destinationInput.value = listId;
                moveSelectedName.textContent = listTitle;
                moveSelectedEl.classList.remove('hidden');
                confirmMoveBtn.disabled = false;
                moveDropdown.classList.add('hidden');
                moveSearchInput.value = '';
            }

            function performListSearch(query) {
                if (moveAbortController) {
                    moveAbortController.abort();
                }

                moveAbortController = new AbortController();
                moveDropdown.classList.remove('hidden');
                moveLoading.classList.remove('hidden');
                moveEmpty.classList.add('hidden');
                moveResults.innerHTML = '';

                fetch(searchUrl + '?q=' + encodeURIComponent(query) + '&exclude=' + currentListId, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: moveAbortController.signal,
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    moveLoading.classList.add('hidden');
                    var lists = data.data || [];

                    if (lists.length === 0) {
                        moveEmpty.classList.remove('hidden');
                        return;
                    }

                    lists.forEach(function (list) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'flex items-center gap-2 px-4 py-2.5 text-left text-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-800 first:rounded-t-lg last:rounded-b-lg';

                        var typeLabel = list.type === 'system'
                            ? '<span class="shrink-0 rounded bg-zinc-200 px-1.5 py-0.5 text-xs text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">System</span>'
                            : '';

                        button.innerHTML = '<span class="truncate">' + escapeHtml(list.title) + '</span>' + typeLabel;

                        button.addEventListener('click', function () {
                            selectList(list.id, list.title);
                        });

                        moveResults.appendChild(button);
                    });
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') {
                        moveDropdown.classList.add('hidden');
                    }
                });
            }

            moveSearchInput.addEventListener('input', function () {
                var query = moveSearchInput.value.trim();

                clearTimeout(moveDebounceTimer);

                if (query.length < 1) {
                    moveDropdown.classList.add('hidden');
                    return;
                }

                moveDebounceTimer = setTimeout(function () {
                    performListSearch(query);
                }, 300);
            });

            cancelMoveBtn.addEventListener('click', closeMoveModal);
            moveBackdrop.addEventListener('click', closeMoveModal);

            albumsContainer.addEventListener('click', function (e) {
                var btn = e.target.closest('.move-album-button');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    openMoveModal(btn.dataset.albumId, btn.dataset.albumTitle);
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !moveModal.classList.contains('hidden')) {
                    closeMoveModal();
                }
            });
        })();
    </script>

    <script>
        (function () {
            var container = document.getElementById('albums-container');
            var reorderUrl = @json(route('lists.albums.reorder', $list));
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            var draggedCard = null;
            var placeholder = null;
            var startY = 0;
            var offsetY = 0;
            var holdTimer = null;
            var isDragging = false;

            function getCards() {
                return Array.from(container.querySelectorAll('.album-card'));
            }

            function createPlaceholder(height) {
                var el = document.createElement('div');
                el.className = 'drag-placeholder rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600';
                el.style.height = height + 'px';
                return el;
            }

            function startDrag(card, clientY) {
                isDragging = true;
                draggedCard = card;

                var rect = card.getBoundingClientRect();
                offsetY = clientY - rect.top;

                placeholder = createPlaceholder(rect.height);
                card.parentNode.insertBefore(placeholder, card);

                card.style.position = 'fixed';
                card.style.top = (clientY - offsetY) + 'px';
                card.style.left = rect.left + 'px';
                card.style.width = rect.width + 'px';
                card.style.zIndex = '100';
                card.style.opacity = '0.9';
                card.style.boxShadow = '0 10px 25px -5px rgba(0,0,0,0.2)';
                card.style.pointerEvents = 'none';
                card.style.transition = 'none';
                card.classList.add('cursor-grabbing');

                document.body.style.userSelect = 'none';
                document.body.style.webkitUserSelect = 'none';
            }

            function moveDrag(clientY) {
                if (!isDragging || !draggedCard) return;

                draggedCard.style.top = (clientY - offsetY) + 'px';

                var cards = getCards();
                for (var i = 0; i < cards.length; i++) {
                    var card = cards[i];
                    if (card === draggedCard) continue;

                    var rect = card.getBoundingClientRect();
                    var midY = rect.top + rect.height / 2;

                    if (clientY < midY) {
                        container.insertBefore(placeholder, card);
                        return;
                    }
                }

                container.appendChild(placeholder);
            }

            function endDrag() {
                clearTimeout(holdTimer);

                if (!isDragging || !draggedCard) {
                    isDragging = false;
                    return;
                }

                placeholder.parentNode.insertBefore(draggedCard, placeholder);
                placeholder.remove();

                draggedCard.style.position = '';
                draggedCard.style.top = '';
                draggedCard.style.left = '';
                draggedCard.style.width = '';
                draggedCard.style.zIndex = '';
                draggedCard.style.opacity = '';
                draggedCard.style.boxShadow = '';
                draggedCard.style.pointerEvents = '';
                draggedCard.style.transition = '';
                draggedCard.classList.remove('cursor-grabbing');

                document.body.style.userSelect = '';
                document.body.style.webkitUserSelect = '';

                saveOrder();

                draggedCard = null;
                placeholder = null;
                isDragging = false;
            }

            function saveOrder() {
                var cards = getCards();
                var albumIds = [];
                for (var i = 0; i < cards.length; i++) {
                    var id = cards[i].dataset.albumDbId;
                    if (id) {
                        albumIds.push(parseInt(id, 10));
                    }
                }

                if (albumIds.length === 0) return;

                fetch(reorderUrl, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ album_ids: albumIds }),
                });
            }

            // Mouse events — drag starts immediately on mousedown on the handle
            container.addEventListener('mousedown', function (e) {
                var handle = e.target.closest('.drag-handle');
                if (!handle) return;

                var card = handle.closest('.album-card');
                if (!card) return;

                e.preventDefault();
                startDrag(card, e.clientY);
            });

            document.addEventListener('mousemove', function (e) {
                if (isDragging) {
                    e.preventDefault();
                    moveDrag(e.clientY);
                }
            });

            document.addEventListener('mouseup', function () {
                if (isDragging) {
                    endDrag();
                }
            });

            // Touch events — long press (200ms) on the card initiates drag
            container.addEventListener('touchstart', function (e) {
                var handle = e.target.closest('.drag-handle');
                if (handle) {
                    // Drag handle: start immediately on touch
                    var card = handle.closest('.album-card');
                    if (!card) return;

                    var touch = e.touches[0];
                    startY = touch.clientY;

                    holdTimer = setTimeout(function () {
                        startDrag(card, touch.clientY);
                    }, 200);
                    return;
                }

                var card = e.target.closest('.album-card');
                if (!card) return;

                // If not on handle, ignore for drag (allow normal scroll)
            }, { passive: true });

            container.addEventListener('touchmove', function (e) {
                if (holdTimer && !isDragging) {
                    var touch = e.touches[0];
                    if (Math.abs(touch.clientY - startY) > 10) {
                        clearTimeout(holdTimer);
                        holdTimer = null;
                    }
                    return;
                }

                if (isDragging) {
                    e.preventDefault();
                    moveDrag(e.touches[0].clientY);
                }
            }, { passive: false });

            container.addEventListener('touchend', function () {
                clearTimeout(holdTimer);
                holdTimer = null;
                if (isDragging) {
                    endDrag();
                }
            });

            container.addEventListener('touchcancel', function () {
                clearTimeout(holdTimer);
                holdTimer = null;
                if (isDragging) {
                    endDrag();
                }
            });
        })();
    </script>
</x-layouts.app>
