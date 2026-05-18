<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Performance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\HRM\Models\HrmPerformanceReview;
use Aero\HRM\Models\ReviewCycle;
use Illuminate\Support\Facades\DB;

class ReviewCycleService
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function activate(ReviewCycle $cycle): ReviewCycle
    {
        return DB::transaction(function () use ($cycle): ReviewCycle {
            $cycle->status = ReviewCycle::STATUS_ACTIVE;
            $cycle->save();

            $employeeIds = $cycle->employee_ids ?? [];

            foreach ($employeeIds as $employeeId) {
                HrmPerformanceReview::firstOrCreate(
                    [
                        'cycle_id' => $cycle->id,
                        'employee_id' => $employeeId,
                    ],
                    [
                        'status' => HrmPerformanceReview::STATUS_DRAFT,
                    ]
                );
            }

            $this->audit->log(
                event: AuditEventType::REVIEW_CYCLE_ACTIVATED->value,
                action: 'cycle_activated',
                subject: $cycle,
                description: "Review cycle '{$cycle->name}' activated with ".count($employeeIds).' employee(s).',
            );

            return $cycle->fresh(['template', 'reviews']);
        });
    }
}
