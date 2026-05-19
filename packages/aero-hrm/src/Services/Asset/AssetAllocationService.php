<?php

namespace Aero\HRM\Services\Asset;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmAsset;
use Aero\HRM\Models\HrmAssetAllocation;
use Illuminate\Support\Facades\DB;

final class AssetAllocationService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function allocate(HrmAsset $asset, int $employeeId, array $payload, int $actorId): HrmAssetAllocation
    {
        abort_unless($asset->status === HrmAsset::STATUS_AVAILABLE, 422, 'Asset is not available for allocation.');

        return DB::transaction(function () use ($asset, $employeeId, $payload, $actorId) {
            $allocation = $asset->allocations()->create([
                'employee_id' => $employeeId,
                'allocated_at' => now(),
                'condition_on_allocation' => $payload['condition'] ?? 'good',
                'allocation_notes' => $payload['notes'] ?? null,
                'allocated_by' => $actorId,
            ]);

            $asset->update(['status' => HrmAsset::STATUS_ALLOCATED]);

            $this->audit->log(
                event: 'ASSET_ALLOCATED',
                action: 'allocate',
                subject: $allocation,
                description: "Asset {$asset->tag} allocated to employee {$employeeId}"
            );

            return $allocation;
        });
    }

    public function returnAsset(HrmAssetAllocation $allocation, array $payload, int $actorId): HrmAssetAllocation
    {
        abort_unless($allocation->returned_at === null, 422, 'Allocation already closed.');

        return DB::transaction(function () use ($allocation, $payload, $actorId) {
            $allocation->loadMissing('asset');

            $allocation->update([
                'returned_at' => now(),
                'condition_on_return' => $payload['condition'] ?? 'good',
                'return_notes' => $payload['notes'] ?? null,
                'returned_by' => $actorId,
            ]);

            $allocation->asset->update(['status' => HrmAsset::STATUS_AVAILABLE]);

            $this->audit->log(
                event: 'ASSET_RETURNED',
                action: 'return',
                subject: $allocation,
                description: "Asset {$allocation->asset->tag} returned by employee {$allocation->employee_id}"
            );

            return $allocation;
        });
    }
}
