<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidation migration for quota_enforcement_settings.
 *
 * The original 2025_12_24_140000 migration created the table with legacy
 * notification/threshold columns. The v2 migration (2026_05_21_030003) added
 * resource/default_limit/hard_limit_pct/action columns additively.
 *
 * This migration uses additive hasColumn guards to ensure all columns from
 * both eras are present without data loss or duplication.
 *
 * Replaces: 2026_05_21_030003_create_quota_enforcement_settings_v2_table.php
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        // Table must already exist from the original migration.
        // We only add columns that are missing, preserving all existing data.
        if (! Schema::connection('central')->hasTable('quota_enforcement_settings')) {
            return;
        }

        Schema::connection('central')->table('quota_enforcement_settings', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('quota_enforcement_settings', 'resource')) {
                $table->string('resource', 64)->nullable()->after('id');
            }

            if (! Schema::connection('central')->hasColumn('quota_enforcement_settings', 'default_limit')) {
                $table->bigInteger('default_limit')->default(0)->after('resource');
            }

            if (! Schema::connection('central')->hasColumn('quota_enforcement_settings', 'warning_threshold_pct')) {
                $table->unsignedTinyInteger('warning_threshold_pct')->default(80)->after('default_limit');
            }

            if (! Schema::connection('central')->hasColumn('quota_enforcement_settings', 'hard_limit_pct')) {
                $table->unsignedTinyInteger('hard_limit_pct')->default(100)->after('warning_threshold_pct');
            }

            if (! Schema::connection('central')->hasColumn('quota_enforcement_settings', 'action')) {
                $table->string('action', 16)->default('warn')->after('hard_limit_pct');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('quota_enforcement_settings')) {
            return;
        }

        Schema::connection('central')->table('quota_enforcement_settings', function (Blueprint $table) {
            $columns = ['resource', 'default_limit', 'warning_threshold_pct', 'hard_limit_pct', 'action'];

            foreach ($columns as $column) {
                if (Schema::connection('central')->hasColumn('quota_enforcement_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
