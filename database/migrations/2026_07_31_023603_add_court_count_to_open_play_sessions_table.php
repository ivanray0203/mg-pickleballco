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
        Schema::table('open_play_sessions', function (Blueprint $table) {
            // How many courts this session runs on in parallel — i.e. how many
            // matches can be "playing" at once from a single shared queue.
            $table->unsignedSmallInteger('court_count')->default(1)->after('capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_sessions', function (Blueprint $table) {
            $table->dropColumn('court_count');
        });
    }
};
