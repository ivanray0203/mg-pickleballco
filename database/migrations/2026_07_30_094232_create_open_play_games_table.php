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
        Schema::create('open_play_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('team_a_score');
            $table->unsignedSmallInteger('team_b_score');
            // Derived from the scores on save: 'a' or 'b'.
            $table->string('winning_team', 1);
            $table->timestamp('played_at')->nullable();
            $table->timestamps();

            $table->index('open_play_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_games');
    }
};
