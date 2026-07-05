<?php

declare(strict_types=1);

namespace Aero\Platform\Listeners;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Re-syncs a tenant's module catalog after a product subscription change.
 *
 * Runs on the queue (ShouldQueue) so a slow sync doesn't stall the request
 * that mutated the subscription — important when the mutation comes from a
 * Stripe webhook (10s timeout before retry).
 *
 * Failure handling: if `Artisan::call` returns non-zero (lock contention,
 * config validation failure) OR throws (DB unreachable, tenant missing),
 * the error is reported to the logger AND re-thrown so the queue worker
 * retries the job per its default backoff. The subscription row is already
 * committed at this point; without this guarantee the catalog can drift
 * from the subscription state.
 */
class ResyncTenantModuleCatalog implements ShouldQueue
{
    use InteractsWithQueue;

    /** Queue worker retry count before the job is marked failed. */
    public int $tries = 3;

    /** Seconds between retries. */
    public int $backoff = 30;

    public function handle(ProductSubscriptionChanged $event): void
    {
        $tenant = Tenant::find($event->subscription->tenant_id);
        if (! $tenant) {
            return;
        }

        // During registration/provisioning the tenant database does not exist yet,
        // and the ProvisionTenant job performs the initial module sync from the
        // tenant's product subscriptions. Attempting to initialize a non-existent
        // tenant DB here throws (and then `Tenancy::end()` in finally throws a
        // secondary "Undefined array key" fatal), which would roll back the
        // registration transaction. Only resync once the tenant is live.
        if ($tenant->status !== Tenant::STATUS_ACTIVE) {
            return;
        }

        // Axis C C2 — bust the per-tenant subscribed-modules cache read on every
        // page load by HandleInertiaRequests, so the new catalog is reflected
        // immediately rather than after the 600s TTL.
        Cache::forget("tenant_subscribed_modules:{$tenant->getTenantKey()}");

        try {
            tenancy()->initialize($tenant);

            $exitCode = Artisan::call('aero:sync-module', ['--scope' => 'tenant']);

            if ($exitCode !== 0) {
                Log::warning('aero:sync-module returned non-zero exit code', [
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $event->subscription->id,
                    'action' => $event->action,
                    'exit_code' => $exitCode,
                ]);

                throw new \RuntimeException(
                    "aero:sync-module failed with exit code {$exitCode} for tenant {$tenant->id}"
                );
            }
        } catch (Throwable $e) {
            Log::error('ResyncTenantModuleCatalog listener failed', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $event->subscription->id,
                'action' => $event->action,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }
}
