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
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->unique();
            $table->string('title');
            $table->string('artists');
            $table->string('cover_url')->nullable();
            $table->unsignedInteger('runtime_ms')->nullable();
            $table->string('album_type');
            $table->unsignedSmallInteger('total_tracks');
            $table->string('release_date');
            $table->string('spotify_uri');
            $table->timestamps();
        });

        Schema::create('album_album_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('album_list_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['album_id', 'album_list_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('album_album_list');
        Schema::dropIfExists('albums');
    }
};
