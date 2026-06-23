<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('spotify_token')->nullable()->change();
            $table->text('spotify_refresh_token')->nullable()->change();
            $table->timestamp('spotify_token_expires_at')->nullable()->change();
        });
    }
};
