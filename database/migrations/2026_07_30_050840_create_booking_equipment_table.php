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
        Schema::create('booking_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            // Snapshot the display name so historical line items survive renames.
            $table->string('name');
            $table->unsignedInteger('quantity');
            // Per-unit, per-hour rate in cents, snapshotted at booking time.
            $table->unsignedInteger('unit_hourly_rate');
            // quantity * unit_hourly_rate * booking duration_hours, in cents.
            $table->unsignedInteger('line_total');
            $table->timestamps();

            $table->index(['equipment_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_equipment');
    }
};
