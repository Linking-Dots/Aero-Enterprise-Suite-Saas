<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantProvisioningLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $q = Tenant::query()->with('domains');

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['plan_id'])) {
            $planId = $filters['plan_id'];
            $q->whereHas('currentSubscription', fn ($sq) => $sq->where('plan_id', $planId));
        }

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('id', $s));
        }

        return $q->orderByDesc('created_at')->paginate(config('aero-platform.admin_page_size', 25))->withQueryString();
    }

    public function show(string $tenantId): Tenant
    {
        return Tenant::with([
            'domains',
            'provisioningLogs' => fn ($q) => $q->latest()->limit(20),
        ])->findOrFail($tenantId);
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'id' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'status' => 'provisioning',
                'byoc_enabled' => $data['byoc_enabled'] ?? false,
            ]);

            TenantProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'status' => 'pending',
                'step' => 'queued',
                'message' => 'Tenant created, provisioning queued',
            ]);

            if (! empty($data['product_id'])) {
                ProductSubscription::create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $data['product_id'],
                    'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                    'amount' => 0, // set by billing flow; 0 for admin-created tenants
                    'currency' => 'USD',
                    'status' => 'trialing',
                    'starts_at' => now(),
                    'trial_ends_at' => now()->addDays(14),
                ]);
            }

            $this->audit->log(
                event: AuditEventType::TENANT_CREATED->value,
                action: 'create',
                subject: $tenant,
                description: "Tenant {$tenant->name} created"
            );

            return $tenant;
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
            ], fn ($v) => $v !== null);

            if (! empty($updateData)) {
                $tenant->update($updateData);
            }

            $this->audit->log(
                event: AuditEventType::TENANT_UPDATED->value,
                action: 'update',
                subject: $tenant,
                description: "Tenant {$tenant->name} updated"
            );

            return $tenant->fresh();
        });
    }

    public function suspend(Tenant $tenant, string $reason): Tenant
    {
        if ($tenant->status === 'suspended') {
            throw new \DomainException('Tenant is already suspended');
        }

        return DB::transaction(function () use ($tenant, $reason) {
            $tenant->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_SUSPENDED->value,
                action: 'suspend',
                subject: $tenant,
                description: "Suspended: {$reason}"
            );

            return $tenant->fresh();
        });
    }

    public function activate(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
                'frozen_at' => null,
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_ACTIVATED->value,
                action: 'activate',
                subject: $tenant,
                description: "Tenant {$tenant->name} activated"
            );

            return $tenant->fresh();
        });
    }

    public function freeze(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'frozen', 'frozen_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_FROZEN->value,
                action: 'freeze',
                subject: $tenant,
                description: "Tenant {$tenant->name} frozen"
            );

            return $tenant->fresh();
        });
    }

    public function unfreeze(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'frozen_at' => null]);

            $this->audit->log(
                event: AuditEventType::TENANT_UNFROZEN->value,
                action: 'unfreeze',
                subject: $tenant,
                description: "Tenant {$tenant->name} unfrozen"
            );

            return $tenant->fresh();
        });
    }

    public function archive(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'archived', 'archived_at' => now()]);

            $this->audit->log(
                event: AuditEventType::TENANT_ARCHIVED->value,
                action: 'archive',
                subject: $tenant,
                description: "Tenant {$tenant->name} archived"
            );

            return $tenant->fresh();
        });
    }

    public function restore(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'archived_at' => null]);

            $this->audit->log(
                event: AuditEventType::TENANT_RESTORED->value,
                action: 'restore',
                subject: $tenant,
                description: "Tenant {$tenant->name} restored"
            );

            return $tenant->fresh();
        });
    }

    public function purge(Tenant $tenant, int $actorId): void
    {
        DB::transaction(function () use ($tenant, $actorId) {
            $this->audit->log(
                event: AuditEventType::TENANT_PURGED->value,
                action: 'delete',
                subject: $tenant,
                description: "Tenant {$tenant->name} purged by user {$actorId}"
            );

            // TODO(P-10): Dispatch TenantPurgeJob to drop tenant DB, revoke domains, cancel Stripe subscription
            $tenant->delete();
        });
    }

    public function updateByocCredentials(Tenant $tenant, array $credentials): Tenant
    {
        return DB::transaction(function () use ($tenant, $credentials) {
            $tenant->update([
                'byoc_enabled' => true,
                'byoc_db_host' => $credentials['host'],
                'byoc_db_name' => $credentials['database'],
                'byoc_db_username' => $credentials['username'],
                'byoc_db_password' => $credentials['password'],
            ]);

            $this->audit->log(
                event: AuditEventType::TENANT_BYOC_UPDATED->value,
                action: 'update',
                subject: $tenant,
                description: "BYOC credentials updated for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }
}
