<?php

declare(strict_types=1);

namespace Aero\Platform\Listeners;

use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Suspends every role_module_access grant that references a module belonging to
 * a cancelled product subscription.
 *
 * Audit D17 — soft-suspend role grants on product unsubscribe.
 *
 * When a product subscription is cancelled:
 *  1. We resolve the product's module_code to a Module row in the tenant DB.
 *  2. All role_module_access rows pointing at that module OR any of its sub_modules
 *     are flipped from status='active' to status='suspended' with suspended_at=now().
 *  3. The rows are NOT deleted — they survive for a 30-day grace period so that
 *     re-subscribing within the window can restore access without reconfiguration.
 *  4. PurgeSuspendedRoleAccess (scheduled daily) hard-deletes rows older than 30 days.
 *
 * This listener is queued (ShouldQueue) so it does not stall the webhook handler
 * that cancelled the subscription (Stripe has a 10-second response deadline).
 */
class SuspendUnsubscribedRoleAccess implements ShouldQueue
{
    use InteractsWithQueue;

    /** Queue worker retry count before the job is marked failed. */
    public int $tries = 3;

    /** Seconds between retries. */
    public int $backoff = 30;

    public function handle(ProductSubscriptionChanged $event): void
    {
        if ($event->action !== 'cancelled') {
            return;
        }

        $tenant = Tenant::find($event->subscription->tenant_id);
        if (! $tenant) {
            return;
        }

        // Resolve product → module code from the central subscription record
        $productCode = $event->subscription->product->module_code ?? null;
        if (! $productCode) {
            Log::warning('SuspendUnsubscribedRoleAccess: subscription has no module_code', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $event->subscription->id,
            ]);

            return;
        }

        try {
            tenancy()->initialize($tenant);

            $module = Module::where('code', $productCode)->first();
            if (! $module) {
                Log::info('SuspendUnsubscribedRoleAccess: module not found in tenant DB — nothing to suspend', [
                    'tenant_id' => $tenant->id,
                    'module_code' => $productCode,
                ]);

                return;
            }

            $subModuleIds = $module->subModules()->pluck('id');

            $baseQuery = RoleModuleAccess::query()
                ->where(function ($q) use ($module, $subModuleIds) {
                    $q->where('module_id', $module->id)
                        ->orWhereIn('sub_module_id', $subModuleIds);
                })
                ->where('status', RoleModuleAccess::STATUS_ACTIVE);

            // Capture affected roles BEFORE the status flip (after it, they no
            // longer match status=active).
            $affectedRoleIds = (clone $baseQuery)->pluck('role_id')->unique()->values();

            $affected = $baseQuery->update([
                'status' => RoleModuleAccess::STATUS_SUSPENDED,
                'suspended_at' => now(),
            ]);

            // Axis C C6/C8 — mass update() bypasses model events, so the HRMAC
            // access cache isn't invalidated automatically. Clear each affected
            // role explicitly (in tenant context) so a suspended grant stops
            // authorizing immediately instead of after CACHE_TTL.
            $hrmac = app(\Aero\HRMAC\Services\RoleModuleAccessService::class);
            foreach ($affectedRoleIds as $roleId) {
                $hrmac->clearRoleCache($roleId);
            }

            Log::info('SuspendUnsubscribedRoleAccess: role grants suspended', [
                'tenant_id' => $tenant->id,
                'module_code' => $productCode,
                'rows_suspended' => $affected,
                'roles_invalidated' => $affectedRoleIds->count(),
            ]);
        } catch (Throwable $e) {
            Log::error('SuspendUnsubscribedRoleAccess failed', [
                'tenant_id' => $tenant->id ?? null,
                'subscription_id' => $event->subscription->id,
                'module_code' => $productCode ?? null,
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
