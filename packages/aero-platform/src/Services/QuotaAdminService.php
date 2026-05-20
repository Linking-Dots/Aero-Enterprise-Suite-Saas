<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\QuotaEnforcementSetting;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantQuotaOverride;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotaAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function listOverrides(array $filters = []): LengthAwarePaginator
    {
        return TenantQuotaOverride::query()
            ->with(['tenant:id,name,subdomain', 'setter:id,name,email'])
            ->when($filters['resource'] ?? null, fn ($q, $r) => $q->where('resource', $r))
            ->when($filters['tenant_id'] ?? null, fn ($q, $t) => $q->where('tenant_id', $t))
            ->latest('id')->paginate(25)->withQueryString();
    }

    public function setOverride(Tenant $tenant, string $resource, int $limit, ?string $reason, ?string $expiresAt): TenantQuotaOverride
    {
        return DB::transaction(function () use ($tenant, $resource, $limit, $reason, $expiresAt) {
            $current = $this->currentUsage($tenant, $resource);
            if ($limit < $current) {
                abort(422, "Limit ({$limit}) cannot be below current usage ({$current}).");
            }

            $override = TenantQuotaOverride::updateOrCreate(
                ['tenant_id' => $tenant->id, 'resource' => $resource],
                [
                    'limit_value' => $limit,
                    'reason' => $reason,
                    'expires_at' => $expiresAt,
                    'set_by' => Auth::guard('landlord')->id(),
                ]
            );

            $this->audit->log(
                event: 'platform.quota.override.set',
                action: 'override',
                subject: $override,
                description: "Quota override for tenant {$tenant->name} resource={$resource} limit={$limit}",
            );

            return $override;
        });
    }

    public function removeOverride(Tenant $tenant, string $resource): void
    {
        DB::transaction(function () use ($tenant, $resource) {
            $override = TenantQuotaOverride::where('tenant_id', $tenant->id)
                ->where('resource', $resource)
                ->firstOrFail();

            $this->audit->log(
                event: 'platform.quota.override.removed',
                action: 'override',
                subject: $override,
                description: "Removed quota override for tenant {$tenant->name} resource={$resource}",
            );

            $override->delete();
        });
    }

    public function updateSettings(string $resource, array $data): QuotaEnforcementSetting
    {
        return DB::transaction(function () use ($resource, $data) {
            $setting = QuotaEnforcementSetting::updateOrCreate(
                ['resource' => $resource],
                [
                    'default_limit' => $data['default_limit'],
                    'warning_threshold_pct' => $data['warning_threshold_pct'] ?? 80,
                    'hard_limit_pct' => $data['hard_limit_pct'] ?? 100,
                    'action' => $data['action'] ?? QuotaEnforcementSetting::ACTION_WARN,
                ]
            );

            $this->audit->log(
                event: 'platform.quota.settings.updated',
                action: 'edit',
                subject: $setting,
                description: "Quota enforcement settings updated for resource={$resource}",
            );

            return $setting;
        });
    }

    public function analytics(): array
    {
        return [
            'overrides_count' => TenantQuotaOverride::count(),
            'by_resource' => TenantQuotaOverride::query()
                ->select('resource', DB::raw('count(*) as count'))
                ->groupBy('resource')->pluck('count', 'resource')->all(),
            'expiring_soon' => TenantQuotaOverride::where('expires_at', '<=', now()->addDays(7))
                ->where('expires_at', '>=', now())->count(),
        ];
    }

    private function currentUsage(Tenant $tenant, string $resource): int
    {
        return (int) DB::connection('central')->table('tenant_stats')
            ->where('tenant_id', $tenant->id)
            ->latest('date')
            ->value(match ($resource) {
                'storage_gb' => 'storage_mb',
                'api_calls' => 'api_requests',
                'users' => 'active_users',
                default => 'storage_mb',
            }) ?? 0;
    }
}
