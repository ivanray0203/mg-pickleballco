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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Internal, human-friendly reference (PAY-YYYYMMDD-XXXX).
            $table->string('payment_reference')->unique();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            // The gateway's own id for this checkout/payment (e.g. cs_xxx).
            $table->string('provider_reference')->nullable();
            $table->string('payment_method');
            // Amount snapshot in integer minor units (centavos). Never a float.
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending');
            $table->text('checkout_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // Non-sensitive provider echo (event ids, failure codes) for auditing.
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('provider_reference');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
