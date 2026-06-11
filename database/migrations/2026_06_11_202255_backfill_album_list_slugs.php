<?php

use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->orderBy('id')->each(function (User $user) {
            $taken = [];

            $user->albumLists()->orderBy('created_at')->orderBy('id')->each(function (AlbumList $list) use (&$taken) {
                $base = match ($list->type) {
                    'system' => AlbumList::LISTEN_LATER_SLUG,
                    'reviewed' => AlbumList::REVIEWED_SLUG,
                    default => $this->slugifyTitle($list->title, $list->id),
                };

                $slug = $this->ensureUnique($base, $taken);
                $taken[] = $slug;

                $list->forceFill(['slug' => $slug])->saveQuietly();
            });
        });

        Schema::table('album_lists', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('album_lists', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->string('slug')->nullable()->change();
        });
    }

    private function slugifyTitle(string $title, int $listId): string
    {
        $slug = trim(substr(Str::slug($title, '-', 'en'), 0, 60), '-');

        return $slug === '' ? 'list-'.$listId : $slug;
    }

    /**
     * @param  array<int, string>  $taken
     */
    private function ensureUnique(string $base, array $taken): string
    {
        if (! in_array($base, $taken, true)) {
            return $base;
        }

        $counter = 2;
        while (in_array($base.'-'.$counter, $taken, true)) {
            $counter++;
        }

        return $base.'-'.$counter;
    }
};
