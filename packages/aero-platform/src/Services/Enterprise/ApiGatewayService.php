<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\ApiQuotaOverride;
use Aero\Platform\Models\Enterprise\ApiRateLimitOverride;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ApiGatewayService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function getRateLimits(?string $tenantId = null): Collection
    {
        return ApiRateLimitOverride::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();
    }

    public function updateRateLimit(string $tenantId, array $data, string $updatedBy): ApiRateLimitOverride
    {
        return DB::transaction(function () use ($tenantId, $data, $updatedBy): ApiRateLimitOverride {
            $override = ApiRateLimitOverride::updateOrCreate(
                ['tenant_id' => $tenantId],
                [
                    'requests_per_minute' => $data['requests_per_minute'],
                    'requests_per_day' => $data['requests_per_day'] ?? null,
                    'updated_by' => $updatedBy,
                ]
            );

            $this->audit->log(
                event: AuditEventType::API_RATE_LIMIT_UPDATED->value,
                action: 'update',
                subject: $override,
                description: "API rate limit updated for tenant {$tenantId}: {$data['requests_per_minute']} req/min"
            );

            return $override;
        });
    }

    public function getApiQuotas(?string $tenantId = null): Collection
    {
        return ApiQuotaOverride::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();
    }

    public function configureQuota(string $tenantId, string $resource, int $limit, string $updatedBy): ApiQuotaOverride
    {
        return DB::transaction(function () use ($tenantId, $resource, $limit, $updatedBy): ApiQuotaOverride {
            $override = ApiQuotaOverride::updateOrCreate(
                ['tenant_id' => $tenantId, 'resource' => $resource],
                ['limit' => $limit, 'updated_by' => $updatedBy]
            );

            $this->audit->log(
                event: AuditEventType::API_QUOTA_CONFIGURED->value,
                action: 'configure',
                subject: $override,
                description: "API quota configured for tenant {$tenantId}: {$resource}={$limit}"
            );

            return $override;
        });
    }

    public function usageAnalytics(string $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'rate_limit' => ApiRateLimitOverride::where('tenant_id', $tenantId)->first()?->toArray(),
            'quotas' => ApiQuotaOverride::where('tenant_id', $tenantId)->get()->toArray(),
        ];
    }
}
