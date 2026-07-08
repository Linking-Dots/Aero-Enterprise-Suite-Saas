<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * product_modules — the many-to-many between a sellable Product and the technical
 * Modules it grants. Replaces the 1:1 products.module_code scalar so a single
 * Product can bundle several modules ("Ops Suite = HRM + Finance").
 *
 * Backfills one row per existing products.module_code so entitlement resolution
 * is unchanged the instant this lands (parity), before any resolver rewrite.
 *
 * The products.module_code column is intentionally KEPT for now (read during the
 * transition); it is dropped in the Phase 4 destructive cleanup only after the
 * resolver reads exclusively from this pivot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->string('module_code');
            $table->timestamps();

            $table->unique(['product_id', 'module_code']);
            $table->index('module_code');
            // modules.code is the natural key (unique). Restrict so a module that
            // is bundled in a product cannot be deleted out from under it.
            $table->foreign('module_code')->references('code')->on('modules')->onDelete('restrict');
        });

        // Backfill: every product's existing scalar module_code becomes a pivot row.
        $now = now();
        DB::table('products')
            ->whereNotNull('module_code')
            ->orderBy('id')
            ->get(['id', 'module_code'])
            ->each(function ($product) use ($now): void {
                DB::table('product_modules')->insertOrIgnore([
                    'product_id'  => $product->id,
                    'module_code' => $product->module_code,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_modules');
    }
};
