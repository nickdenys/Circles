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

    <div id="albums-container" class="mt-8 flex flex-col gap-3">
        <p class="py-8 text-center text-zinc-500 dark:text-zinc-400">
            No albums yet. Search for albums above to add them to this list.
        </p>
    </div>

    @unless ($list->isSystem())
        <script>
            (function () {
                const modal = document.getElementById('edit-list-modal');
                const openBtn = document.getElementById('edit-list-button');
                const cancelBtn = document.getElementById('cancel-edit-list');
                const backdrop = document.getElementById('edit-modal-backdrop');
                const titleInput = document.getElementById('edit-list-title');

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

                @if ($errors->any())
                    openModal();
                @endif
            })();
        </script>
    @endunless
</x-layouts.app>
