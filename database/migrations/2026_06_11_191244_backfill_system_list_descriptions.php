<?php

use App\Models\AlbumList;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AlbumList::query()
            ->where('type', 'system')
            ->update(['description' => AlbumList::LISTEN_LATER_DESCRIPTION]);

        AlbumList::query()
            ->where('type', 'reviewed')
            ->update(['description' => AlbumList::REVIEWED_DESCRIPTION]);
    }

    public function down(): void
    {
        AlbumList::query()
            ->whereIn('type', ['system', 'reviewed'])
            ->update(['description' => null]);
    }
};
