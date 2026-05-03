<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module add-on subscriptions table.
 *
 * Stores individual module subscriptions separately from the base plan subscription.
 * A tenant has one base plan subscription (subscriptions table) and zero or more
 * module add-ons (subscription_modules table), each with independent billing cycle,
 * price, and status.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_modules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic link to the billable entity (usually a Tenant)
            $table->string('billable_type', 100);
            $table->string('billable_id', 100);

            // Module reference (matches module_pricing.module_code)
            $table->string('module_code', 50);

            // Billing
            $table->string('billing_cycle'); // monthly, yearly
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Status lifecycle
            $table->string('status', 20)->default('active'); // active, cancelled, past_due, trialing, paused, expired
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // Payment gateway references
            $table->string('payment_method')->nullable(); // stripe, paypal, etc.
            $table->string('external_subscription_id')->nullable(); // Stripe subscription item / invoice line item ID
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['billable_type', 'billable_id']);
            $table->index('module_code');
            $table->index('status');
            $table->index('ends_at');
            $table->index(['billable_type', 'billable_id', 'status']);
            $table->index(['billable_type', 'billable_id', 'module_code', 'status'], 'sub_mod_bill_mod_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_modules');
    }
};
