<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoices table — replaces storing invoices inside tenant.data JSON.
     *
     * Separate from Stripe invoices: this tracks both Stripe-originated
     * and locally-generated invoices (e.g. SSLCommerz, manual adjustments).
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic billable (Tenant or future billable types)
            $table->string('billable_type');
            $table->string('billable_id');
            $table->index(['billable_type', 'billable_id'], 'invoices_billable_index');

            // Optional link to plan subscription or module subscription
            $table->uuid('subscription_id')->nullable()->index();
            $table->uuid('subscription_module_id')->nullable()->index();

            // Invoice details
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft'); // draft, issued, paid, void, overdue, refunded
            $table->string('currency', 10)->default('usd');

            // Amounts (DECIMAL for financial precision)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);

            // Billing period
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();

            // Payment
            $table->string('payment_method')->nullable(); // stripe, sslcommerz, manual
            $table->string('external_invoice_id')->nullable()->index(); // Stripe invoice ID, etc.
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();

            $table->string('type'); // subscription, module, usage, addon, adjustment, tax
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);

            // Optional references
            $table->uuid('plan_id')->nullable();
            $table->string('module_code')->nullable();

            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
    }
};
