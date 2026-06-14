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
        Schema::create('user_impersonations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            // Plain indexed columns (no hard FK to users): aero-auth is a foundational
            // shared package — central uses landlord_users, tenant/standalone use users.
            $table->unsignedBigInteger('impersonator_id')->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->text('reason')->nullable();
            $table->integer('max_duration_minutes')->default(60);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('force_ended')->default(false);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['target_id', 'ended_at']);
            $table->index(['impersonator_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_impersonations');
    }
};
