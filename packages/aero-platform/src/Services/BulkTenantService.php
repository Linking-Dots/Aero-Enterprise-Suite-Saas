<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Jobs\ExecuteBulkTenantAction;
use Aero\Platform\Models\BulkOperation;
use Illuminate\Support\Facades\DB;

class BulkTenantService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function execute(string $type, array $tenantIds, array $payload, int $actorId): BulkOperation
    {
        return DB::transaction(function () use ($type, $tenantIds, $payload, $actorId) {
            $op = BulkOperation::create([
                'type' => $type,
                'payload' => array_merge($payload, ['tenant_ids' => $tenantIds]),
                'status' => 'queued',
                'created_by' => $actorId,
                'total' => count($tenantIds),
                'processed' => 0,
            ]);

            foreach ($tenantIds as $tid) {
                ExecuteBulkTenantAction::dispatch($op->id, $tid, $type, $payload);
            }

            $this->audit->log(
                event: AuditEventType::TENANT_BULK_OPERATION_QUEUED->value,
                action: $type,
                subject: $op,
                description: "Bulk {$type} queued for ".count($tenantIds).' tenants'
            );

            return $op;
        });
    }
}
