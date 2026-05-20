<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantProvisioningLog;
use Illuminate\Support\Facades\DB;

class TenantProvisioningService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function queue(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            TenantProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'status' => 'pending',
                'step' => 'queued',
                'message' => 'Provisioning queued',
            ]);

            $tenant->update(['status' => 'provisioning']);

            $this->audit->log(
                event: 'TENANT_PROVISIONING_QUEUED',
                action: 'queue',
                subject: $tenant,
                description: "Provisioning queued for {$tenant->name}"
            );
        });
    }

    public function retry(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            TenantProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'status' => 'pending',
                'step' => 'retry',
                'message' => 'Provisioning retry requested',
            ]);

            $tenant->update(['status' => 'provisioning']);

            $this->audit->log(
                event: 'TENANT_PROVISIONING_RETRIED',
                action: 'retry',
                subject: $tenant,
                description: "Provisioning retry queued for {$tenant->name}"
            );
        });
    }

    public function approve(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active']);

            $this->audit->log(
                event: 'TENANT_APPROVED',
                action: 'approve',
                subject: $tenant,
                description: "Tenant {$tenant->name} approved"
            );
        });
    }

    public function reject(Tenant $tenant, string $reason): void
    {
        DB::transaction(function () use ($tenant, $reason) {
            $tenant->update(['status' => 'failed']);

            $this->audit->log(
                event: 'TENANT_REJECTED',
                action: 'reject',
                subject: $tenant,
                description: "Tenant {$tenant->name} rejected: {$reason}"
            );
        });
    }

    public function extendTrial(Tenant $tenant, int $days): Tenant
    {
        return DB::transaction(function () use ($tenant, $days) {
            $trialEnds = ($tenant->stripe_trial_ends_at ?? now())->addDays($days);
            $tenant->update(['stripe_trial_ends_at' => $trialEnds]);

            $this->audit->log(
                event: 'TENANT_TRIAL_EXTENDED',
                action: 'extend',
                subject: $tenant,
                description: "Trial extended by {$days} days for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }

    public function convertTrial(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'stripe_trial_ends_at' => null]);

            $this->audit->log(
                event: 'TENANT_TRIAL_CONVERTED',
                action: 'convert',
                subject: $tenant,
                description: "Trial converted to paid for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }
}
