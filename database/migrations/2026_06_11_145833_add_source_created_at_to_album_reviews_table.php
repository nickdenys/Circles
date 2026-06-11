<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('album_reviews', function (Blueprint $table) {
            $table->timestamp('source_created_at')->nullable()->after('source_position');
        });
    }

    public function down(): void
    {
        Schema::table('album_reviews', function (Blueprint $table) {
            $table->dropColumn('source_created_at');
        });
    }
};
