<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Laravel Cashier required columns to the subscriptions table.
 *
 * The subscriptions table already stores our custom lifecycle fields.
 * Cashier requires polymorphic billable columns and Stripe-specific
 * fields so that Tenant->newSubscription() and subscription('default')
 * work natively.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Cashier polymorphic billable columns (required for Tenant->subscription())
            if (! Schema::hasColumn('subscriptions', 'billable_type')) {
                $table->string('billable_type')->index()->after('id');
            }
            if (! Schema::hasColumn('subscriptions', 'billable_id')) {
                $table->string('billable_id')->index()->after('billable_type');
            }

            // Cashier subscription name (e.g. 'default', 'premium')
            if (! Schema::hasColumn('subscriptions', 'name')) {
                $table->string('name')->default('default')->after('billable_id');
            }

            // Stripe columns required by Cashier
            if (! Schema::hasColumn('subscriptions', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->index()->after('name');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_status')) {
                $table->string('stripe_status')->nullable()->after('stripe_id');
            }
            if (! Schema::hasColumn('subscriptions', 'stripe_price')) {
                $table->string('stripe_price')->nullable()->after('stripe_status');
            }
            if (! Schema::hasColumn('subscriptions', 'quantity')) {
                $table->integer('quantity')->nullable()->after('stripe_price');
            }

            // Add a composite index that Cashier queries use
            $table->index(['billable_type', 'billable_id', 'name'], 'subs_billable_name_idx');
        });

        // Remove legacy tenant_id FK if it exists — billing moves to subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            // Laravel doesn't expose a clean way to check if a FK exists,
            // so we drop it by convention name or wrap in try/catch.
            try {
                $table->dropForeign(['tenant_id']);
            } catch (\Throwable $e) {
                // FK may not exist under this name
            }

            // Make tenant_id nullable so existing rows don't break,
            // then we can drop it in a future migration after data migration.
            if (Schema::hasColumn('subscriptions', 'tenant_id')) {
                $table->string('tenant_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subs_billable_name_idx');

            if (Schema::hasColumn('subscriptions', 'billable_type')) {
                $table->dropColumn('billable_type');
            }
            if (Schema::hasColumn('subscriptions', 'billable_id')) {
                $table->dropColumn('billable_id');
            }
            if (Schema::hasColumn('subscriptions', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('subscriptions', 'stripe_id')) {
                $table->dropColumn('stripe_id');
            }
            if (Schema::hasColumn('subscriptions', 'stripe_status')) {
                $table->dropColumn('stripe_status');
            }
            if (Schema::hasColumn('subscriptions', 'stripe_price')) {
                $table->dropColumn('stripe_price');
            }
            if (Schema::hasColumn('subscriptions', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
