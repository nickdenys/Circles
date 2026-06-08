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
            $table->string('sort')->default('manual')->after('type');
            $table->string('direction')->default('asc')->after('sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('album_lists', function (Blueprint $table) {
            $table->dropColumn(['sort', 'direction']);
        });
    }
};
