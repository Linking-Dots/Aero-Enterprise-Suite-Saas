<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a scope column to distinguish platform vs tenant modules:
     * - 'platform': Modules for Platform Admin (10 modules from platform_hierarchy)
     * - 'tenant': Modules for Tenant users (5 modules from hierarchy)
     */
    public function up(): void
    {
        // Guard: table may not exist in platform-only test environments.
        if (! Schema::hasTable('modules')) {
            return;
        }

        // Guard: core's create_modules_table already includes 'scope' column
        if (Schema::hasColumn('modules', 'scope')) {
            return;
        }

        Schema::table('modules', function (Blueprint $table) {
            // string, not enum: module configs use additional scopes like
            // 'infrastructure' (auth/core/etc) — a 2-value enum truncates them and
            // aborts aero:sync-module. aero-core/aero-hrmac already use string.
            $table->string('scope')->default('tenant')->after('code');
            $table->index('scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['scope']);
            $table->dropColumn('scope');
        });
    }
};
