<?php

declare(strict_types=1);

namespace Aero\Platform\Listeners;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Appends grant/revoke rows to the tenant_entitlements ledger as product
 * subscriptions change — the audit trail of "which modules a tenant could use,
 * when, and why" (Stripe active-entitlement parity).
 *
 * This is the AUDIT record, not the enforcement path: ModuleEntitlementService
 * still computes live entitlement from active subscriptions. The ledger is
 * therefore best-effort — every branch is guarded and swallows failure so a
 * ledger hiccup can never block a billing change.
 *
 * Runs synchronously on the central DB (tenant_entitlements is central; no tenant
 * DB init required), unlike ResyncTenantModuleCatalog which must boot tenancy.
 */
class RecordProductEntitlementLedger
{
    /** Actions that put the tenant INTO the entitled set. */
    private const GRANTING = ['created', 'reactivated'];

    /** Actions that take the tenant OUT of the entitled set. */
    private const REVOKING = ['cancelled'];

    public function handle(ProductSubscriptionChanged $event): void
    {
        try {
            if (! Schema::hasTable('tenant_entitlements')) {
                return;
            }

            $sub = $event->subscription;
            $moduleCodes = $this->productModuleCodes($sub->product_id);

            if ($moduleCodes === []) {
                return;
            }

            if (in_array($event->action, self::GRANTING, true)) {
                $this->grant($sub->tenant_id, (string) $sub->id, $moduleCodes);
            } elseif (in_array($event->action, self::REVOKING, true)) {
                $this->revoke((string) $sub->id);
            }
        } catch (Throwable $e) {
            // Ledger is best-effort; never break the subscription flow.
        }
    }

    /**
     * Module codes a product grants — pivot first, unioned with the legacy scalar
     * so a not-yet-backfilled product still records correctly during transition.
     *
     * @return array<int, string>
     */
    private function productModuleCodes(string $productId): array
    {
        $pivot = DB::table('product_modules')
            ->where('product_id', $productId)
            ->pluck('module_code')
            ->all();

        $scalar = (array) DB::table('products')
            ->where('id', $productId)
            ->value('module_code');

        return array_values(array_unique(array_filter(array_merge($pivot, $scalar))));
    }

    /**
     * Open a grant row per module for this subscription, unless one is already open
     * (idempotent — re-firing 'created'/'reactivated' won't duplicate).
     *
     * @param  array<int, string>  $moduleCodes
     */
    private function grant(?string $tenantId, string $subscriptionId, array $moduleCodes): void
    {
        $now = now();

        foreach ($moduleCodes as $code) {
            $alreadyOpen = DB::table('tenant_entitlements')
                ->where('source', 'subscription')
                ->where('source_id', $subscriptionId)
                ->where('module_code', $code)
                ->whereNull('revoked_at')
                ->exists();

            if ($alreadyOpen) {
                continue;
            }

            DB::table('tenant_entitlements')->insert([
                'tenant_id'   => $tenantId,
                'module_code' => $code,
                'source'      => 'subscription',
                'source_id'   => $subscriptionId,
                'granted_at'  => $now,
                'revoked_at'  => null,
                'reason'      => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    /** Close every open subscription grant for this subscription. */
    private function revoke(string $subscriptionId): void
    {
        $now = now();

        DB::table('tenant_entitlements')
            ->where('source', 'subscription')
            ->where('source_id', $subscriptionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $now,
                'updated_at' => $now,
            ]);
    }
}
