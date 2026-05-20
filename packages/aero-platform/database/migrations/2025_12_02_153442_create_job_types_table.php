<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DUPLICATE MIGRATION - Also exists in database/migrations/tenant/
 *
 * This migration creates the job_types lookup table.
 * It exists in BOTH root and tenant migrations because:
 * - Root (Central DB): Platform-level job type definitions (shared/standardized)
 * - Tenant (Tenant DB): Tenant-specific job type usage (isolated data)
 *
 * This allows tenants to have their own job type data while maintaining
 * platform-level definitions if needed.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('central')->hasTable('job_types')) {
            return;
        }

        Schema::create('job_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_types');
    }
};
