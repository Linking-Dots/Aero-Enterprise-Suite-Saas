<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P-3 quota enforcement settings — new schema with resource/default_limit columns.
 * The original table (2025_12_24) uses quota_type and notification columns.
 * This migration adds a separate P-3 schema; QuotaEnforcementSetting model is updated
 * to use whichever table is present. If the table already exists, the migration is a no-op.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        // The original migration already created quota_enforcement_settings with a
        // different schema. We only add the new columns if they are missing, so both
        // the old and new columns coexist and the model can use the new ones.
        if (! Schema::connection('central')->hasColumn('quota_enforcement_settings', 'resource')) {
            Schema::connection('central')->table('quota_enforcement_settings', function (Blueprint $table) {
                $table->string('resource', 64)->nullable()->after('id');
                $table->bigInteger('default_limit')->default(0)->after('resource');
                $table->unsignedTinyInteger('hard_limit_pct')->default(100)->after('warning_threshold_percentage');
                $table->string('action', 16)->default('warn')->after('hard_limit_pct');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('central')->hasColumn('quota_enforcement_settings', 'resource')) {
            Schema::connection('central')->table('quota_enforcement_settings', function (Blueprint $table) {
                $table->dropColumn(['resource', 'default_limit', 'hard_limit_pct', 'action']);
            });
        }
    }
};
