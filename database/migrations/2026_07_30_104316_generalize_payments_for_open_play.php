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
        Schema::table('payments', function (Blueprint $table) {
            // A payment now targets either a booking or an open-play
            // registration, so booking_id becomes optional.
            $table->foreignId('booking_id')->nullable()->change();

            $table->foreignId('open_play_registration_id')
                ->nullable()
                ->after('booking_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('open_play_registration_id');
            $table->foreignId('booking_id')->nullable(false)->change();
        });
    }
};
