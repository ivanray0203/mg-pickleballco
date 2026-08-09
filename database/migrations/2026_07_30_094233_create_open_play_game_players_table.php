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
        Schema::create('open_play_game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('open_play_registration_id')->constrained()->cascadeOnDelete();
            // Which side of the game this player was on: 'a' or 'b'.
            $table->string('team', 1);
            $table->timestamps();

            // A player appears once per game (named to stay within MySQL's 64-char limit).
            $table->unique(['open_play_game_id', 'open_play_registration_id'], 'opgp_game_registration_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_game_players');
    }
};
