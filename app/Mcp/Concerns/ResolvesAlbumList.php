<?php

namespace App\Mcp\Concerns;

use App\Models\AlbumList;
use App\Models\User;

trait ResolvesAlbumList
{
    protected function resolveList(User $user, ?string $identifier): ?AlbumList
    {
        if ($identifier === null) {
            return $user->albumLists()->where('type', 'system')->first();
        }

        if (is_numeric($identifier)) {
            return $user->albumLists()->where('id', $identifier)->first();
        }

        return $user->albumLists()->whereRaw('LOWER(title) = ?', [strtolower($identifier)])->first();
    }
}
