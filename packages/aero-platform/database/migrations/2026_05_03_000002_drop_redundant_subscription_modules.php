<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop partially-created or redundant tables so the fixed migration can re-run cleanly.
 *
 * subscription_modules was left in a broken state when the original
 * 2026_05_03_000003 migration failed due to a MySQL 64-character index-name limit.
 * This migration removes it so the corrected migration can recreate it properly.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the partially-created subscription_modules table from the failed migration
        Schema::dropIfExists('subscription_modules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: the table will be recreated by 2026_05_03_000003_create_subscription_modules_table.
    }
};
