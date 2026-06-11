<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('album_lists', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        Schema::create('album_list_slugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_list_slugs');

        Schema::table('album_lists', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
