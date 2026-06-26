<?php

use App\Models\AlbumList;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AlbumList::query()
            ->where('type', 'system')
            ->update(['mode' => 'listening']);

        AlbumList::query()
            ->where('type', '!=', 'system')
            ->where('mode', 'listening')
            ->update(['mode' => 'default']);
    }

    public function down(): void
    {
        AlbumList::query()
            ->where('type', 'system')
            ->update(['mode' => 'default']);
    }
};
