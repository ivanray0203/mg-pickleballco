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
        Schema::create('open_play_matchups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            // Auto-generated 2v2 teams, stored as registration-id arrays.
            $table->json('team_a');
            $table->json('team_b');
            // Queue order within the session (lower plays sooner).
            $table->unsignedInteger('position');
            $table->string('status')->default('queued');
            // The game recorded when this matchup's score is entered.
            $table->foreignId('open_play_game_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['open_play_session_id', 'status', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_matchups');
    }
};
