<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('album_lists', function (Blueprint $table) {
            $table->string('share_hash')->nullable()->unique()->after('direction');
            $table->timestamp('shared_at')->nullable()->after('share_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('album_lists', function (Blueprint $table) {
            $table->dropUnique(['share_hash']);
            $table->dropColumn(['share_hash', 'shared_at']);
        });
    }
};
