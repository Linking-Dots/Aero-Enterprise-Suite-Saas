<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Webhook idempotency table.
     *
     * Stores processed webhook event IDs so duplicate deliveries
     * from Stripe (or other providers) are safely skipped.
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider', 50)->index(); // stripe, sslcommerz, etc.
            $table->string('event_id')->index();     // Stripe evt_xxx
            $table->string('event_type');             // customer.subscription.updated
            $table->string('status')->default('processed'); // processed, failed
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['provider', 'event_id'], 'webhook_events_provider_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
