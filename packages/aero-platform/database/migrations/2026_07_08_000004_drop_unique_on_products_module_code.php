<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * products.module_code was UNIQUE (a 1:1 product↔module assumption). With the
 * product_modules pivot now canonical, a module can be bundled by several
 * products, and the scalar is only a "primary module" BC hint — so the unique
 * constraint is wrong and blocks creating a second product that shares a primary
 * module. Drop the unique index; keep a plain index for the readers that still
 * filter on it (ModuleAnalyticsController etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only the SaaS/central products table has this; guard for standalone.
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'module_code')) {
            return;
        }

        try {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropUnique('products_module_code_unique');
            });
        } catch (\Throwable $e) {
            // Index name may differ or already be gone — fall back to a raw drop.
            try {
                DB::statement('ALTER TABLE products DROP INDEX products_module_code_unique');
            } catch (\Throwable $ignored) {
                // Already non-unique; nothing to do.
            }
        }

        // Ensure a plain lookup index remains.
        try {
            Schema::table('products', function (Blueprint $table): void {
                $table->index('module_code', 'products_module_code_index');
            });
        } catch (\Throwable $ignored) {
            // Index already present.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        try {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('products_module_code_index');
                $table->unique('module_code', 'products_module_code_unique');
            });
        } catch (\Throwable $ignored) {
            // Best-effort restore.
        }
    }
};
