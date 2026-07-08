<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills the tenant_entitlements ledger with the module grants implied by
 * currently active/trialing product subscriptions. RecordProductEntitlementLedger
 * only writes rows for subscription changes going FORWARD, so subscriptions that
 * predate that listener have no ledger history — this seeds them so the ledger
 * (and the Entitlements admin feed) reflects reality.
 *
 * Additive & idempotent: inserts one open grant per (subscription, module) only
 * when an open row for that pair doesn't already exist. Safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenant_entitlements') || ! Schema::hasTable('product_subscriptions')) {
            return;
        }

        $now = now();

        $subscriptions = DB::table('product_subscriptions as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->whereIn('ps.status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('ps.ends_at')->orWhere('ps.ends_at', '>', $now))
            ->where('p.is_active', true)
            ->get(['ps.id as sub_id', 'ps.tenant_id', 'p.id as product_id', 'p.module_code']);

        foreach ($subscriptions as $sub) {
            // Modules this subscription grants: pivot ∪ legacy scalar.
            $modules = DB::table('product_modules')->where('product_id', $sub->product_id)->pluck('module_code')->all();
            if ($sub->module_code) {
                $modules[] = $sub->module_code;
            }
            $modules = array_values(array_unique(array_filter($modules)));

            foreach ($modules as $code) {
                $exists = DB::table('tenant_entitlements')
                    ->where('source', 'subscription')
                    ->where('source_id', (string) $sub->sub_id)
                    ->where('module_code', $code)
                    ->whereNull('revoked_at')
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('tenant_entitlements')->insert([
                    'tenant_id'   => $sub->tenant_id,
                    'module_code' => $code,
                    'source'      => 'subscription',
                    'source_id'   => (string) $sub->sub_id,
                    'granted_at'  => $now,
                    'revoked_at'  => null,
                    'reason'      => 'backfilled from active subscription',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_entitlements')) {
            DB::table('tenant_entitlements')
                ->where('source', 'subscription')
                ->where('reason', 'backfilled from active subscription')
                ->delete();
        }
    }
};
