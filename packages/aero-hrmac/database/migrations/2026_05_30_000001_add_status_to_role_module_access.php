<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit D17 — soft-suspend role grants on product unsubscribe.
 *
 * Adds a `status` enum and `suspended_at` timestamp to role_module_access.
 * Default is 'active' so all existing rows are unaffected.
 *
 * Lifecycle:
 *  - Subscription cancelled  → SuspendUnsubscribedRoleAccess sets status='suspended', suspended_at=now()
 *  - Re-subscribed (≤30 days)→ ReactivateRoleAccessOnResubscribe flips back to status='active'
 *  - After 30 days           → PurgeSuspendedRoleAccess hard-deletes the row
 *
 * This migration runs per-tenant (stancl/tenancy applies it inside each tenant DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        // The base aero-core create_role_module_access migration now ships these
        // columns so standalone has them. Guard here so this (per-tenant) migration
        // is a no-op when they already exist.
        if (Schema::hasColumn('role_module_access', 'status')) {
            return;
        }
        Schema::table('role_module_access', function (Blueprint $t) {
            $t->enum('status', ['active', 'suspended'])->default('active')->after('access_scope');
            $t->timestamp('suspended_at')->nullable()->after('status');
            $t->index(['status', 'suspended_at'], 'rma_status_suspended_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('role_module_access', function (Blueprint $t) {
            $t->dropIndex('rma_status_suspended_at_index');
            $t->dropColumn(['status', 'suspended_at']);
        });
    }
};
