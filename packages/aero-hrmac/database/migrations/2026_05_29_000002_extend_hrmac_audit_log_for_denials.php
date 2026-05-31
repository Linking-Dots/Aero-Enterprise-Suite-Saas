<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan 04 (aero-hrmac) Task 1 — extend hrmac_audit_log to capture access denials.
 *
 * The original table was purpose-built for ROLE MUTATION events (role_id required,
 * action default 'sync', before_state / after_state JSON). Phase 1 audit found that
 * CheckRoleModuleAccess::handle() only Log::warning()s denials — they never reach the
 * structured table. This migration adds the columns needed to record access-denial
 * events alongside role mutations, distinguished by the new `event` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrmac_audit_log', function (Blueprint $table) {
            // Mark what kind of event this is: role mutation vs access denial vs grant
            $table->string('event', 32)->default('role_mutation')->after('id')->index();

            // Access denial / grant carries the HRMAC path the user attempted
            $table->string('module_code', 64)->nullable()->after('action');
            $table->string('sub_module_code', 64)->nullable()->after('module_code');
            $table->string('component_code', 64)->nullable()->after('sub_module_code');
            $table->string('action_code', 64)->nullable()->after('component_code');

            // HTTP context for denial forensics
            $table->string('path', 512)->nullable()->after('user_agent');
            $table->string('method', 10)->nullable()->after('path');

            $table->index(['event', 'created_at'], 'hrmac_audit_event_created_idx');
            $table->index(['module_code', 'sub_module_code'], 'hrmac_audit_module_idx');
        });

        // role_id was previously NOT NULL — relax it so denial events (which have no role mutation)
        // can be inserted. MySQL change column syntax.
        Schema::table('hrmac_audit_log', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hrmac_audit_log', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->dropIndex('hrmac_audit_event_created_idx');
            $table->dropIndex('hrmac_audit_module_idx');
            $table->dropColumn(['event', 'module_code', 'sub_module_code', 'component_code', 'action_code', 'path', 'method']);
        });
    }
};
