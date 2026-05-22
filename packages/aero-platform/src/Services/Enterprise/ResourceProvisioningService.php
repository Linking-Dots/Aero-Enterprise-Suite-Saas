<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Enterprise;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Enterprise\AutoScalingRule;
use Aero\Platform\Models\Enterprise\DbServerPool;
use Aero\Platform\Models\Enterprise\StorageBackend;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ResourceProvisioningService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function manageDbPools(array $data): DbServerPool
    {
        return DB::transaction(function () use ($data): DbServerPool {
            $pool = DbServerPool::create($data);

            $this->audit->log(
                event: AuditEventType::DB_POOL_CREATED->value,
                action: 'create',
                subject: $pool,
                description: "DB pool '{$pool->name}' created"
            );

            return $pool;
        });
    }

    public function listDbPools(): Collection
    {
        return DbServerPool::where('is_active', true)->get();
    }

    public function manageStorage(array $data): StorageBackend
    {
        return DB::transaction(function () use ($data): StorageBackend {
            $backend = StorageBackend::create($data);

            $this->audit->log(
                event: AuditEventType::STORAGE_BACKEND_CREATED->value,
                action: 'create',
                subject: $backend,
                description: "Storage backend '{$backend->name}' created"
            );

            return $backend;
        });
    }

    public function configureAutoScaling(array $data): AutoScalingRule
    {
        return DB::transaction(function () use ($data): AutoScalingRule {
            $rule = AutoScalingRule::updateOrCreate(
                ['resource_type' => $data['resource_type']],
                $data
            );

            $this->audit->log(
                event: AuditEventType::AUTO_SCALING_CONFIGURED->value,
                action: 'configure',
                subject: $rule,
                description: "Auto-scaling configured for resource type {$data['resource_type']}"
            );

            return $rule;
        });
    }
}
