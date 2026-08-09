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
        Schema::table('open_play_registrations', function (Blueprint $table) {
            // Paid registrations hold a slot until this time; a lapsed unpaid
            // hold is released so the slot frees up (mirrors booking holds).
            $table->timestamp('expires_at')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_play_registrations', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
