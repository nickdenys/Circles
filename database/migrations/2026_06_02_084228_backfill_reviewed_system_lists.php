<?php

use App\Models\AlbumList;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::query()->chunkById(500, function ($users) {
            foreach ($users as $user) {
                $alreadyHasReviewed = $user->albumLists()
                    ->where('type', 'reviewed')
                    ->exists();

                if ($alreadyHasReviewed) {
                    continue;
                }

                $user->albumLists()->create([
                    'title' => 'Reviewed',
                    'type' => 'reviewed',
                    'sort' => 'added',
                    'direction' => 'desc',
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        AlbumList::query()
            ->where('type', 'reviewed')
            ->delete();
    }
};
