<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical RBAC permission tables migration.
 *
 * Consolidates:
 *  - 2025_11_29_000000_create_permission_tables.php                  (base)
 *  - 2025_12_04_110855_add_scope_and_protection_to_rbac_tables.php   (absorbed)
 *  - 2025_12_30_100218_add_is_active_to_roles_table.php              (absorbed)
 *  - 2026_01_11_000001_add_default_dashboard_to_roles_table.php      (absorbed – seed logic moved to RoleSeeder)
 *
 * NOTE: The seedDefaultDashboards() logic that was embedded in the
 *       add_default_dashboard migration must be moved to:
 *       database/seeders/RoleSeeder.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── roles ──────────────────────────────────────────────────────────────
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->string('guard_name')->default('web');

                // Absorbed from: add_scope_and_protection_to_rbac_tables
                $table->enum('scope', ['platform', 'tenant'])
                    ->default('tenant')
                    ->comment('Role scope: platform or tenant');
                $table->boolean('is_protected')
                    ->default(false)
                    ->comment('Protected roles cannot be deleted or modified');

                // Absorbed from: add_is_active_to_roles_table
                $table->boolean('is_active')
                    ->default(true);

                // Absorbed from: add_default_dashboard_to_roles_table
                $table->string('default_dashboard')
                    ->nullable()
                    ->comment('Route name for the default dashboard (e.g., hrm.dashboard)');
                $table->integer('priority')
                    ->default(0)
                    ->comment('Higher value = takes precedence when user has multiple roles');

                $table->integer('tenant_id')->nullable()->index();

                $table->timestamps();

                $table->unique(['name', 'guard_name']);
                $table->index('scope');
                $table->index(['scope', 'tenant_id']);
                $table->index('is_active');
            });
        }

        // ── model_has_roles ────────────────────────────────────────────────────
        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');

                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->onDelete('cascade');

                $table->index(
                    ['model_id', 'model_type'],
                    'model_has_roles_model_id_model_type_index'
                );

                $table->primary(
                    ['role_id', 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
    }
};
