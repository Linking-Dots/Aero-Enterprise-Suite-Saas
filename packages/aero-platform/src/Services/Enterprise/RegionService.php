<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\Region;
use Aero\Platform\Models\Enterprise\TenantRegionAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RegionService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(): Collection
    {
        return Region::orderBy('name')->get();
    }

    public function enable(Region $region): Region
    {
        return DB::transaction(function () use ($region): Region {
            $region->update(['is_active' => true]);

            $this->audit->log(
                event: AuditEventType::REGION_ENABLED->value,
                action: 'enable',
                subject: $region,
                description: "Region {$region->code} enabled"
            );

            return $region->refresh();
        });
    }

    public function disable(Region $region): Region
    {
        return DB::transaction(function () use ($region): Region {
            $region->update(['is_active' => false]);

            $this->audit->log(
                event: AuditEventType::REGION_DISABLED->value,
                action: 'disable',
                subject: $region,
                description: "Region {$region->code} disabled"
            );

            return $region->refresh();
        });
    }

    public function assignTenant(string $tenantId, string $regionId, string $assignedBy): TenantRegionAssignment
    {
        return DB::transaction(function () use ($tenantId, $regionId, $assignedBy): TenantRegionAssignment {
            $assignment = TenantRegionAssignment::updateOrCreate(
                ['tenant_id' => $tenantId],
                ['region_id' => $regionId, 'assigned_by' => $assignedBy, 'assigned_at' => now()]
            );

            $this->audit->log(
                event: AuditEventType::TENANT_REGION_ASSIGNED->value,
                action: 'assign',
                subject: $assignment,
                description: "Tenant {$tenantId} assigned to region {$regionId}"
            );

            return $assignment;
        });
    }

    public function configureCdn(Region $region, array $config): Region
    {
        return DB::transaction(function () use ($region, $config): Region {
            $region->update(['cdn_config' => $config]);

            $this->audit->log(
                event: AuditEventType::REGION_CDN_CONFIGURED->value,
                action: 'configure',
                subject: $region,
                description: "CDN configured for region {$region->code}"
            );

            return $region->refresh();
        });
    }
}
