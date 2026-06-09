<?php

namespace Aero\Installation\Installation\Steps;

use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantProvisioningStep extends BaseInstallationStep
{
    protected string $mode;

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'tenant_provisioning';
    }

    public function description(): string
    {
        return 'Provision default tenant for SaaS mode';
    }

    public function order(): int
    {
        return 13;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'settings', 'admin'];
    }

    public function execute(): array
    {
        if ($this->mode !== 'saas') {
            return [
                'status' => 'skipped',
                'reason' => 'Not SaaS mode',
            ];
        }

        if (! Schema::hasTable('tenants')) {
            return [
                'status' => 'skipped',
                'reason' => 'tenants table does not exist',
            ];
        }

        // Defer actual tenant provisioning to a background worker or post-installation step
        // to avoid HTTP timeouts (as tenant database provisioning is a heavy operation).
        $pendingCount = Tenant::where('status', 'pending')->count();
        if ($pendingCount > 0) {
            $this->log("Found {$pendingCount} pending tenants. Skipping synchronous provisioning to prevent timeouts; they should be provisioned via queue/background jobs.");
            return [
                'status' => 'skipped',
                'reason' => "Deferred {$pendingCount} pending tenants to background queue to prevent HTTP timeout",
                'pending_count' => $pendingCount,
            ];
        }

        return [
            'status' => 'skipped',
            'reason' => 'No pending tenants to provision',
        ];
    }

    protected function provisionTenant(Tenant $tenant): void
    {
        $tenant->update([
            'status' => 'provisioning',
            'provisioning_step' => 'starting',
        ]);

        $jobClass = config('tenancy.jobs.ProvisionTenant', \Aero\Platform\Jobs\ProvisionTenant::class);

        if (class_exists($jobClass)) {
            try {
                dispatch(new $jobClass($tenant));
            } catch (\Throwable $e) {
                $tenant->update([
                    'status' => 'failed',
                    'data' => array_merge($tenant->data ?? [], [
                        'provisioning_error' => $e->getMessage(),
                    ]),
                ]);
                throw $e;
            }
        } else {
            $tenant->update([
                'status' => 'active',
                'provisioning_step' => null,
                'data' => array_merge($tenant->data ?? [], [
                    'admin_setup_completed' => false,
                ]),
            ]);
        }
    }

    public function validate(): bool
    {
        if ($this->mode !== 'saas') {
            return true;
        }

        try {
            return Schema::hasTable('tenants');
        } catch (\Exception) {
            return false;
        }
    }

    public function canSkip(): bool
    {
        return $this->mode !== 'saas';
    }

    public function isRetriable(): bool
    {
        return true;
    }
}
