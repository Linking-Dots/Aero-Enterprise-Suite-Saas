<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tenant_entitlements — append-only ledger of module-access grants/revocations.
 *
 * This is the AUDIT record ("who could use what module, when, and why"), giving
 * us Stripe-parity active-entitlement history. It is NOT the enforcement source:
 * ModuleEntitlementService still computes live entitlement from active
 * subscriptions / licenses. The resolver additionally UNIONS any open
 * source=override rows (comp / trial / grandfather granted outside a purchase).
 *
 * tenant_id is a plain nullable string (no FK): standalone installs have no
 * tenants table, and writers guard on Schema::hasTable, so this ledger is a
 * best-effort record that never blocks entitlement resolution (fail-open).
 *
 *   source:  subscription | license | override | baseline
 *   open row = revoked_at IS NULL  (currently entitled by that source)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_entitlements', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('module_code');
            $table->string('source');
            $table->string('source_id')->nullable();
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'module_code']);
            $table->index(['tenant_id', 'revoked_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_entitlements');
    }
};
