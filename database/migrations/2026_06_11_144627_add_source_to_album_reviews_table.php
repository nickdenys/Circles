<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('album_reviews', function (Blueprint $table) {
            $table->foreignId('source_album_list_id')
                ->nullable()
                ->after('review')
                ->constrained('album_lists')
                ->nullOnDelete();
            $table->unsignedInteger('source_position')->nullable()->after('source_album_list_id');
        });
    }

    public function down(): void
    {
        Schema::table('album_reviews', function (Blueprint $table) {
            $table->dropForeign(['source_album_list_id']);
            $table->dropColumn(['source_album_list_id', 'source_position']);
        });
    }
};
