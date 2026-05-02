<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unify subscriptions table for Cashier v15 single source of truth.
 *
 * Adds polymorphic billable columns required by Laravel Cashier v15,
 * plus missing columns referenced by lifecycle service.
 * Populates billable columns from existing tenant_id for backward compatibility.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Cashier v15 polymorphic billable columns
            if (! Schema::hasColumn('subscriptions', 'billable_type')) {
                $table->string('billable_type')
                    ->default(\Aero\Platform\Models\Tenant::class)
                    ->after('id');
            }
            if (! Schema::hasColumn('subscriptions', 'billable_id')) {
                $table->string('billable_id')->nullable()->after('billable_type');
            }

            // Cashier v15 quantity column
            if (! Schema::hasColumn('subscriptions', 'quantity')) {
                $table->integer('quantity')->nullable()->default(1)->after('stripe_price');
            }

            // Missing lifecycle column referenced by service but never migrated
            if (! Schema::hasColumn('subscriptions', 'next_billing_date')) {
                $table->timestamp('next_billing_date')->nullable()->after('current_period_start');
            }

            // Cashier v15 subscription name column (may already be present as 'type')
            if (! Schema::hasColumn('subscriptions', 'name')) {
                $table->string('name')->nullable()->after('type');
            }

            // Indexes for Cashier queries
            $table->index(['billable_type', 'billable_id']);
        });

        // Backfill billable_id from tenant_id so Cashier can locate existing rows
        if (Schema::hasColumn('subscriptions', 'tenant_id')) {
            \Illuminate\Support\Facades\DB::table('subscriptions')
                ->whereNull('billable_id')
                ->update([
                    'billable_id' => \Illuminate\Support\Facades\DB::raw('tenant_id'),
                    'billable_type' => \Aero\Platform\Models\Tenant::class,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['billable_type', 'billable_id']);
            $table->dropColumn(['billable_type', 'billable_id', 'quantity', 'next_billing_date', 'name']);
        });
    }
};
