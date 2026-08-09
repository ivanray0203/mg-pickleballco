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
        Schema::create('open_play_game_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            // The player who reported the result.
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            // Proposed teams stored as registration-id arrays; the authoritative
            // OpenPlayGame + player rows are only created on organizer confirm.
            $table->json('team_a');
            $table->json('team_b');
            $table->unsignedSmallInteger('team_a_score');
            $table->unsignedSmallInteger('team_b_score');
            $table->string('status')->default('pending');
            // Organizer who confirmed/rejected, and the game created on confirm.
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('open_play_game_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['open_play_session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_game_submissions');
    }
};
