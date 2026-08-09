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
        Schema::table('open_play_matchups', function (Blueprint $table) {
            // Which court a playing matchup is on (e.g. "Court 2"); null while queued.
            $table->string('court_label')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_matchups', function (Blueprint $table) {
            $table->dropColumn('court_label');
        });
    }
};
