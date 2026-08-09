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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // The acting admin. Nullable + nullOnDelete so history survives if
            // the user is later removed.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            // Optional target entity (polymorphic), e.g. Court #3.
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            // Safe, non-sensitive context only. Never secrets or payment payloads.
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('action');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
