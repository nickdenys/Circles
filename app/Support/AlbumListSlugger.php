<?php

namespace App\Support;

use App\Models\AlbumList;
use App\Models\AlbumListSlugHistory;
use App\Models\User;
use Illuminate\Support\Str;

class AlbumListSlugger
{
    public const RESERVED_SLUGS = [
        'search',
        'store',
        'create',
        'index',
        'new',
        'edit',
        'delete',
        'update',
        'settings',
        'me',
        'profile',
        'api',
        'admin',
        'auth',
        'login',
        'logout',
        AlbumList::LISTEN_LATER_SLUG,
        AlbumList::REVIEWED_SLUG,
    ];

    public const MAX_LENGTH = 60;

    /**
     * Slugify a title to the canonical base slug. Returns null if the result is
     * empty after transliteration; callers should treat that as a validation
     * failure (degenerate title).
     */
    public function base(string $title): ?string
    {
        $slug = Str::slug($title, '-', 'en');
        $slug = substr($slug, 0, self::MAX_LENGTH);
        $slug = trim($slug, '-');

        return $slug === '' ? null : $slug;
    }

    /**
     * Resolve a free slug for the user. Returns either:
     *   ['slug' => 'best-of-2024', 'conflict' => null]   when free or auto-suffixed
     *   ['slug' => null, 'conflict' => [...]]            when a history collision
     *                                                     is found and force is false
     *
     * @return array{slug: ?string, conflict: ?array{slug: string, previous_owner_title: string, suggested_alternative: string}}
     */
    public function resolveForUser(
        User $user,
        string $base,
        ?int $ignoreListId = null,
        bool $force = false,
    ): array {
        if ($this->collidesWithCurrent($user, $base, $ignoreListId)) {
            return ['slug' => $this->nextAvailable($user, $base, $ignoreListId), 'conflict' => null];
        }

        if (! $force && $previous = $this->historyOwner($user, $base, $ignoreListId)) {
            return [
                'slug' => null,
                'conflict' => [
                    'slug' => $base,
                    'previous_owner_title' => $previous->title,
                    'suggested_alternative' => $this->nextAvailable($user, $base, $ignoreListId),
                ],
            ];
        }

        return ['slug' => $base, 'conflict' => null];
    }

    /**
     * Delete history rows that conflict with a slug the user just claimed via
     * force_slug. Called after the new slug has been written.
     */
    public function purgeHistoryFor(User $user, string $slug): void
    {
        AlbumListSlugHistory::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->delete();
    }

    public function isReserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED_SLUGS, true);
    }

    private function collidesWithCurrent(User $user, string $slug, ?int $ignoreListId): bool
    {
        return $user->albumLists()
            ->where('slug', $slug)
            ->when($ignoreListId, fn ($q) => $q->where('id', '!=', $ignoreListId))
            ->exists();
    }

    private function historyOwner(User $user, string $slug, ?int $ignoreListId): ?AlbumList
    {
        $history = AlbumListSlugHistory::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->latest()
            ->first();

        if (! $history) {
            return null;
        }

        $list = $user->albumLists()->find($history->album_list_id);

        if (! $list) {
            return null;
        }

        if ($ignoreListId !== null && $list->id === $ignoreListId) {
            return null;
        }

        return $list;
    }

    private function nextAvailable(User $user, string $base, ?int $ignoreListId): string
    {
        $counter = 2;

        do {
            $candidate = $base.'-'.$counter;
            $counter++;
        } while ($this->collidesWithCurrent($user, $candidate, $ignoreListId));

        return $candidate;
    }
}
