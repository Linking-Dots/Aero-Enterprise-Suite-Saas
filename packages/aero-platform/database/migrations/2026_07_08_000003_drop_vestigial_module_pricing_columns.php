<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the two vestigial pricing columns on `modules` that were only ever
 * written by ModuleAdminService::updatePricing — a dead editor (now removed)
 * that wrote non-billed values. Real, billed pricing lives on `products`
 * (products.monthly_price / yearly_price); the module registry carries no price.
 *
 * SCOPE NOTE: Only these two columns are dropped. The other legacy pricing/stripe
 * columns on `modules` (monthly_price, yearly_price, stripe_*) are left in place —
 * their read-sites could not be disentangled from products/plans usage with
 * confidence, and dropping on uncertainty would risk the billing/registration
 * paths. They are harmless-redundant and can be removed later under a dedicated,
 * individually-verified refactor.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $columns = ['price_monthly', 'price_annual'];

    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table): void {
            foreach ($this->columns as $col) {
                if (Schema::hasColumn('modules', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table): void {
            if (! Schema::hasColumn('modules', 'price_monthly')) {
                $table->decimal('price_monthly', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('modules', 'price_annual')) {
                $table->decimal('price_annual', 10, 2)->nullable();
            }
        });
    }
};
