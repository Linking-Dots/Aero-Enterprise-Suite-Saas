<?php

namespace Aero\Platform\Jobs;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\WebhookEventDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk Tenant Operations Job
 *
 * Performs bulk operations on multiple tenants (suspend, activate, delete)
 */
class BulkTenantOperationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes for bulk operations

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $tenantIds,
        public string $operation,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookEventDispatcher $eventDispatcher): void
    {
        Log::info('Starting bulk tenant operation', [
            'operation' => $this->operation,
            'tenant_count' => count($this->tenantIds),
        ]);

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($this->tenantIds as $tenantId) {
            try {
                DB::beginTransaction();

                $tenant = Tenant::find($tenantId);

                if (! $tenant) {
                    $results['failed']++;
                    $results['errors'][] = "Tenant {$tenantId} not found";

                    continue;
                }

                $success = match ($this->operation) {
                    'suspend' => $this->suspendTenant($tenant, $eventDispatcher),
                    'activate' => $this->activateTenant($tenant, $eventDispatcher),
                    'delete' => $this->deleteTenant($tenant, $eventDispatcher),
                    'update_plan' => $this->updateTenantPlan($tenant, $eventDispatcher),
                    'reset_quota' => $this->resetTenantQuota($tenant, $eventDispatcher),
                    default => throw new \InvalidArgumentException("Unknown operation: {$this->operation}"),
                };

                if ($success) {
                    $results['success']++;
                    DB::commit();
                } else {
                    $results['failed']++;
                    DB::rollBack();
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed']++;
                $results['errors'][] = "Tenant {$tenantId}: {$e->getMessage()}";

                Log::error("Bulk operation failed for tenant {$tenantId}", [
                    'operation' => $this->operation,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Completed bulk tenant operation', [
            'operation' => $this->operation,
            'results' => $results,
        ]);
    }

    /**
     * Suspend tenant
     */
    protected function suspendTenant(Tenant $tenant, WebhookEventDispatcher $eventDispatcher): bool
    {
        if ($tenant->status === 'suspended') {
            return true; // Already suspended
        }

        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $this->options['reason'] ?? 'Bulk suspension',
        ]);

        // Dispatch webhook event
        $eventDispatcher->dispatch('tenant.suspended', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'reason' => $this->options['reason'] ?? null,
            'suspended_at' => $tenant->suspended_at->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Activate tenant
     */
    protected function activateTenant(Tenant $tenant, WebhookEventDispatcher $eventDispatcher): bool
    {
        if ($tenant->status === 'active') {
            return true; // Already active
        }

        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Dispatch webhook event
        $eventDispatcher->dispatch('tenant.activated', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'activated_at' => now()->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Delete tenant
     */
    protected function deleteTenant(Tenant $tenant, WebhookEventDispatcher $eventDispatcher): bool
    {
        // Soft delete
        if ($this->options['soft_delete'] ?? true) {
            $tenant->delete();

            $eventDispatcher->dispatch('tenant.deleted', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'deleted_at' => now()->toIso8601String(),
                'soft_delete' => true,
            ]);

            return true;
        }

        // Hard delete (requires confirmation)
        if (($this->options['confirm_hard_delete'] ?? false) === true) {
            $tenant->forceDelete();

            $eventDispatcher->dispatch('tenant.deleted', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'deleted_at' => now()->toIso8601String(),
                'soft_delete' => false,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Update tenant plan
     */
    protected function updateTenantPlan(Tenant $tenant, WebhookEventDispatcher $eventDispatcher): bool
    {
        if (! isset($this->options['plan_id'])) {
            throw new \InvalidArgumentException('plan_id is required for update_plan operation');
        }

        $subscription = $tenant->subscription('default');
        $oldPlanId = $subscription?->plan_id;

        if ($subscription) {
            $subscription->update([
                'plan_id' => $this->options['plan_id'],
            ]);
        }

        $eventDispatcher->dispatch('tenant.updated', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'changes' => [
                'plan_id' => [
                    'old' => $oldPlanId,
                    'new' => $this->options['plan_id'],
                ],
            ],
        ]);

        return true;
    }

    /**
     * Reset tenant quota
     */
    protected function resetTenantQuota(Tenant $tenant, WebhookEventDispatcher $eventDispatcher): bool
    {
        $tenant->update([
            'current_users' => 0,
            'current_storage_gb' => 0,
            'current_api_calls' => 0,
        ]);

        $eventDispatcher->dispatch('quota.reset', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'reset_at' => now()->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk tenant operation job failed permanently', [
            'operation' => $this->operation,
            'tenant_count' => count($this->tenantIds),
            'exception' => $exception->getMessage(),
        ]);
    }
}
