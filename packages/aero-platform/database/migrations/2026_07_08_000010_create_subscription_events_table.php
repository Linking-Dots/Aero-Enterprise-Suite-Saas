<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * subscription_events — the priced MRR-movement ledger.
 *
 * Every lifecycle change that moves recurring revenue records one row with the
 * signed monthly delta (mrr_delta) and a movement category, so analytics can
 * report real new / expansion / contraction / churn instead of reconstructing
 * only new+churn from created_at/cancelled_at. Central-owned like subscriptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('subscription_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('subscription_id', 36)->index();
            $table->string('kind', 16)->default('plan'); // plan | product
            $table->string('tenant_id')->nullable()->index();
            $table->string('event_type', 40);            // created|upgraded|downgraded|cycle_changed|cancelled|reactivated|trial_converted
            $table->string('movement', 16);              // new|expansion|contraction|churn|neutral
            $table->decimal('old_mrr', 12, 2)->default(0);
            $table->decimal('new_mrr', 12, 2)->default(0);
            $table->decimal('mrr_delta', 12, 2)->default(0); // signed monthly delta
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['movement', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('subscription_events');
    }
};
