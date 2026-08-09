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
        Schema::create('open_play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity');
            // Optional target skill tier (beginner/novice/expert); null = all levels.
            $table->string('skill_level')->nullable();
            // Flat per-player fee, stored as integer cents. 0 = free session.
            $table->unsignedInteger('price_amount')->default(0);
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['court_id', 'session_date']);
            $table->index(['session_date', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('open_play_sessions');
    }
};
