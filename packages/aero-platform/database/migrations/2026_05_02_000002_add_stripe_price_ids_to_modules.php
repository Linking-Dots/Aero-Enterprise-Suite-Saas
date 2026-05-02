<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Stripe Price IDs to modules table for module-level billing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->string('stripe_monthly_price_id')->nullable()->after('monthly_price');
            $table->string('stripe_yearly_price_id')->nullable()->after('stripe_monthly_price_id');
            $table->string('stripe_product_id')->nullable()->after('stripe_yearly_price_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_monthly_price_id',
                'stripe_yearly_price_id',
                'stripe_product_id',
            ]);
        });
    }
};
