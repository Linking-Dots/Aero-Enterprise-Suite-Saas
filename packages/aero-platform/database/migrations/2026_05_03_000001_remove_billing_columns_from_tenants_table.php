<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove billing/subscription columns from tenants table.
 *
 * Tenant should store only identity data. Billing state moves to:
 * - subscriptions table (plan_id, billing_cycle, trial_ends_at)
 * - subscription_modules table (module add-ons)
 * - tenant_module pivot (feature access, not billing)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite does not support dropping columns that have implicit FK indexes.
        // In a fresh test DB these columns do not exist anyway, so skip safely.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            // Drop plan FK first
            if (Schema::hasColumn('tenants', 'plan_id')) {
                // Drop foreign key if it exists (Laravel doesn't have easy FK introspection,
                // so we wrap in try/catch for safety)
                try {
                    $table->dropForeign(['plan_id']);
                } catch (\Throwable $e) {
                    // FK may not exist under some DB names
                }
                $table->dropColumn('plan_id');
            }

            if (Schema::hasColumn('tenants', 'subscription_plan')) {
                $table->dropColumn('subscription_plan');
            }

            if (Schema::hasColumn('tenants', 'modules')) {
                $table->dropColumn('modules');
            }

            if (Schema::hasColumn('tenants', 'trial_ends_at')) {
                $table->dropColumn('trial_ends_at');
            }

            if (Schema::hasColumn('tenants', 'subscription_ends_at')) {
                $table->dropColumn('subscription_ends_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->uuid('plan_id')->nullable()->after('phone');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
            $table->index('plan_id');

            $table->string('subscription_plan')->nullable()->after('plan_id');
            $table->json('modules')->nullable()->after('subscription_plan');
            $table->date('trial_ends_at')->nullable()->after('modules');
            $table->date('subscription_ends_at')->nullable()->after('trial_ends_at');
        });
    }
};
