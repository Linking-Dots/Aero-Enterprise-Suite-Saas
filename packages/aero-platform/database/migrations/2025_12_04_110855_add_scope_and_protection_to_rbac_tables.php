<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DUPLICATE MIGRATION - Also exists in database/migrations/tenant/
 *
 * This migration adds scope and protection columns to RBAC tables.
 * It exists in BOTH root and tenant migrations because:
 * - Root (Central DB): Updates landlord permission tables (platform admin roles)
 * - Tenant (Tenant DB): Updates tenant permission tables (tenant user roles)
 *
 * Both contexts need these columns for proper role/permission management.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds scope and is_protected columns to roles and permissions tables
     * to support platform/tenant separation and Super Administrator protection.
     *
     * Compliance: Section 2 - Role & Permission Scopes
     */
    public function up(): void
    {
        // Add scope and is_protected to roles table
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'scope')) {
                    $table->enum('scope', ['platform', 'tenant'])
                        ->default('tenant')
                        ->after('guard_name')
                        ->comment('Role scope: platform or tenant');
                }

                if (! Schema::hasColumn('roles', 'is_protected')) {
                    $table->boolean('is_protected')
                        ->default(false)
                        ->after('scope')
                        ->comment('Protected roles cannot be deleted or modified');
                }

                // The HRMAC Role model (shared tenant/landlord) writes is_active;
                // the central roles table otherwise lacks it (tenant DBs get it
                // from HRMAC migrations), breaking landlord role seeding.
                if (! Schema::hasColumn('roles', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('is_protected');
                }

                // Add index for scope queries
                if (! Schema::hasColumn('roles', 'scope')) {
                    $table->index('scope');
                }
                $table->index(['scope', 'tenant_id']);
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropIndex(['scope', 'tenant_id']);
                if (Schema::hasColumn('roles', 'is_protected')) {
                    $table->dropColumn('is_protected');
                }
                if (Schema::hasColumn('roles', 'scope')) {
                    $table->dropIndex(['scope']);
                    $table->dropColumn('scope');
                }
            });
        }
    }
};
