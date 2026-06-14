<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P-8 — Advanced Billing
 *
 * Tables: coupon_campaigns, coupons, coupon_redemptions, platform_addons,
 *         usage_meters, usage_events, refunds, credit_notes, dunning_rules
 *
 * All tables live in the central database.
 */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'central';
    }

    public function up(): void
    {
        // -----------------------------------------------------------------------
        // coupon_campaigns (must be created before coupons FK)
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('coupon_campaigns')) {
            Schema::connection('central')->create('coupon_campaigns', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->text('description')->nullable();
                $t->enum('status', ['draft', 'active', 'ended'])->default('draft');
                $t->timestamp('starts_at')->nullable();
                $t->timestamp('ends_at')->nullable();
                $t->foreignId('created_by')->constrained('landlord_users');
                $t->timestamps();
            });
        }

        // -----------------------------------------------------------------------
        // coupons
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('coupons')) {
            Schema::connection('central')->create('coupons', function (Blueprint $t) {
                $t->id();
                $t->string('code', 64)->unique();
                $t->string('name');
                $t->enum('type', ['percent', 'fixed'])->default('percent');
                $t->decimal('value', 10, 2);
                $t->string('currency', 8)->nullable();
                $t->enum('duration', ['once', 'repeating', 'forever'])->default('once');
                $t->unsignedSmallInteger('duration_months')->nullable();
                $t->unsignedInteger('max_redemptions')->nullable();
                $t->unsignedInteger('redemption_count')->default(0);
                $t->timestamp('expires_at')->nullable();
                $t->enum('status', ['active', 'archived'])->default('active');
                $t->foreignId('campaign_id')->nullable()->constrained('coupon_campaigns')->nullOnDelete();
                $t->foreignId('created_by')->constrained('landlord_users');
                $t->timestamps();
                $t->index(['status', 'expires_at']);
            });
        }

        // -----------------------------------------------------------------------
        // coupon_redemptions (polymorphic subscribable)
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('coupon_redemptions')) {
            Schema::connection('central')->create('coupon_redemptions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('coupon_id')->constrained('coupons');
                $t->string('tenant_id');
                $t->nullableMorphs('subscribable'); // Subscription | ProductSubscription
                $t->decimal('discount_applied', 10, 2);
                $t->timestamp('redeemed_at');
                $t->timestamps();
                $t->index(['coupon_id', 'tenant_id']);
            });
        }

        // -----------------------------------------------------------------------
        // platform_addons
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('platform_addons')) {
            Schema::connection('central')->create('platform_addons', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->text('description')->nullable();
                $t->decimal('price', 10, 2)->default(0);
                $t->string('billing_period', 24)->default('monthly');
                $t->enum('status', ['active', 'archived'])->default('active');
                $t->foreignId('created_by')->constrained('landlord_users');
                $t->timestamps();
            });
        }

        // -----------------------------------------------------------------------
        // usage_meters
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('usage_meters')) {
            Schema::connection('central')->create('usage_meters', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('event_code');
                $t->enum('aggregation', ['sum', 'count', 'max'])->default('count');
                $t->decimal('price_per_unit', 12, 6)->default(0);
                $t->string('unit_label', 32)->default('unit');
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }

        // -----------------------------------------------------------------------
        // usage_events
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('usage_events')) {
            Schema::connection('central')->create('usage_events', function (Blueprint $t) {
                $t->id();
                $t->foreignId('meter_id')->constrained('usage_meters');
                $t->string('tenant_id');
                $t->decimal('quantity', 12, 4)->default(1);
                $t->json('metadata')->nullable();
                $t->timestamp('occurred_at');
                $t->timestamps();
                $t->index(['meter_id', 'tenant_id', 'occurred_at']);
            });
        }

        // -----------------------------------------------------------------------
        // refunds
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('refunds')) {
            Schema::connection('central')->create('refunds', function (Blueprint $t) {
                $t->id();
                $t->string('reference')->unique();
                $t->string('tenant_id');
                $t->foreignId('invoice_id')->nullable();
                $t->decimal('amount', 12, 2);
                $t->string('currency', 8)->default('USD');
                $t->text('reason');
                $t->enum('status', ['pending', 'approved', 'processed', 'failed'])->default('pending');
                $t->string('gateway_refund_id')->nullable();
                $t->foreignId('requested_by')->constrained('landlord_users');
                $t->foreignId('approved_by')->nullable()->constrained('landlord_users');
                $t->foreignId('processed_by')->nullable()->constrained('landlord_users');
                $t->timestamp('approved_at')->nullable();
                $t->timestamp('processed_at')->nullable();
                $t->timestamps();
            });
        }

        // -----------------------------------------------------------------------
        // credit_notes
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('credit_notes')) {
            Schema::connection('central')->create('credit_notes', function (Blueprint $t) {
                $t->id();
                $t->string('reference')->unique();
                $t->string('tenant_id');
                $t->decimal('amount', 12, 2);
                $t->string('currency', 8)->default('USD');
                $t->text('reason');
                $t->decimal('amount_used', 12, 2)->default(0);
                $t->enum('status', ['open', 'partially_applied', 'fully_applied', 'voided'])->default('open');
                $t->foreignId('created_by')->constrained('landlord_users');
                $t->timestamps();
            });
        }

        // -----------------------------------------------------------------------
        // dunning_rules
        // -----------------------------------------------------------------------
        if (! Schema::connection('central')->hasTable('dunning_rules')) {
            Schema::connection('central')->create('dunning_rules', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->unsignedTinyInteger('day_offset');
                $t->enum('action', ['retry', 'email', 'suspend', 'mark_unpaid'])->default('retry');
                $t->foreignId('email_template_id')->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedSmallInteger('order_index')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('dunning_rules');
        Schema::connection('central')->dropIfExists('credit_notes');
        Schema::connection('central')->dropIfExists('refunds');
        Schema::connection('central')->dropIfExists('usage_events');
        Schema::connection('central')->dropIfExists('usage_meters');
        Schema::connection('central')->dropIfExists('platform_addons');
        Schema::connection('central')->dropIfExists('coupon_redemptions');
        Schema::connection('central')->dropIfExists('coupons');
        Schema::connection('central')->dropIfExists('coupon_campaigns');
    }
};
