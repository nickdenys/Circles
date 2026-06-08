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
        Schema::create('album_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->decimal('rating', 2, 1);
            $table->text('review')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'album_id']);
            $table->index('album_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('album_reviews');
    }
};
