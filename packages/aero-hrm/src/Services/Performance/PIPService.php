<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Performance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\PerformanceImprovementPlan;
use Illuminate\Support\Facades\DB;

class PIPService
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function create(array $payload): PerformanceImprovementPlan
    {
        return DB::transaction(function () use ($payload): PerformanceImprovementPlan {
            /** @var PerformanceImprovementPlan $pip */
            $pip = PerformanceImprovementPlan::create([
                'employee_id' => $payload['employee_id'],
                'title' => $payload['title'],
                'objectives' => $payload['objectives'],
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'],
                'status' => $payload['status'] ?? PerformanceImprovementPlan::STATUS_DRAFT,
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->audit->log(
                event: AuditEventType::PIP_CREATED->value,
                action: 'pip_created',
                subject: $pip,
                description: "Performance Improvement Plan '{$pip->title}' created for employee #{$pip->employee_id}.",
            );

            return $pip->fresh(['employee']);
        });
    }
}
