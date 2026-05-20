<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        Schema::connection('central')->table('subscriptions', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('subscriptions', 'current_period_end')) {
                $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            }
            if (! Schema::connection('central')->hasColumn('subscriptions', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('cancelled_at');
            }
            if (! Schema::connection('central')->hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('stripe_id');
            }
        });

        // Add composite index on [tenant_id, status] if missing
        try {
            $sm = Schema::connection('central')->getConnection()->getDoctrineSchemaManager();
            $indexes = array_keys($sm->listTableIndexes('subscriptions'));
            if (! in_array('subscriptions_tenant_id_status_index', $indexes, true)) {
                Schema::connection('central')->table('subscriptions', function (Blueprint $table) {
                    $table->index(['tenant_id', 'status'], 'subscriptions_tenant_id_status_index');
                });
            }
        } catch (\Throwable) {
            // Ignore if doctrine schema manager unavailable
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('subscriptions', function (Blueprint $table) {
            $cols = array_filter([
                Schema::connection('central')->hasColumn('subscriptions', 'current_period_end') ? 'current_period_end' : null,
                Schema::connection('central')->hasColumn('subscriptions', 'cancel_reason') ? 'cancel_reason' : null,
                Schema::connection('central')->hasColumn('subscriptions', 'stripe_subscription_id') ? 'stripe_subscription_id' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
