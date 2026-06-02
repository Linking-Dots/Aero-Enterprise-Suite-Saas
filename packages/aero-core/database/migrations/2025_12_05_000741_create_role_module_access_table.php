<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DUPLICATE MIGRATION - Also exists in database/migrations/
 *
 * This migration creates the role_module_access table.
 * It exists in BOTH root and tenant migrations with DIFFERENT IMPLEMENTATIONS:
 *
 * - Root (Central DB): Has foreign key constraints to modules, sub_modules, etc.
 *   (all tables are in the same central database)
 *
 * - Tenant (Tenant DB): Uses unsigned integers WITHOUT foreign keys
 *   (module hierarchy is in central DB, so cross-database references not possible)
 *
 * This architectural difference is intentional and required for multi-tenant isolation.
 * Module hierarchy definitions are centralized but tenant roles reference them by ID.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the role_module_access table for direct role-to-module hierarchy mapping.
     * This replaces the complex permission system with a simpler, visual approach.
     *
     * Access is granted at ONE level only (module, submodule, component, or action).
     * Higher level grants cascade down to children.
     *
     * Example:
     * - role_id=1, module_id=5, others=null → Role has FULL access to module 5 (all children)
     * - role_id=1, action_id=42, others=null → Role has access to ONLY action 42
     *
     * NOTE: module_id, sub_module_id, component_id, and action_id are references to
     * tables in the CENTRAL database. They cannot have foreign key constraints since
     * they're cross-database references.
     */
    public function up(): void
    {
        Schema::create('role_module_access', function (Blueprint $table) {
            $table->id();

            // Role reference (from Spatie - in tenant database)
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            // Module hierarchy IDs - these reference central database tables
            // No foreign key constraints since they're cross-database references
            $table->unsignedBigInteger('module_id')->nullable();
            $table->unsignedBigInteger('sub_module_id')->nullable();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('action_id')->nullable();

            // Access scope for "own", "team", "department" level access
            $table->enum('access_scope', ['all', 'own', 'team', 'department'])
                ->default('all')
                ->comment('Scope of access: all records, own records, team records, or department records');

            // Subscription suspension state. RoleModuleAccessService always filters
            // status='active'; this column ships with the base table so it exists in
            // standalone (aero-hrmac's add_status migration only runs per-tenant in SaaS).
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamp('suspended_at')->nullable();

            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['role_id', 'module_id']);
            $table->index(['role_id', 'sub_module_id']);
            $table->index(['role_id', 'component_id']);
            $table->index(['role_id', 'action_id']);

            // Unique constraint - prevent duplicate access entries
            $table->unique(
                ['role_id', 'module_id', 'sub_module_id', 'component_id', 'action_id'],
                'role_module_access_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_module_access');
    }
};
