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
 * Reactivates suspended role_module_access grants when a tenant re-subscribes
 * to a product within the 30-day grace window.
 *
 * Audit D17 — soft-suspend role grants on product unsubscribe.
 *
 * When a product subscription is reactivated:
 *  1. We resolve the product's module_code to a Module row in the tenant DB.
 *  2. All role_module_access rows with status='suspended' AND suspended_at within
 *     the past 30 days are flipped back to status='active' with suspended_at=null.
 *  3. Rows older than 30 days have already been hard-deleted by PurgeSuspendedRoleAccess
 *     and cannot be restored. The customer must reconfigure their RBAC in that case.
 *
 * This listener is queued (ShouldQueue) so it does not stall the webhook handler
 * that reactivated the subscription (Stripe has a 10-second response deadline).
 */
class ReactivateRoleAccessOnResubscribe implements ShouldQueue
{
    use InteractsWithQueue;

    /** Queue worker retry count before the job is marked failed. */
    public int $tries = 3;

    /** Seconds between retries. */
    public int $backoff = 30;

    public function handle(ProductSubscriptionChanged $event): void
    {
        if ($event->action !== 'reactivated') {
            return;
        }

        $tenant = Tenant::find($event->subscription->tenant_id);
        if (! $tenant) {
            return;
        }

        // Resolve product → module code from the central subscription record
        $productCode = $event->subscription->product->module_code ?? null;
        if (! $productCode) {
            Log::warning('ReactivateRoleAccessOnResubscribe: subscription has no module_code', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $event->subscription->id,
            ]);

            return;
        }

        try {
            tenancy()->initialize($tenant);

            $module = Module::where('code', $productCode)->first();
            if (! $module) {
                Log::info('ReactivateRoleAccessOnResubscribe: module not found in tenant DB — nothing to reactivate', [
                    'tenant_id' => $tenant->id,
                    'module_code' => $productCode,
                ]);

                return;
            }

            $subModuleIds = $module->subModules()->pluck('id');

            // Only rows within the 30-day grace window can be restored.
            // Rows older than 30 days were hard-deleted by PurgeSuspendedRoleAccess.
            $baseQuery = RoleModuleAccess::query()
                ->where(function ($q) use ($module, $subModuleIds) {
                    $q->where('module_id', $module->id)
                        ->orWhereIn('sub_module_id', $subModuleIds);
                })
                ->where('status', RoleModuleAccess::STATUS_SUSPENDED)
                ->where('suspended_at', '>=', now()->subDays(30));

            // Capture affected roles BEFORE the status flip.
            $affectedRoleIds = (clone $baseQuery)->pluck('role_id')->unique()->values();

            $affected = $baseQuery->update([
                'status' => RoleModuleAccess::STATUS_ACTIVE,
                'suspended_at' => null,
            ]);

            // Axis C C6/C8 — mass update() bypasses model events; clear each affected
            // role's HRMAC access cache so the restored grant authorizes immediately.
            $hrmac = app(\Aero\HRMAC\Services\RoleModuleAccessService::class);
            foreach ($affectedRoleIds as $roleId) {
                $hrmac->clearRoleCache($roleId);
            }

            Log::info('ReactivateRoleAccessOnResubscribe: role grants reactivated', [
                'tenant_id' => $tenant->id,
                'module_code' => $productCode,
                'rows_reactivated' => $affected,
                'roles_invalidated' => $affectedRoleIds->count(),
            ]);
        } catch (Throwable $e) {
            Log::error('ReactivateRoleAccessOnResubscribe failed', [
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
