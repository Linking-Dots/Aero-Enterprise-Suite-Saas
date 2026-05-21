<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\ProductSubscription;
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
                event: AuditEventType::TENANT_PROVISIONING_QUEUED->value,
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
                event: AuditEventType::TENANT_PROVISIONING_RETRIED->value,
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

            // Activate any trialing ProductSubscriptions for this tenant
            ProductSubscription::where('tenant_id', $tenant->id)
                ->where('status', 'trialing')
                ->update(['status' => 'active', 'trial_ends_at' => null, 'starts_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_APPROVED->value,
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
                event: AuditEventType::TENANT_REJECTED->value,
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
                event: AuditEventType::TENANT_TRIAL_EXTENDED->value,
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

            // Activate any trialing ProductSubscriptions for this tenant
            ProductSubscription::where('tenant_id', $tenant->id)
                ->where('status', 'trialing')
                ->update(['status' => 'active', 'trial_ends_at' => null, 'starts_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_TRIAL_CONVERTED->value,
                action: 'convert',
                subject: $tenant,
                description: "Trial converted to paid for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }
}
