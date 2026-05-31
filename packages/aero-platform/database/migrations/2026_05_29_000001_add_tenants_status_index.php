<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0 Task 6 of foundation 10/10 push.
 *
 * The Tenant model's scopeActive/scopeSuspended/scopeProvisioning all filter
 * by `status`, but the original create_custom_tenants_table migration shipped
 * no index on this column. As the tenants table grows past ~10k rows, every
 * dashboard query that filters by status becomes a full table scan.
 *
 * Adds a single-column index on `status` plus a composite (status, created_at)
 * for the common "active tenants ordered by signup date" pagination query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('status', 'tenants_status_idx');
            $table->index(['status', 'created_at'], 'tenants_status_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_status_idx');
            $table->dropIndex('tenants_status_created_at_idx');
        });
    }
};
